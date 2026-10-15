<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PrivateFile;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('SecurePass123!')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.login']);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_accepts_an_existing_bcrypt_hash_and_rehashes_it_when_needed(): void
    {
        $user = User::factory()->create();
        $importedHash = password_hash('ImportedPass123!', PASSWORD_BCRYPT, ['cost' => 10]);
        DB::table('users')->where('id', $user->id)->update(['password' => $importedHash]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ImportedPass123!',
        ])->assertRedirect(route('dashboard'));

        $storedHash = $user->fresh()->password;
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(password_verify('ImportedPass123!', $storedHash));
        $this->assertSame('bcrypt', password_get_info($storedHash)['algoName']);
        $this->assertFalse(Hash::needsRehash($storedHash));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'active' => false,
            'password' => Hash::make('SecurePass123!'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'SecurePass123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return str_contains((string) $mail->subject, 'استعادة كلمة المرور')
                && str_contains((string) $mail->actionUrl, '/reset-password/')
                && str_contains((string) $mail->actionText, 'تعيين كلمة مرور جديدة');
        });
    }

    public function test_password_reset_request_does_not_send_mail_for_an_inactive_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['active' => false]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'إذا كان البريد مسجلًا فستصلك رسالة استعادة كلمة المرور.');

        Notification::assertNothingSent();
    }

    public function test_password_reset_stores_bcrypt_and_revokes_existing_sessions_and_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldSecure123!')]);
        $user->createToken('old-mobile-session', ['mobile:read']);
        DB::table('sessions')->insert([
            'id' => 'existing-web-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test browser',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecure456!',
            'password_confirmation' => 'NewSecure456!',
        ])->assertRedirect(route('login'));

        $storedHash = $user->fresh()->password;
        $this->assertTrue(Hash::check('NewSecure456!', $storedHash));
        $this->assertSame('bcrypt', password_get_info($storedHash)['algoName']);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.password.reset',
        ]);
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldSecure123!')]);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'مستخدم محدث',
            'email' => 'updated@example.com',
            'phone' => '0599000000',
        ])->assertSessionHas('status', 'profile-updated');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'OldSecure123!',
            'password' => 'NewSecure456!',
            'password_confirmation' => 'NewSecure456!',
        ])->assertSessionHas('status', 'password-updated');

        $user->refresh();
        $this->assertSame('مستخدم محدث', $user->name);
        $this->assertTrue(Hash::check('NewSecure456!', $user->password));
        $this->assertSame(2, AuditLog::query()->where('user_id', $user->id)->count());
    }

    public function test_profile_supports_avatar_preview_and_realtime_password_check(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['password' => Hash::make('CurrentSecure123!')]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('قوة كلمة المرور')
            ->assertSee('الصورة الشخصية');

        $this->postJson(route('profile.password.check'), ['password' => 'wrong-password'])
            ->assertOk()
            ->assertJson(['valid' => false]);
        $this->postJson(route('profile.password.check'), ['password' => 'CurrentSecure123!'])
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => null,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
        ])->assertSessionHas('status', 'profile-updated');

        $avatar = PrivateFile::query()->findOrFail($user->fresh()->avatar_private_file_id);
        Storage::disk('private')->assertExists($avatar->path);
        $this->get(route('private-files.preview', $avatar))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => null,
            'remove_avatar' => true,
        ])->assertSessionHas('status', 'profile-updated');

        $this->assertNull($user->fresh()->avatar_private_file_id);
        $this->assertSoftDeleted('private_files', ['id' => $avatar->id]);
        Storage::disk('private')->assertMissing($avatar->path);
    }
}
