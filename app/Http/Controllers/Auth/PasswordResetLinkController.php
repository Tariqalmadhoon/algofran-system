<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $user = User::query()
            ->where('email', $email)
            ->where('active', true)
            ->whereNull('archived_at')
            ->first();

        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', 'إذا كان البريد مسجلًا فستصلك رسالة استعادة كلمة المرور.');
    }
}
