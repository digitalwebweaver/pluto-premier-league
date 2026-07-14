<?php

namespace Tests\Feature\Auth;

use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_renders(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/account')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Profile'));
    }

    public function test_profile_updates_name_and_notification_pref(): void
    {
        $user = TeamUser::factory()->create([
            'name' => 'Old Name',
            'notification_pref' => 'email',
        ]);

        $this->actingAs($user, 'team')->patch('/account', [
            'name' => 'New Name',
            'email' => $user->email,
            'notification_pref' => 'none',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('none', $user->notification_pref);
    }

    public function test_email_must_be_unique_within_the_guard(): void
    {
        TeamUser::factory()->create(['email' => 'taken@example.com']);
        $user = TeamUser::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user, 'team')->patch('/account', [
            'name' => $user->name,
            'email' => 'taken@example.com',
            'notification_pref' => 'email',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_keep_their_own_email(): void
    {
        $user = TeamUser::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user, 'team')->patch('/account', [
            'name' => 'Renamed',
            'email' => 'mine@example.com',
            'notification_pref' => 'email',
        ])->assertSessionHasNoErrors();
    }

    public function test_account_requires_auth(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
    }
}
