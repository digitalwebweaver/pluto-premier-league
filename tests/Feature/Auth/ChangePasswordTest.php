<?php

namespace Tests\Feature\Auth;

use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_page_renders(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/settings/password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Password'));
    }

    public function test_password_updates_with_correct_current_password(): void
    {
        $user = TeamUser::factory()->create(['password' => 'OldPass123']);

        $this->actingAs($user, 'team')->put('/settings/password', [
            'current_password' => 'OldPass123',
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('BrandNew123', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = TeamUser::factory()->create(['password' => 'OldPass123']);

        $this->actingAs($user, 'team')->put('/settings/password', [
            'current_password' => 'WrongOldPass',
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldPass123', $user->fresh()->password));
    }

    public function test_change_password_enforces_policy(): void
    {
        $user = TeamUser::factory()->create(['password' => 'OldPass123']);

        $this->actingAs($user, 'team')->put('/settings/password', [
            'current_password' => 'OldPass123',
            'password' => 'nodigitshere',
            'password_confirmation' => 'nodigitshere',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_requires_auth(): void
    {
        $this->put('/settings/password', [])->assertRedirect(route('login'));
    }
}
