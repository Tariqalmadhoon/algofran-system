<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, int $id): bool {
    return $user->active
        && (int) $user->id === $id
        && (! $user->requiresTwoFactorAuthentication() || $user->hasEnabledTwoFactorAuthentication());
});
