<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless(config('system.identity.two_factor_enabled', false), 404);

        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(
        Request $request,
        TwoFactorAuthenticationService $twoFactor,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_unless(config('system.identity.two_factor_enabled', false), 404);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ]);
        $user = User::query()->find($request->session()->get('login.id'));
        if (! $user || ! $user->active || $user->archived_at || ! $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login')->withErrors(['email' => 'انتهت محاولة تسجيل الدخول.']);
        }

        $key = 'web-two-factor:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'محاولات كثيرة. حاول مجددًا بعد دقيقة.']);
        }

        if (! $twoFactor->verify($user, $data['code'] ?? null, $data['recovery_code'] ?? null)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['code' => 'رمز المصادقة الثنائية غير صحيح.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('auth.password_confirmed_at', time());
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.two-factor.passed', $user, actor: $user);
        $audit->record('auth.login', $user, actor: $user);

        return redirect()->intended(route('dashboard'));
    }
}
