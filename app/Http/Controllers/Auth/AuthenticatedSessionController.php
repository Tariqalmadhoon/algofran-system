<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $key = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'محاولات كثيرة. حاول مجددًا بعد دقيقة.']);
        }

        $guard = Auth::guard('web');
        $provider = $guard->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        $credentialsAreValid = false;
        if ($user && $user->active && ! $user->archived_at) {
            try {
                $credentialsAreValid = $provider->validateCredentials($user, $credentials);
            } catch (RuntimeException $exception) {
                Log::warning('Login rejected because the stored password hash is invalid.', [
                    'user_id' => $user->id,
                    'algorithm' => password_get_info((string) $user->getAuthPassword())['algoName'] ?? 'unknown',
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if (! $credentialsAreValid) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة أو الحساب غير نشط.']);
        }

        if (config('hashing.rehash_on_login', true) && method_exists($provider, 'rehashPasswordIfRequired')) {
            $provider->rehashPasswordIfRequired($user, $credentials);
        }

        RateLimiter::clear($key);

        if (config('system.identity.two_factor_enabled', false) && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->regenerate();
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('auth.password_confirmed_at', time());
        $user->forceFill(['last_login_at' => now()])->save();
        $auditLogger->record('auth.login', $user, actor: $user);

        if ($user->requiresTwoFactorAuthentication()) {
            return redirect()->route('profile.security')->with('error', 'يلزم إعداد المصادقة الثنائية قبل متابعة استخدام الحساب.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $auditLogger->record('auth.logout', $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
