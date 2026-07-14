<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Meeting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the 18 scoring categories (Phase 2D) from requirements/05. Point values
 * are NOT here — they're in scoring_rules (ScoringRuleSeeder, Phase 2E). Also
 * attaches the full active set to every existing meeting by default (BR-MTG-002).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('categories')) {
            $this->command?->warn('CategorySeeder skipped — `categories` table not created yet.');

            return;
        }

        $categories = [
            ['name' => 'Visitors', 'code' => 'VIS', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Inductions', 'code' => 'IND', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Referrals', 'code' => 'REF', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'V2V', 'code' => 'V2V', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Specific Ask Completed', 'code' => 'ASK', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Trainings', 'code' => 'TRN', 'input_shape' => Category::CONDITIONAL_MULTIPLIER],
            ['name' => 'Attendance', 'code' => 'ATT', 'input_shape' => Category::ROSTER_FLAT_PENALTY],
            ['name' => 'Punctuality', 'code' => 'PUN', 'input_shape' => Category::ROSTER_FLAT_PENALTY],
            ['name' => 'Wearing Badge/Pin', 'code' => 'PIN', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => "Getting Achiever's Pin", 'code' => 'ACH', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Thank You Notes (TYFCB)', 'code' => 'TYF', 'input_shape' => Category::AMOUNT_SUBTYPE],
            ['name' => 'Joint Presentations', 'code' => 'JP', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => "Social/Member's Place Visibility", 'code' => 'SOC', 'input_shape' => Category::COUNT_SUBTYPE],
            // count_subtype (not binary_flat) — more than one member can win Golden Mic
            // in the same meeting (owner correction, 2026-07-13); each is a row, summed.
            ['name' => 'Golden Mic', 'code' => 'GLD', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Team/Joint Meeting', 'code' => 'TJM', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Abiding Theme', 'code' => 'THM', 'input_shape' => Category::BINARY_FLAT],
            ['name' => 'Testimonials', 'code' => 'TST', 'input_shape' => Category::COUNT_SUBTYPE],
            ['name' => 'Attire', 'code' => 'ATR', 'input_shape' => Category::COUNT_SUBTYPE],
        ];

        foreach ($categories as $i => $c) {
            Category::updateOrCreate(
                ['code' => $c['code']],
                $c + ['display_order' => $i + 1, 'is_active' => true]
            );
        }

        // Default a new/seeded meeting's applicable set to the full active list.
        // Exception: meeting 1 (the chapter kick-off) scores a reduced set — the
        // exact set on the Digital Titans workbook's first tab ("1-7-26"): Visitors,
        // Inductions, Attendance, Punctuality, Wearing Pin, Getting Achiever's Pin,
        // Golden Mic, Abiding Theme, Attire. Referrals/V2V/TYFCB/Trainings/Specific
        // Ask/Joint Presentations/Social Visibility/Team-Joint Meeting/Testimonials
        // are NOT scored at meeting 1 — owner confirmed 2026-07-13 (the Commissioner
        // PPL broadcast's "Referrals/V2V/Business Given at meeting 1" does not match
        // actual practice; the workbook is authoritative).
        if (Schema::hasTable('meeting_categories')) {
            $activeIds = Category::active()->pluck('id');
            $meeting1Ids = Category::whereIn('code', ['VIS', 'IND', 'ATT', 'PUN', 'PIN', 'ACH', 'GLD', 'THM', 'ATR'])
                ->pluck('id');

            Meeting::all()->each(function (Meeting $meeting) use ($activeIds, $meeting1Ids) {
                if ($meeting->categories()->count() === 0) {
                    $meeting->categories()->sync($meeting->sequence_no === 1 ? $meeting1Ids : $activeIds);
                }
            });
        }
    }
}
