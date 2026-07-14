<?php

namespace Tests\Feature\Auth;

use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_team_captain_logs_in_and_lands_on_team_dashboard(): void
    {
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('team.dashboard'));
        $this->assertAuthenticated('team');
        $this->assertGuest('lt');
    }

    public function test_lt_member_logs_in_and_lands_on_lt_overview(): void
    {
        LtUser::factory()->create([
            'email' => 'lead@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->post('/login', [
            'guard' => 'lt',
            'email' => 'lead@example.com',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('lt.overview'));
        $this->assertAuthenticated('lt');
        $this->assertGuest('team');
    }

    public function test_wrong_password_shows_generic_error_and_stays_guest(): void
    {
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $this->from('/login')->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest('team');
    }

    public function test_unknown_email_and_wrong_password_fail_identically(): void
    {
        // No user with this email — must NOT reveal that (non-enumeration).
        $this->from('/login')->post('/login', [
            'guard' => 'team',
            'email' => 'nobody@example.com',
            'password' => 'whatever123',
        ])->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_credentials_do_not_work_across_guards(): void
    {
        // A team user's credentials must not authenticate on the lt guard.
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $this->post('/login', [
            'guard' => 'lt',
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('lt');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        foreach (range(1, 5) as $i) {
            $this->post('/login', [
                'guard' => 'team',
                'email' => 'cap@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        // The 6th failure is a throttle message, not the generic "failed" one.
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Too many login attempts', $errors[0]);

        RateLimiter::clear('cap@example.com|team|127.0.0.1');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $captain = TeamUser::factory()->create();

        $this->actingAs($captain, 'team')
            ->get('/login')
            ->assertRedirect(route('team.dashboard'));
    }

    public function test_logout_invalidates_session(): void
    {
        $captain = TeamUser::factory()->create();

        $this->actingAs($captain, 'team')
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest('team');
    }
}
