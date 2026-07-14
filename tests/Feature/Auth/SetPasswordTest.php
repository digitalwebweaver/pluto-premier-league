<?php

namespace Tests\Feature\Auth;

use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_must_set_password_user_is_forced_to_set_password_screen(): void
    {
        $user = TeamUser::factory()->mustSetPassword()->create();

        // Any app route bounces to the set-password screen.
        $this->actingAs($user, 'team')->get('/team')
            ->assertRedirect(route('password.set'));
    }

    public function test_set_password_screen_itself_is_reachable(): void
    {
        $user = TeamUser::factory()->mustSetPassword()->create();

        $this->actingAs($user, 'team')->get('/set-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/SetPassword'));
    }

    public function test_user_can_set_password_and_is_released_to_dashboard(): void
    {
        $user = TeamUser::factory()->mustSetPassword()->create();

        $this->actingAs($user, 'team')->post('/set-password', [
            'password' => 'FreshPass123',
            'password_confirmation' => 'FreshPass123',
        ])->assertRedirect(route('team.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_set_password);
        $this->assertTrue(Hash::check('FreshPass123', $user->password));

        // No longer forced.
        $this->actingAs($user, 'team')->get('/team')->assertOk();
    }

    public function test_set_password_enforces_policy(): void
    {
        $user = TeamUser::factory()->mustSetPassword()->create();

        $this->actingAs($user, 'team')->post('/set-password', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_set_password);
    }
}
