<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'sequence_no' => fake()->unique()->numberBetween(1, 20),
            'meeting_date' => '2026-07-01',
            'status' => Meeting::SCHEDULED,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => Meeting::OPEN]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => Meeting::CLOSED]);
    }
}
