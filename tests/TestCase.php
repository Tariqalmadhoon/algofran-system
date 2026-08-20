<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($user instanceof User && $user->exists && $user->requiresTwoFactorAuthentication()) {
            $this->completeTwoFactorAuthentication($user);
        }

        return parent::actingAs($user, $guard);
    }

    protected function completeTwoFactorAuthentication(User $user): void
    {
        if ($user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        app(EnableTwoFactorAuthentication::class)($user, true);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }
}
