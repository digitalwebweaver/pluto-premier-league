<?php

namespace Tests\Feature\Auth;

use App\Models\TeamUser;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_reset_link_is_sent_for_an_existing_team_user(): void
    {
        Notification::fake();
        $user = TeamUser::factory()->create(['email' => 'cap@example.com']);

        $this->post('/forgot-password', ['guard' => 'team', 'email' => 'cap@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_still_gets_generic_status_and_no_mail(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['guard' => 'team', 'email' => 'nobody@example.com'])
            ->assertSessionHas('status'); // generic — no enumeration

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'must_set_password' => true,
        ]);
        $token = Password::broker('team_users')->createToken($user);

        $this->post('/reset-password', [
            'guard' => 'team',
            'token' => $token,
            'email' => 'cap@example.com',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123', $user->password));
        $this->assertFalse($user->must_set_password); // reset also clears the flag
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        TeamUser::factory()->create(['email' => 'cap@example.com']);

        $this->post('/reset-password', [
            'guard' => 'team',
            'token' => 'not-a-real-token',
            'email' => 'cap@example.com',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_enforces_password_policy(): void
    {
        $user = TeamUser::factory()->create(['email' => 'cap@example.com']);
        $token = Password::broker('team_users')->createToken($user);

        // No number → fails BR-AUTH-002.
        $this->post('/reset-password', [
            'guard' => 'team',
            'token' => $token,
            'email' => 'cap@example.com',
            'password' => 'onlyletters',
            'password_confirmation' => 'onlyletters',
        ])->assertSessionHasErrors('password');
    }
}
