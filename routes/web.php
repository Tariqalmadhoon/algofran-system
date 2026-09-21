<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordConfirmationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\MobileDistributionController;
use App\Http\Controllers\MobileReleaseChunkUploadController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherDailyController;
use App\Http\Controllers\TeacherMobileAppController;
use App\Http\Middleware\EnsureStrongIdentity;
use App\Http\Middleware\EnsureSuperAdministrator;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('public.home');
Route::get('/about', [PublicSiteController::class, 'about'])->name('public.about');
Route::get('/programs', [PublicSiteController::class, 'programs'])->name('public.programs');
Route::get('/activities', [PublicSiteController::class, 'activities'])->name('activities.index');
Route::get('/activities/{content:slug}', [PublicSiteController::class, 'activity'])->name('activities.show');
Route::get('/news', [PublicSiteController::class, 'news'])->name('news.index');
Route::get('/news/{content:slug}', [PublicSiteController::class, 'newsItem'])->name('news.show');
Route::get('/achievements', [PublicSiteController::class, 'achievements'])->name('public.achievements');
Route::get('/gallery', [PublicSiteController::class, 'gallery'])->name('public.gallery');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('public.contact');
Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('public.sitemap');
Route::get('/robots.txt', [PublicSiteController::class, 'robots'])->name('public.robots');
Route::get('/ready', ReadinessController::class)->middleware('throttle:30,1')->name('system.ready');
Route::get('/pages/{content:slug}', [PublicSiteController::class, 'page'])->name('public.page');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:6,1')->name('two-factor.challenge.store');
});

Route::middleware(['auth', EnsureUserIsActive::class, 'auth.session', EnsureStrongIdentity::class])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/confirm-password', [PasswordConfirmationController::class, 'create'])->name('password.confirm');
    Route::post('/confirm-password', [PasswordConfirmationController::class, 'store'])->middleware('throttle:6,1')->name('password.confirm.store');

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/password/check', [ProfileController::class, 'checkPassword'])
        ->middleware('throttle:20,1')
        ->name('profile.password.check');

    Route::middleware('password.confirm')->group(function () {
        Route::get('/profile/security', [SecurityController::class, 'show'])->name('profile.security');
        Route::post('/profile/security/two-factor', [SecurityController::class, 'enable'])->name('profile.two-factor.enable');
        Route::post('/profile/security/two-factor/confirm', [SecurityController::class, 'confirm'])->middleware('throttle:6,1')->name('profile.two-factor.confirm');
        Route::post('/profile/security/two-factor/rotate', [SecurityController::class, 'rotate'])->name('profile.two-factor.rotate');
        Route::delete('/profile/security/two-factor', [SecurityController::class, 'disable'])->name('profile.two-factor.disable');
        Route::post('/profile/security/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('profile.two-factor.recovery');
        Route::delete('/profile/security/sessions', [SecurityController::class, 'revokeOtherSessions'])->name('profile.sessions.destroy-others');
    });

    Route::view('/organization', 'organization')
        ->middleware('can:organization.view')
        ->name('organization.index');

    Route::view('/access-control', 'access.index')
        ->middleware(EnsureSuperAdministrator::class)
        ->name('access.index');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/trash', [StudentController::class, 'trash'])->name('students.trash');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/teacher/daily', TeacherDailyController::class)->name('teacher.daily');
    Route::get('/teacher/mobile-app', TeacherMobileAppController::class)
        ->middleware('can:recitations.create')
        ->name('teacher.mobile.app');
    Route::get('/teacher/mobile-app/download/{versionCode}', [TeacherMobileAppController::class, 'download'])
        ->middleware('can:recitations.create')
        ->whereNumber('versionCode')
        ->name('teacher.mobile.app.download');

    Route::view('/academic', 'academic.index')
        ->middleware('can:courses.manage')
        ->name('academic.index');
    Route::view('/alerts', 'alerts.index')
        ->middleware('can:alerts.view')
        ->name('alerts.index');
    Route::view('/calendar', 'calendar.index')
        ->middleware('can:calendar.view')
        ->name('calendar.index');
    Route::view('/notifications', 'notifications.index')
        ->middleware('can:notifications.view')
        ->name('notifications.index');
    Route::view('/reports', 'reports.index')
        ->middleware('can:reports.view')
        ->name('reports.index');
    Route::view('/cms', 'cms.index')
        ->middleware('can:website.manage')
        ->name('cms.index');
    Route::get('/mobile-distribution', MobileDistributionController::class)
        ->middleware(EnsureSuperAdministrator::class)
        ->name('mobile.distribution');
    Route::post('/mobile-distribution/apk-chunks', MobileReleaseChunkUploadController::class)
        ->middleware(['throttle:mobile-release-upload', EnsureSuperAdministrator::class])
        ->name('mobile.distribution.apk-chunks');

    Route::get('/private-files/{privateFile}', [PrivateFileController::class, 'show'])
        ->name('private-files.show');
    Route::get('/private-files/{privateFile}/preview', [PrivateFileController::class, 'preview'])
        ->name('private-files.preview');
});
