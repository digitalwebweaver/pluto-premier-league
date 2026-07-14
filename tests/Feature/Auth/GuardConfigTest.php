<?php

namespace Tests\Feature\Auth;

use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuardConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_and_lt_guards_map_to_their_models(): void
    {
        $this->assertInstanceOf(
            TeamUser::class,
            Auth::guard('team')->getProvider()->createModel()
        );
        $this->assertInstanceOf(
            LtUser::class,
            Auth::guard('lt')->getProvider()->createModel()
        );
    }

    public function test_password_brokers_exist_for_both_guards(): void
    {
        $this->assertNotNull(config('auth.passwords.team_users'));
        $this->assertNotNull(config('auth.passwords.lt_users'));
    }

    public function test_models_hash_password_and_cast_flags(): void
    {
        $captain = TeamUser::factory()->create(['password' => 'Password123']);

        // 'hashed' cast means the stored value is not the plaintext.
        $this->assertNotSame('Password123', $captain->password);
        $this->assertTrue(Hash::check('Password123', $captain->password));
        $this->assertIsBool($captain->must_set_password);
    }

    public function test_team_guard_authenticates_a_team_user(): void
    {
        TeamUser::factory()->create([
            'email' => 'cap@example.com',
            'password' => 'Password123',
        ]);

        $this->assertTrue(
            Auth::guard('team')->attempt(['email' => 'cap@example.com', 'password' => 'Password123'])
        );
        // The same credentials must NOT authenticate against the lt guard.
        $this->assertFalse(
            Auth::guard('lt')->attempt(['email' => 'cap@example.com', 'password' => 'Password123'])
        );
    }

    public function test_must_set_password_state_helper(): void
    {
        $lt = LtUser::factory()->mustSetPassword()->create();
        $this->assertTrue($lt->must_set_password);
    }
}
