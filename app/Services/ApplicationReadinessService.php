<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ApplicationReadinessService
{
    public function isReady(): bool
    {
        $key = 'system-readiness:'.Str::uuid();

        try {
            DB::select('SELECT 1');
            Cache::put($key, 'ready', 10);

            return Cache::pull($key) === 'ready'
                && is_dir(storage_path('app/private'))
                && is_writable(storage_path('app/private'))
                && is_writable(storage_path('framework'))
                && is_writable(base_path('bootstrap/cache'));
        } catch (Throwable $exception) {
            Cache::forget($key);
            Log::warning('Application readiness check failed.', ['exception_class' => $exception::class]);

            return false;
        }
    }
}
