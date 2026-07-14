<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamUser>
 */
class TeamUserFactory extends Factory
{
    protected $model = \App\Models\TeamUser::class;

    public function definition(): array
    {
        return [
            'team_id' => null, // set once teams exist (Phase 2A)
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'must_set_password' => false,
            'notification_pref' => 'email',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /** Account issued but not yet activated — forced through set-password. */
    public function mustSetPassword(): static
    {
        return $this->state(fn () => ['must_set_password' => true]);
    }
}
