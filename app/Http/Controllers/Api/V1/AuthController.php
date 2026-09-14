<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\MobileDevice;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MobileTwoFactorChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request, AuditLogger $audit, MobileTwoFactorChallengeService $challenges): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
            'device_uuid' => ['nullable', 'uuid'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);
        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.', 'error' => ['code' => 'invalid_credentials']], 422);
        }
        if (! $user->active || $user->archived_at) {
            return response()->json(['message' => 'هذا الحساب غير نشط.', 'error' => ['code' => 'account_inactive']], 403);
        }

        if ($user->requiresTwoFactorAuthentication() && ! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'يلزم إعداد المصادقة الثنائية من بوابة الويب قبل استخدام هذا الحساب على جهاز.',
                'error' => ['code' => 'two_factor_setup_required'],
            ], 403);
        }

        if (config('system.identity.two_factor_enabled', false) && $user->hasEnabledTwoFactorAuthentication()) {
            $challenge = $challenges->issue($user, $data['device_name'], $this->deviceData($data));

            return response()->json([
                'message' => 'أدخل رمز المصادقة الثنائية لإكمال تسجيل الدخول.',
                'data' => [
                    'two_factor_required' => true,
                    'challenge_token' => $challenge['token'],
                    'expires_in' => $challenge['expires_in'],
                ],
            ], 202);
        }

        return $this->issueToken($user, $data['device_name'], $audit, $this->deviceData($data));
    }

    public function twoFactorChallenge(
        Request $request,
        AuditLogger $audit,
        MobileTwoFactorChallengeService $challenges,
    ): JsonResponse {
        abort_unless(config('system.identity.two_factor_enabled', false), 404);

        $data = $request->validate([
            'challenge_token' => ['required', 'string', 'size:80'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ]);
        $challenge = $challenges->consume(
            $data['challenge_token'],
            $data['code'] ?? null,
            $data['recovery_code'] ?? null,
        );

        if (! $challenge) {
            return response()->json([
                'message' => 'رمز المصادقة أو جلسة التحدي غير صالحة.',
                'error' => ['code' => 'invalid_two_factor_challenge'],
            ], 422);
        }

        $audit->record('api.two-factor.passed', $challenge['user'], actor: $challenge['user']);

        return $this->issueToken(
            $challenge['user'],
            $challenge['device_name'],
            $audit,
            $challenge['device'] ?? [],
        );
    }

    private function issueToken(User $user, string $deviceName, AuditLogger $audit, array $deviceData = []): JsonResponse
    {
        abort_if(! $user->active || $user->archived_at, 403, 'هذا الحساب غير نشط.');

        return DB::transaction(function () use ($user, $deviceName, $audit, $deviceData): JsonResponse {
            $deviceUuid = $deviceData['device_uuid'] ?? null;
            $tokenName = $deviceUuid ? $deviceName.' ['.$deviceUuid.']' : $deviceName;
            $tokenNamesToReplace = $deviceUuid ? [$tokenName, $deviceName] : [$tokenName];
            $user->tokens()->whereIn('name', $tokenNamesToReplace)->delete();
            $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 43200));
            $abilities = ['mobile:read'];
            if ($user->can('recitations.create')
                && $user->can('attendance.manage')
                && $user->teacherProfile()->where('active', true)->exists()) {
                $abilities[] = 'mobile:sync';
            }
            if ($user->can('recitations.export')
                && $user->teacherProfile()->where('active', true)->exists()) {
                $abilities[] = 'mobile:export';
            }

            $token = $user->createToken($tokenName, $abilities, $expiresAt)->plainTextToken;
            $device = null;
            if (! empty($deviceData['device_uuid'])) {
                $device = MobileDevice::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'uuid' => $deviceData['device_uuid'],
                ], [
                    'name' => $deviceName,
                    'platform' => $deviceData['platform'] ?? null,
                    'app_version' => $deviceData['app_version'] ?? null,
                    'last_seen_at' => now(),
                    'disabled_at' => null,
                ]);
            }

            $user->forceFill(['last_login_at' => now()])->save();
            $audit->record('api-token.created', $user, newValues: [
                'device_name' => $deviceName,
                'device_uuid' => $device?->uuid,
                'abilities' => $abilities,
                'expires_at' => $expiresAt->toIso8601String(),
            ], actor: $user);

            return response()->json(['message' => 'تم تسجيل الدخول بنجاح.', 'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toIso8601String(),
                'abilities' => $abilities,
                'device_uuid' => $device?->uuid,
                'user' => new UserResource($user),
            ]]);
        });
    }

    public function current(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();
        $deviceName = $accessToken instanceof PersonalAccessToken ? $accessToken->name : 'session';
        $audit->record('api-token.revoked', $request->user(), newValues: ['device_name' => $deviceName]);
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->json(['message' => 'تم تسجيل الخروج وإلغاء رمز الجهاز.']);
    }

    private function deviceData(array $data): array
    {
        return [
            'device_uuid' => $data['device_uuid'] ?? null,
            'platform' => $data['platform'] ?? null,
            'app_version' => $data['app_version'] ?? null,
        ];
    }
}
