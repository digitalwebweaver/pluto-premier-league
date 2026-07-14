<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds LVB Pluto's real teams. LT can add/edit/deactivate teams in-app.
 */
class TeamSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('teams')) {
            $this->command?->warn('TeamSeeder skipped — `teams` table not created yet.');

            return;
        }

        $teams = [
            ['name' => 'Digital Titans', 'short_code' => 'DT', 'crest_color' => '#1B2F52', 'logo_path' => '/images/teams/digital-titans.jpeg'],
            ['name' => 'XtraVision', 'short_code' => 'XV', 'crest_color' => '#3F8F6B', 'logo_path' => '/images/teams/xtravision.jpeg'],
            ['name' => 'Madhuvan Stallion', 'short_code' => 'MS', 'crest_color' => '#B5473A', 'logo_path' => '/images/teams/madhuvan-stallion.jpeg'],
            ['name' => 'Jupiter Wealth', 'short_code' => 'JW', 'crest_color' => '#9A6F1E', 'logo_path' => '/images/teams/jupiter-wealth.jpeg'],
        ];

        foreach ($teams as $t) {
            Team::updateOrCreate(['name' => $t['name']], $t + ['is_active' => true]);
        }
    }
}
