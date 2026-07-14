<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $palette = ['#12213D', '#1B2F52', '#3F8F6B', '#B5473A', '#9A6F1E', '#5A6684', '#3F6F8F', '#7A5C3E'];

        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
            'business_category' => fake()->randomElement([
                'Financial Advisor', 'Interior Design', 'Legal Services',
                'Digital Marketing', 'Real Estate', 'Chartered Accountant',
            ]),
            'avatar_color' => fake()->randomElement($palette),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
