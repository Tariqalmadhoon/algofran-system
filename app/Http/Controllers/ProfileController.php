<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\PrivateFileService;
use Illuminate\Http\JsonResponse;
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
        return view('profile.edit', ['user' => $request->user()->load('avatar')]);
    }

    public function update(Request $request, AuditLogger $auditLogger, PrivateFileService $privateFiles): RedirectResponse
    {
        $user = $request->user();
        $user->load('avatar');
        $oldAvatar = $user->avatar;
        $old = $user->only(['name', 'email', 'phone', 'avatar_private_file_id']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar_private_file_id'] = $privateFiles->store(
                $request->file('avatar'),
                $user,
                $user,
                "users/{$user->id}/avatar",
                'user-avatar',
            )->id;
        } elseif ($request->boolean('remove_avatar')) {
            $data['avatar_private_file_id'] = null;
        }

        unset($data['avatar'], $data['remove_avatar']);

        if ($user->email !== $data['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($data)->save();
        $auditLogger->record('profile.updated', $user, $old, $user->only(['name', 'email', 'phone', 'avatar_private_file_id']));

        if ($oldAvatar && $oldAvatar->id !== $user->avatar_private_file_id) {
            $privateFiles->delete($oldAvatar);
        }

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

    public function checkPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['nullable', 'string', 'max:255']]);

        return response()->json([
            'valid' => filled($data['password'] ?? null)
                && Hash::check($data['password'], $request->user()->password),
        ]);
    }
}
