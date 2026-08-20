<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $old = $user->only(['name', 'email', 'phone']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if ($user->email !== $data['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($data)->save();
        $auditLogger->record('profile.updated', $user, $old, $user->only(['name', 'email', 'phone']));

        return back()->with('status', 'profile-updated');
    }

    public function password(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);
        $request->user()->tokens()->delete();
        $auditLogger->record('profile.password.changed', $request->user());

        return back()->with('status', 'password-updated');
    }
}
