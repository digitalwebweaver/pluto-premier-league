<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\Season;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the active season (Phase 2C) + its fortnightly meetings. All meetings
 * start OPEN so any team can submit for any meeting while the LT backfills
 * real dates and original scores (owner request, 2026-07-13) — the LT edits
 * dates/status per meeting in-app (Meetings screen) afterward.
 */
class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('seasons')) {
            $this->command?->warn('SeasonSeeder skipped — `seasons` table not created yet.');

            return;
        }

        $season = Season::updateOrCreate(
            ['name' => 'Season 4 · 2026'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'is_active' => true, 'is_complete' => false]
        );

        $meetings = [
            ['sequence_no' => 1, 'meeting_date' => '2026-07-01', 'status' => Meeting::OPEN],
            ['sequence_no' => 2, 'meeting_date' => '2026-07-15', 'status' => Meeting::OPEN],
            ['sequence_no' => 3, 'meeting_date' => '2026-07-29', 'status' => Meeting::OPEN],
            ['sequence_no' => 4, 'meeting_date' => '2026-08-12', 'status' => Meeting::OPEN],
            ['sequence_no' => 5, 'meeting_date' => '2026-08-26', 'status' => Meeting::OPEN],
            ['sequence_no' => 6, 'meeting_date' => '2026-09-09', 'status' => Meeting::OPEN],
            ['sequence_no' => 7, 'meeting_date' => '2026-09-23', 'status' => Meeting::OPEN],
        ];

        foreach ($meetings as $m) {
            $season->meetings()->updateOrCreate(['sequence_no' => $m['sequence_no']], $m);
        }
    }
}
