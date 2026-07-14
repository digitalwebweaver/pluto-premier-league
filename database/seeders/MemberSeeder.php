<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds real team rosters as the owner supplies them. Captains can also add/
 * edit their own members in-app (Team → Roster — FR-MBR-001..003). Runs after
 * TeamSeeder so members link to a team.
 */
class MemberSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('members')) {
            $this->command?->warn('MemberSeeder skipped — `members` table not created yet.');

            return;
        }

        // team name => [member name, ...]
        $rosters = [
            'Digital Titans' => [
                'Kamlesh Nishad',
                'Meghna Shah',
                'Harun Kazi',
                'Ekta Parikh',
                'Suhani Nayak',
                'Bhavik Patel',
            ],
        ];

        $palette = [
            '#1B2F52', '#3F6F8F', '#3F8F6B', '#7A5C3E', '#4B5A78', '#5E7B6B',
            '#8A6D4B', '#556074', '#6B5340', '#9A6F1E',
        ];

        foreach ($rosters as $teamName => $names) {
            $team = Team::where('name', $teamName)->first();
            if (! $team) {
                continue;
            }

            foreach ($names as $i => $name) {
                Member::updateOrCreate(
                    ['team_id' => $team->id, 'name' => $name],
                    ['avatar_color' => $palette[$i % count($palette)], 'is_active' => true]
                );
            }
        }
    }
}
