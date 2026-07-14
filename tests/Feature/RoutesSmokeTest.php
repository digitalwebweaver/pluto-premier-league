<?php

namespace Tests\Feature;

use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_welcome(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
    }

    public function test_team_dashboard_renders_for_captain(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/team')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Team/Dashboard'));
    }

    public function test_lt_overview_renders_for_lt(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('LT/Overview'));
    }

    public function test_shared_season_route_renders(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/season')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Season'));
    }

    public function test_unknown_route_is_404(): void
    {
        $this->get('/definitely-not-a-route')->assertNotFound();
    }
}
