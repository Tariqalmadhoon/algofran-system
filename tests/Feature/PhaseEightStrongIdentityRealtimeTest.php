<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentProgressSnapshot;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\StudentAlertEngine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PhaseEightStrongIdentityRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['system.identity.two_factor_enabled' => true]);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_two_factor_can_be_temporarily_disabled_without_removing_existing_setup(): void
    {
        $user = User::factory()->create([
            'email' => 'paused-two-factor@example.com',
            'password' => Hash::make('PausedTwoFactor123!'),
        ]);
        $user->assignRole('center-manager');
        $this->completeTwoFactorAuthentication($user);
        $secret = $user->getRawOriginal('two_factor_secret');

        config(['system.identity.two_factor_enabled' => false]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'PausedTwoFactor123!',
        ])->assertRedirect(route('dashboard'));

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.security'))
            ->assertOk()
            ->assertDontSee('المصادقة الثنائية');

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('profile.two-factor.enable'))
            ->assertNotFound();

        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('data.two_factor_challenge', false);

        $this->assertSame($secret, $user->fresh()->getRawOriginal('two_factor_secret'));
    }

    public function test_sensitive_role_is_forced_to_configure_two_factor_authentication(): void
    {
        $manager = User::factory()->create([
            'email' => 'sensitive@example.com',
            'password' => Hash::make('SensitivePass123!'),
        ]);
        $manager->assignRole('center-manager');

        $this->post(route('login.store'), [
            'email' => $manager->email,
            'password' => 'SensitivePass123!',
        ])->assertRedirect(route('profile.security'));

        $this->assertAuthenticatedAs($manager);
        $this->get(route('dashboard'))->assertRedirect(route('profile.security'));
        $this->get(route('profile.security'))->assertOk()->assertSee('المصادقة الثنائية');
    }

    public function test_user_can_enable_confirm_and_protect_two_factor_secret_at_rest(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        Auth::guard('web')->login($manager);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('profile.two-factor.enable'))
            ->assertRedirect();

        $manager->refresh();
        $this->assertNotNull($manager->two_factor_secret);
        $this->assertNotSame(
            Fortify::currentEncrypter()->decrypt($manager->two_factor_secret),
            $manager->getRawOriginal('two_factor_secret'),
        );
        $this->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee('<svg', false);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('profile.two-factor.confirm'), ['code' => $this->currentCode($manager)])
            ->assertRedirect();

        $manager->refresh();
        $this->assertTrue($manager->hasEnabledTwoFactorAuthentication());
        $this->assertCount(8, $manager->recoveryCodes());
        $this->assertStringNotContainsString($manager->recoveryCodes()[0], $manager->getRawOriginal('two_factor_recovery_codes'));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => 'auth.two-factor.enabled']);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('profile.two-factor.disable'))
            ->assertSessionHasErrors('two_factor');
        $this->assertTrue($manager->refresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_web_login_requires_and_accepts_a_valid_totp_code(): void
    {
        $user = User::factory()->create([
            'email' => 'totp@example.com',
            'password' => Hash::make('TotpPassword123!'),
        ]);
        $this->completeTwoFactorAuthentication($user);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'TotpPassword123!',
            'remember' => true,
        ])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->post(route('two-factor.challenge.store'), ['code' => $this->currentCode($user)])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.two-factor.passed']);
    }

    public function test_mobile_login_uses_one_time_two_factor_challenge_and_stable_v1_contract(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-2fa@example.com',
            'password' => Hash::make('MobileTwoFactor123!'),
        ]);
        $this->completeTwoFactorAuthentication($user);
        $recoveryCode = $user->recoveryCodes()[0];

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'MobileTwoFactor123!',
            'device_name' => 'secure-phone',
        ])->assertStatus(202)
            ->assertHeader('X-API-Version', '1')
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonMissingPath('data.token');

        $challengeToken = $login->json('data.challenge_token');
        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'recovery_code' => $recoveryCode,
        ])->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user']]);

        $this->assertNotContains($recoveryCode, $user->refresh()->recoveryCodes());
        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => $this->currentCode($user),
        ])->assertUnprocessable()->assertJsonPath('error.code', 'invalid_two_factor_challenge');

        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertHeader('X-API-Version', '1')
            ->assertExactJson(['data' => [
                'api_version' => '1',
                'status' => 'stable',
                'minimum_supported_client' => '1.0.0',
                'authentication' => 'Bearer',
                'locale' => 'ar',
                'direction' => 'rtl',
                'two_factor_challenge' => true,
            ]]);

        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertHeader('X-API-Version', '1')
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_sensitive_api_identity_fails_closed_without_two_factor_setup(): void
    {
        $manager = User::factory()->create([
            'email' => 'api-manager@example.com',
            'password' => Hash::make('ApiManager123!'),
        ]);
        $manager->assignRole('center-manager');

        $this->postJson('/api/v1/auth/login', [
            'email' => $manager->email,
            'password' => 'ApiManager123!',
            'device_name' => 'manager-phone',
        ])->assertForbidden()->assertJsonPath('error.code', 'two_factor_setup_required');

        Sanctum::actingAs($manager, ['mobile:read']);
        $this->getJson('/api/v1/user')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'two_factor_setup_required');
    }

    public function test_security_center_revokes_other_sessions_and_all_api_tokens(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $user->tokens()->create(['name' => 'old-phone', 'token' => hash('sha256', 'old-phone-token'), 'abilities' => ['mobile:read']]);
        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Mozilla/5.0 Windows Chrome/120',
            'payload' => 'test',
            'last_activity' => now()->subMinute()->getTimestamp(),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('profile.sessions.destroy-others'))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.sessions.revoked']);
    }

    public function test_realtime_notifications_keep_database_fallback_and_private_channel_scope(): void
    {
        $user = User::factory()->create();
        $notification = new SystemNotification('عنوان', 'رسالة', '/notifications');

        config(['broadcasting.default' => 'log']);
        $this->assertSame(['database'], $notification->via($user));
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => 'realtime.example.test',
        ]);
        $this->assertSame(['database', 'broadcast'], $notification->via($user));
        $this->assertSame('system.notification', $notification->broadcastType());
        config(['broadcasting.default' => 'log']);

        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        $this->actingAs($manager);
        $channelAuthorizer = Broadcast::driver('log')->getChannels()->get('App.Models.User.{id}');
        $this->assertNotNull($channelAuthorizer);
        $this->assertTrue($channelAuthorizer($manager->fresh(), $manager->id));
        $this->assertFalse($channelAuthorizer($manager->fresh(), $user->id));
        Auth::guard('web')->logout();
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-App.Models.User.'.$manager->id])
            ->assertUnauthorized();
    }

    public function test_new_critical_alert_notifies_only_once_while_it_remains_open(): void
    {
        Notification::fake();
        $manager = User::factory()->create();
        $manager->assignRole('center-manager');
        [$student, $snapshot] = $this->studentWithCriticalSnapshot();
        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $student->currentHalaqa->center_id,
            'branch_id' => $student->currentHalaqa->branch_id,
            'employee_number' => 'STF-ALERT-MANAGER',
            'job_title' => 'مدير المركز',
            'active' => true,
        ]);

        app(StudentAlertEngine::class)->evaluate($student, $snapshot);
        app(StudentAlertEngine::class)->evaluate($student, $snapshot);

        Notification::assertSentToTimes($manager, SystemNotification::class, 1);
        Notification::assertSentTo($manager, SystemNotification::class, fn (SystemNotification $notification): bool => $notification->title === 'تنبيه طلابي حرج');
    }

    private function currentCode(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    /** @return array{Student,StudentProgressSnapshot} */
    private function studentWithCriticalSnapshot(): array
    {
        $center = Center::query()->create(['name' => 'مركز الهوية', 'code' => 'IDENTITY-CENTER']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'فرع الهوية', 'code' => 'IDENTITY-BRANCH']);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'name' => 'حلقة الهوية',
            'code' => 'IDENTITY-HALAQA',
            'capacity' => 20,
            'active' => true,
        ]);
        $student = Student::query()->create([
            'student_number' => 'IDENTITY-STUDENT',
            'first_name' => 'طالب',
            'father_name' => 'اختبار',
            'grandfather_name' => 'الهوية',
            'family_name' => 'القوية',
            'full_name' => 'طالب اختبار الهوية',
            'registration_date' => today(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
        ]);
        $snapshot = new StudentProgressSnapshot([
            'memorization_sessions' => 0,
            'performance_trend' => -20,
            'metrics' => ['evaluation_count' => 6],
        ]);

        return [$student, $snapshot];
    }
}
