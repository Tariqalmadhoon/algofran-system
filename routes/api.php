<?php

use App\Http\Controllers\Api\V1\AcademicController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\HalaqaController;
use App\Http\Controllers\Api\V1\MetaController;
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
    });
});
