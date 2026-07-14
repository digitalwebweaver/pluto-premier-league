<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Domain seeders are skeletons (Phase 0D) — each is a guarded no-op until
     * its table exists, so `migrate --seed` runs clean today and fills in as
     * later phases add tables. Auth accounts are seeded in Phase 1.
     */
    public function run(): void
    {
        $this->call([
            SeasonSeeder::class,   // season + meetings
            TeamSeeder::class,     // the 4 real teams (must run before AuthSeeder)
            AuthSeeder::class,     // LT + one captain login per team
            MemberSeeder::class,   // real rosters as the owner supplies them
            CategorySeeder::class, // 18 scoring categories + meeting applicability
            ScoringRuleSeeder::class, // default scoring rules (LT adjusts point values in-app)
            // StandingsSeeder is demo-only fake history — omitted for real data.
        ]);
    }
}
