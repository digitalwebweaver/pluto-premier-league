<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

/**
 * Backfills teams.logo_path for known chapter teams whose row predates the
 * logo feature (e.g. created by an earlier seed/deploy before logo_path
 * existed). Deliberately narrow and non-destructive: only ever sets
 * logo_path, and only when it is currently NULL — never touches name,
 * crest_color, short_code, or an already-set (possibly custom) logo, so it
 * is safe to run on every deploy (docker/start.sh) without clobbering
 * in-app customization.
 */
class SyncTeamLogos extends Command
{
    protected $signature = 'teams:sync-logos';

    protected $description = 'Backfill logo_path for known teams where it is currently null';

    public function handle(): int
    {
        $logos = [
            'Digital Titans' => '/images/teams/digital-titans.jpeg',
            'XtraVision' => '/images/teams/xtravision.jpeg',
            'Madhuvan Stallion' => '/images/teams/madhuvan-stallion.jpeg',
            'Jupiter Wealth' => '/images/teams/jupiter-wealth.jpeg',
        ];

        foreach ($logos as $name => $path) {
            $updated = Team::where('name', $name)->whereNull('logo_path')->update(['logo_path' => $path]);
            if ($updated) {
                $this->info("Set logo for {$name}.");
            }
        }

        return self::SUCCESS;
    }
}
