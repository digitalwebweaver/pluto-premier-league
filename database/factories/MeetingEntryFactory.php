<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingEntry>
 */
class MeetingEntryFactory extends Factory
{
    protected $model = MeetingEntry::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'meeting_id' => Meeting::factory(),
            'status' => MeetingEntry::DRAFT,
            'computed_total' => 0,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => MeetingEntry::SUBMITTED, 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => MeetingEntry::APPROVED, 'approved_at' => now()]);
    }
}
