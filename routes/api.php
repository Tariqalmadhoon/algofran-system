<?php

use App\Http\Controllers\Api\V1\AcademicController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\HalaqaController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\MobileProfileController;
use App\Http\Controllers\Api\V1\MobileReleaseController;
use App\Http\Controllers\Api\V1\MobileReportExportController;
use App\Http\Controllers\Api\V1\MobileSyncController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Middleware\AddApiVersionHeader;
use App\Http\Middleware\EnsureStrongIdentity;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', AddApiVersionHeader::class])->group(function () {
    Route::get('/meta', MetaController::class);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/auth/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->middleware('throttle:6,1');

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class, EnsureStrongIdentity::class, 'abilities:mobile:read'])->group(function () {
        Route::get('/user', [AuthController::class, 'current']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::middleware('can:recitations.create')->group(function () {
            Route::get('/mobile/releases/latest', [MobileReleaseController::class, 'latest']);
            Route::get('/mobile/releases/android/{versionCode}/download', [MobileReleaseController::class, 'download'])
                ->whereNumber('versionCode')
                ->name('api.v1.mobile-releases.android.download');
        });

        Route::get('/halaqas', [HalaqaController::class, 'index'])->middleware('can:halaqas.view');

        Route::middleware('can:students.view')->group(function () {
            Route::get('/students', [StudentController::class, 'index']);
            Route::get('/students/{student}', [StudentController::class, 'show']);
            Route::get('/students/{student}/progress', [StudentController::class, 'progress']);
            Route::get('/students/{student}/daily-records', [StudentController::class, 'dailyRecords']);
            Route::get('/courses', [CourseController::class, 'index']);
        });

        Route::middleware('can:recitations.view')->group(function () {
            Route::get('/daily-records', [AcademicController::class, 'dailyRecords']);
            Route::get('/recitations', [AcademicController::class, 'recitations']);
            Route::get('/attendance', [AcademicController::class, 'attendance']);
            Route::get('/certificates', [AcademicController::class, 'certificates']);
        });

        Route::get('/calendar', [CalendarController::class, 'index'])->middleware('can:calendar.view');
        Route::get('/alerts', [AlertController::class, 'index'])->middleware('can:alerts.view');

        Route::prefix('mobile')->group(function () {
            Route::middleware('can:students.view')->group(function () {
                Route::get('/profile', [MobileProfileController::class, 'teacher']);
                Route::get('/students/{student}/profile', [MobileProfileController::class, 'student']);
            });

            Route::middleware(['can:recitations.export', 'abilities:mobile:export'])->group(function () {
                Route::get('/report-exports', [MobileReportExportController::class, 'index']);
                Route::post('/report-exports', [MobileReportExportController::class, 'store']);
                Route::get('/report-exports/{reportExport:uuid}', [MobileReportExportController::class, 'show']);
                Route::get('/report-exports/{reportExport:uuid}/download', [MobileReportExportController::class, 'download'])
                    ->name('api.v1.mobile.report-exports.download');
            });

            Route::middleware(['can:recitations.create', 'can:attendance.manage'])->group(function () {
                Route::get('/bootstrap', [MobileSyncController::class, 'bootstrap']);
                Route::get('/sync/changes', [MobileSyncController::class, 'changes']);
                Route::post('/sync/daily-records', [MobileSyncController::class, 'push'])
                    ->middleware('abilities:mobile:sync');
                Route::post('/sync/student-operations', [MobileSyncController::class, 'students'])
                    ->middleware('abilities:mobile:sync');
            });
        });
    });
});
