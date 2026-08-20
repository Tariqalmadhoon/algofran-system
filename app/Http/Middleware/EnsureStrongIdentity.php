<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStrongIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->requiresTwoFactorAuthentication() || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'يلزم تفعيل المصادقة الثنائية لهذا الحساب.',
                'error' => ['code' => 'two_factor_setup_required'],
            ], 403);
        }

        if ($request->routeIs([
            'profile.security', 'profile.two-factor.*', 'profile.sessions.*',
            'password.confirm', 'password.confirm.store', 'logout',
        ])) {
            return $next($request);
        }

        return redirect()->route('profile.security')->with('error', 'يلزم إعداد المصادقة الثنائية قبل متابعة استخدام الحساب.');
    }
}
