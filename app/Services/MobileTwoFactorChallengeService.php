<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class MobileTwoFactorChallengeService
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    /** @return array{token:string,expires_in:int} */
    public function issue(User $user, string $deviceName): array
    {
        $ttl = (int) config('system.identity.challenge_ttl_seconds', 300);
        $token = Str::random(80);
        $expiresAt = now()->addSeconds($ttl);

        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'device_name' => $deviceName,
            'attempts' => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);

        return ['token' => $token, 'expires_in' => $ttl];
    }

    /** @return array{user:User,device_name:string}|null */
    public function consume(string $token, ?string $code, ?string $recoveryCode): ?array
    {
        $key = $this->key($token);

        try {
            return Cache::lock($key.':lock', 5)->block(2, function () use ($key, $code, $recoveryCode): ?array {
                $challenge = Cache::get($key);
                if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->getTimestamp()) {
                    Cache::forget($key);

                    return null;
                }

                $user = User::query()->find($challenge['user_id'] ?? null);
                if (! $user || ! $user->active || ! $this->twoFactor->verify($user, $code, $recoveryCode)) {
                    $attempts = (int) ($challenge['attempts'] ?? 0) + 1;
                    if ($attempts >= (int) config('system.identity.challenge_max_attempts', 5)) {
                        Cache::forget($key);
                    } else {
                        $challenge['attempts'] = $attempts;
                        Cache::put($key, $challenge, now()->setTimestamp((int) $challenge['expires_at']));
                    }

                    return null;
                }

                Cache::forget($key);

                return ['user' => $user, 'device_name' => (string) $challenge['device_name']];
            });
        } catch (Throwable) {
            return null;
        }
    }

    private function key(string $token): string
    {
        return 'mobile-two-factor:'.hash('sha256', $token);
    }
}
