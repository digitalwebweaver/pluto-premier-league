<?php

namespace Tests\Feature\Auth;

use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_user_cannot_access_lt_routes_gets_403(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/queue')
            ->assertForbidden(); // 403, per BR-AUTH-001
    }

    public function test_lt_user_cannot_access_team_routes_gets_403(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/team/submit')
            ->assertForbidden();
    }

    public function test_team_user_can_access_own_area(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/team')
            ->assertOk();
    }

    public function test_lt_user_can_access_own_area(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_not_403(): void
    {
        $this->get('/team')->assertRedirect(route('login'));
        $this->get('/lt')->assertRedirect(route('login'));
    }

    public function test_shared_league_route_allows_either_guard(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/league')->assertOk();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/league')->assertOk();
    }

    public function test_shared_league_route_redirects_guest(): void
    {
        $this->get('/league')->assertRedirect(route('login'));
    }
}
