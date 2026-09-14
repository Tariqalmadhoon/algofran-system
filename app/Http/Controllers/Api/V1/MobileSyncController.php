<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncDailyRecordsRequest;
use App\Http\Requests\Api\V1\SyncStudentOperationsRequest;
use App\Models\MobileDevice;
use App\Models\User;
use App\Services\MobileBootstrapService;
use App\Services\MobileDailySyncService;
use App\Services\MobileStudentSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileSyncController extends Controller
{
    public function bootstrap(Request $request, MobileBootstrapService $bootstrap): JsonResponse
    {
        $data = $request->validate(['device_uuid' => ['required', 'uuid']]);
        $device = $this->device($request->user(), $data['device_uuid']);
        $device->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['data' => $bootstrap->build($request->user())]);
    }

    public function push(SyncDailyRecordsRequest $request, MobileDailySyncService $sync): JsonResponse
    {
        $data = $request->validated();
        $device = $this->device($request->user(), $data['device_uuid']);

        return response()->json([
            'data' => $sync->push($request->user(), $device, $data['operations']),
        ]);
    }

    public function changes(Request $request, MobileDailySyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'uuid'],
            'cursor' => ['nullable', 'string', 'max:500'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $device = $this->device($request->user(), $data['device_uuid']);
        $changes = $sync->changes($request->user(), $data['cursor'] ?? null, (int) ($data['limit'] ?? 50));
        $device->forceFill(['last_seen_at' => now(), 'last_synced_at' => now()])->save();

        return response()->json(['data' => $changes]);
    }

    public function students(SyncStudentOperationsRequest $request, MobileStudentSyncService $sync): JsonResponse
    {
        $data = $request->validated();
        $device = $this->device($request->user(), $data['device_uuid']);

        return response()->json([
            'data' => $sync->push($request->user(), $device, $data['operations']),
        ]);
    }

    private function device(User $user, string $uuid): MobileDevice
    {
        $device = MobileDevice::query()
            ->where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->whereNull('disabled_at')
            ->first();

        if (! $device) {
            throw ValidationException::withMessages([
                'device_uuid' => 'هذا الجهاز غير مسجل أو تم إيقافه. سجّل الدخول مجددًا من التطبيق.',
            ]);
        }

        return $device;
    }
}
