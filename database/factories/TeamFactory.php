<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();
        $palette = ['#12213D', '#1B2F52', '#3F8F6B', '#B5473A', '#9A6F1E', '#5A6684', '#3F6F8F', '#7A5C3E'];

        return [
            'name' => $name,
            'short_code' => Team::deriveShortCode($name),
            'crest_color' => fake()->randomElement($palette),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
