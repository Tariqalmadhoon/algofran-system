<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class SecurityController extends Controller
{
    public function show(Request $request, UserSessionService $sessions): View
    {
        $user = $request->user();
        $twoFactorEnabled = (bool) config('system.identity.two_factor_enabled', false);

        return view('profile.security', [
            'user' => $user,
            'qrCode' => $twoFactorEnabled && $user->two_factor_secret && ! $user->hasEnabledTwoFactorAuthentication()
                ? $user->twoFactorQrCodeSvg()
                : null,
            'recoveryCodes' => $twoFactorEnabled && $user->hasEnabledTwoFactorAuthentication() ? $user->recoveryCodes() : [],
            'sessions' => $sessions->sessions($user, $request),
            'tokens' => $user->tokens()->latest()->get(),
        ]);
    }

    public function enable(
        Request $request,
        EnableTwoFactorAuthentication $enable,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->ensureTwoFactorIsEnabled();
        $enable($request->user());
        $audit->record('auth.two-factor.setup-started', $request->user());

        return back()->with('status', 'two-factor-setup-started');
    }

    public function confirm(
        Request $request,
        ConfirmTwoFactorAuthentication $confirm,
        UserSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->ensureTwoFactorIsEnabled();
        $data = $request->validateWithBag('confirmTwoFactorAuthentication', ['code' => ['required', 'digits:6']]);
        $confirm($request->user(), $data['code']);
        $revoked = $sessions->revokeOthers($request->user(), $request);
        $audit->record('auth.two-factor.enabled', $request->user(), newValues: $revoked);

        return back()->with('status', 'two-factor-enabled');
    }

    public function regenerateRecoveryCodes(
        Request $request,
        GenerateNewRecoveryCodes $generate,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->ensureTwoFactorIsEnabled();
        abort_unless($request->user()->hasEnabledTwoFactorAuthentication(), 409);
        $generate($request->user());
        $audit->record('auth.two-factor.recovery-regenerated', $request->user());

        return back()->with('status', 'recovery-codes-regenerated');
    }

    public function rotate(
        Request $request,
        EnableTwoFactorAuthentication $enable,
        UserSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->ensureTwoFactorIsEnabled();
        abort_unless($request->user()->hasEnabledTwoFactorAuthentication(), 409);
        $request->user()->forceFill(['two_factor_confirmed_at' => null])->save();
        $enable($request->user(), true);
        $revoked = $sessions->revokeOthers($request->user(), $request);
        $audit->record('auth.two-factor.rotated', $request->user(), newValues: $revoked);

        return back()->with('status', 'two-factor-setup-started');
    }

    public function disable(
        Request $request,
        DisableTwoFactorAuthentication $disable,
        UserSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->ensureTwoFactorIsEnabled();
        if ($request->user()->requiresTwoFactorAuthentication()) {
            throw ValidationException::withMessages([
                'two_factor' => 'لا يمكن تعطيل المصادقة الثنائية لهذا الدور. يمكنك استبدال تطبيق المصادقة بدلًا من ذلك.',
            ]);
        }

        $disable($request->user());
        $revoked = $sessions->revokeOthers($request->user(), $request);
        $audit->record('auth.two-factor.disabled', $request->user(), newValues: $revoked);

        return back()->with('status', 'two-factor-disabled');
    }

    public function revokeOtherSessions(
        Request $request,
        UserSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $revoked = $sessions->revokeOthers($request->user(), $request);
        $audit->record('auth.sessions.revoked', $request->user(), newValues: $revoked);

        return back()->with('status', 'other-sessions-revoked');
    }

    private function ensureTwoFactorIsEnabled(): void
    {
        abort_unless(config('system.identity.two_factor_enabled', false), 404);
    }
}
