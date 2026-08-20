<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

        Notification::assertSentTo($user, ResetPassword::class);
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
}
