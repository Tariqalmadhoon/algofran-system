<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordConfirmationController extends Controller
{
    public function create(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->session()->put('auth.password_confirmed_at', time());
        $audit->record('auth.password.confirmed', $request->user());

        return redirect()->route('profile.security');
    }
}
