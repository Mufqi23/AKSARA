<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_two_factor_authentication_from_profile(): void
    {
        $this->seed();
        $user = User::where('role', 'student')->firstOrFail();

        $this->actingAs($user)->post('/profile/two-factor')->assertRedirect();
        $this->assertTrue($user->fresh()->two_factor_enabled);

        $this->actingAs($user)->post('/profile/two-factor')->assertRedirect();
        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_login_with_two_factor_enabled_requires_otp_verification(): void
    {
        $this->seed();
        $user = User::where('role', 'student')->firstOrFail();
        $user->update(['two_factor_enabled' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'CampusDemo!2026',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->assertNotNull($user->fresh()->two_factor_code);
        $this->assertNotNull($user->fresh()->two_factor_expires_at);

        $this->get('/two-factor-challenge')->assertOk()->assertSee('Autentikasi');

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        $user->update(['two_factor_code' => Hash::make('123456')]);
        $this->post('/two-factor-challenge', ['code' => '123456'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->two_factor_code);
    }

    public function test_otp_code_is_delivered_by_email_notification(): void
    {
        Notification::fake();
        $this->seed();
        $user = User::where('role', 'student')->firstOrFail();
        $user->update(['two_factor_enabled' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'CampusDemo!2026'])
            ->assertRedirect('/two-factor-challenge');

        $sent = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function (TwoFactorCode $n) use (&$sent) {
            $sent = $n->code;

            return preg_match('/^\d{6}$/', $n->code) === 1;
        });

        $this->assertTrue(Hash::check($sent, $user->fresh()->two_factor_code));
        $this->post('/two-factor-challenge', ['code' => $sent])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_otp_can_be_resent_and_previous_code_is_invalidated(): void
    {
        Notification::fake();
        $this->seed();
        $user = User::where('role', 'student')->firstOrFail();
        $user->update(['two_factor_enabled' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'CampusDemo!2026']);
        $firstHash = $user->fresh()->two_factor_code;

        $this->post('/two-factor-challenge/resend')->assertRedirect()->assertSessionHas('success');

        Notification::assertSentToTimes($user, TwoFactorCode::class, 2);
        $this->assertNotSame($firstHash, $user->fresh()->two_factor_code);
        $this->assertGuest();
    }

    public function test_resend_and_challenge_require_pending_login(): void
    {
        $this->post('/two-factor-challenge/resend')->assertForbidden();
        $this->get('/two-factor-challenge')->assertForbidden();
    }

    public function test_expired_otp_code_is_rejected(): void
    {
        Notification::fake();
        $this->seed();
        $user = User::where('role', 'student')->firstOrFail();
        $user->update(['two_factor_enabled' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'CampusDemo!2026']);
        $user->update(['two_factor_code' => Hash::make('654321'), 'two_factor_expires_at' => now()->subMinute()]);

        $this->post('/two-factor-challenge', ['code' => '654321'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
