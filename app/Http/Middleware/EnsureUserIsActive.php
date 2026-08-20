<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->active) {
            if ($request->expectsJson()) {
                $accessToken = $request->user()->currentAccessToken();
                if ($accessToken instanceof PersonalAccessToken) {
                    $accessToken->delete();
                }

                return response()->json([
                    'message' => 'هذا الحساب غير نشط.',
                    'error' => ['code' => 'account_inactive'],
                ], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'هذا الحساب غير نشط. راجع إدارة المركز.',
            ]);
        }

        return $next($request);
    }
}
