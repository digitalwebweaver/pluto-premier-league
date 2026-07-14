<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ScoringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScoringRule>
 */
class ScoringRuleFactory extends Factory
{
    protected $model = ScoringRule::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'subtype_label' => fake()->words(2, true),
            'points' => fake()->numberBetween(0, 300),
            'extra_params' => null,
            'is_active' => true,
            'display_order' => 1,
        ];
    }
}
