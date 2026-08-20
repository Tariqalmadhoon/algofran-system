<?php

namespace App\Services;

use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Throwable;

class TwoFactorAuthenticationService
{
    public function __construct(private readonly TwoFactorAuthenticationProvider $provider) {}

    public function verify(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return false;
        }

        try {
            if (filled($code)) {
                return $this->provider->verify(
                    Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                    preg_replace('/\s+/', '', (string) $code),
                );
            }

            if (filled($recoveryCode)) {
                $recoveryCode = trim((string) $recoveryCode);
                $matched = collect($user->recoveryCodes())
                    ->first(fn (string $stored): bool => hash_equals($stored, $recoveryCode));

                if ($matched) {
                    $user->replaceRecoveryCode($matched);

                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }
}
