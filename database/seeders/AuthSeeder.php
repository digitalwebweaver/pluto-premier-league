<?php

namespace Database\Seeders;

use App\Models\LtUser;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the Leadership Team login + one captain login per team, with known
 * credentials (password: Password123). LT can reset/reissue any of these from
 * the in-app Logins screen. Runs after TeamSeeder so captains link to a team.
 */
class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Leadership Team.
        LtUser::updateOrCreate(
            ['email' => 'leadership@pluto.local'],
            [
                'name' => 'Leadership Admin',
                'password' => Hash::make('Password123'),
                'must_set_password' => false,
                'notification_pref' => 'email',
                'is_active' => true,
            ]
        );

        // One captain per team (email = short-code@pluto.local).
        $captains = [
            'Digital Titans' => ['dt@pluto.local', 'Digital Titans Captain'],
            'XtraVision' => ['xv@pluto.local', 'XtraVision Captain'],
            'Madhuvan Stallion' => ['ms@pluto.local', 'Madhuvan Stallion Captain'],
            'Jupiter Wealth' => ['jw@pluto.local', 'Jupiter Wealth Captain'],
        ];

        foreach ($captains as $teamName => [$email, $name]) {
            $team = Team::where('name', $teamName)->first();

            TeamUser::updateOrCreate(
                ['email' => $email],
                [
                    'team_id' => $team?->id,
                    'name' => $name,
                    'password' => Hash::make('Password123'),
                    'must_set_password' => false,
                    'notification_pref' => 'email',
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Seeded LT (leadership@pluto.local) + 4 team captains (dt/xv/ms/jw @pluto.local) — password: Password123');
    }
}
