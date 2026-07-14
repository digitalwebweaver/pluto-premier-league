<?php

namespace Tests\Feature\LT;

use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginManagementTest extends TestCase
{
    use RefreshDatabase;

    private function lt(): LtUser
    {
        return LtUser::factory()->create();
    }

    public function test_page_renders_for_lt(): void
    {
        $this->actingAs($this->lt(), 'lt')
            ->get('/lt/logins')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LT/Logins'));
    }

    public function test_captains_cannot_access_login_management(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/logins')
            ->assertForbidden();
    }

    public function test_lt_issues_a_captain_login_that_must_set_password(): void
    {
        $this->actingAs($this->lt(), 'lt')
            ->post('/lt/logins/captains', [
                'name' => 'New Captain',
                'email' => 'newcap@example.com',
            ])
            ->assertSessionHas('issued'); // one-time credential returned to LT

        $captain = TeamUser::where('email', 'newcap@example.com')->first();
        $this->assertNotNull($captain);
        $this->assertTrue($captain->must_set_password);
    }

    public function test_issued_captain_can_use_temp_password_then_is_forced_to_set(): void
    {
        // Issue the login and capture the temp password from the flash payload.
        $response = $this->actingAs($this->lt(), 'lt')->post('/lt/logins/captains', [
            'name' => 'New Captain',
            'email' => 'newcap@example.com',
        ]);
        $temp = session('issued')['password'];

        // Fresh guest session: the captain signs in with the temp password...
        $this->post('/logout');
        $this->post('/login', [
            'guard' => 'team',
            'email' => 'newcap@example.com',
            'password' => $temp,
        ])->assertRedirect(); // authenticated

        $this->assertAuthenticated('team');

        // ...and is forced to the set-password screen on the next request.
        $this->get('/team')->assertRedirect(route('password.set'));
    }

    public function test_lt_resets_a_captain_password(): void
    {
        $captain = TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'must_set_password' => false,
        ]);

        $this->actingAs($this->lt(), 'lt')
            ->post("/lt/logins/captains/{$captain->id}/reset")
            ->assertSessionHas('issued');

        $captain->refresh();
        $this->assertTrue($captain->must_set_password);

        // The new temp password authenticates.
        $temp = session('issued')['password'];
        $this->post('/logout');
        $this->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => $temp,
        ]);
        $this->assertAuthenticated('team');
    }

    public function test_issuing_captain_requires_unique_email(): void
    {
        TeamUser::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->lt(), 'lt')
            ->post('/lt/logins/captains', [
                'name' => 'Dupe',
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_lt_issues_another_lt_login(): void
    {
        $this->actingAs($this->lt(), 'lt')
            ->post('/lt/logins/lt', [
                'name' => 'New Leader',
                'email' => 'newlt@example.com',
            ])
            ->assertSessionHas('issued');

        $this->assertTrue(
            LtUser::where('email', 'newlt@example.com')->first()->must_set_password
        );
    }
}
