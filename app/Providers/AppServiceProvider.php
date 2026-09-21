<?php

namespace App\Providers;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols());

        // The signed Android release is larger than Livewire's conservative
        // default (12 MB). Individual forms still enforce their own lower
        // limits; this only permits the protected release publisher to accept
        // the configured APK size.
        config()->set('livewire.temporary_file_upload.rules', [
            'required',
            'file',
            'max:'.max(1024, (int) config('system.mobile_app.upload_max_kilobytes', 131072)),
        ]);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('mobile-release-upload', fn (Request $request) => Limit::perMinute(90)->by($request->user()?->id ?: $request->ip()));

        DB::whenQueryingForLongerThan((int) config('system.monitoring.query_budget_ms', 750), function (Connection $connection, QueryExecuted $event): void {
            Log::warning('Database query budget exceeded.', [
                'connection' => $connection->getName(),
                'total_duration_ms' => $connection->totalQueryDuration(),
                'last_query_duration_ms' => $event->time,
            ]);
        });

        foreach ([Student::class, Halaqa::class, Course::class, Achievement::class] as $model) {
            $model::saved(fn () => Cache::forget('public-site:home-stats:v1'));
            $model::deleted(fn () => Cache::forget('public-site:home-stats:v1'));
        }

        Event::listen(QueueBusy::class, function (QueueBusy $event): void {
            Log::warning('Queue backlog threshold exceeded.', [
                'connection' => $event->connection,
                'queue' => $event->queue,
                'size' => $event->size,
            ]);
        });

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));
    }
}
