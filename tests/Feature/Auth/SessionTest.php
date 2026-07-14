<?php

namespace Tests\Feature\Auth;

use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_bounced_from_protected_page_keeps_intended_url(): void
    {
        // Simulates an expired session: a guest hits a deep protected page.
        $this->get('/team/roster')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status'); // "Please sign in to continue."

        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        // After re-authenticating, they land back on the page they wanted.
        $this->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ])->assertRedirect('/team/roster');
    }

    public function test_login_without_intended_goes_to_dashboard(): void
    {
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $this->post('/login', [
            'guard' => 'team',
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ])->assertRedirect(route('team.dashboard'));
    }
}
