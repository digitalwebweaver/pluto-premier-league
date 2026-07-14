<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ScoringRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the default scoring rules (Phase 2E) from requirements/05. Point values
 * live ONLY here / in-app (BR-SCO-001) — LT edits them afterward.
 *
 * NOTE: the source spreadsheet's stray `×76` on Specific Ask is a spreadsheet
 * error and is NOT reproduced (BR-SCO-002) — Specific Ask is a clean count×200.
 * TYFCB (amount ÷ 10000 × 100), Golden Mic (200) and Abiding Theme (200) are
 * confirmed against the Digital Titans workbook. Team/Joint Meeting (300, up
 * from 100) and the V2V Chapter Director/ED tier (300) are confirmed against
 * the Commissioner PPL WhatsApp broadcast (2026-07-13).
 */
class ScoringRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('scoring_rules') || ! Schema::hasTable('categories')) {
            $this->command?->warn('ScoringRuleSeeder skipped — scoring_rules/categories not ready.');

            return;
        }

        // code => [ [subtype_label, points, extra_params|null], ... ]
        $rules = [
            'VIS' => [['Hot', 300], ['Open', 200], ['Closed', 50], ['Repeat', 0]],
            'IND' => [['Inducted', 500]],
            'REF' => [['Same team', 50], ['Cross team / chapter', 100], ['Cross region / commissioner', 150]],
            'V2V' => [['Same team', 50], ['Cross team / chapter / commissioner', 150], ['Cross region', 200], ['Chapter Director / ED', 300]],
            'ASK' => [['Completed', 200]], // clean ×200 — stray ×76 NOT reproduced (BR-SCO-002)
            'TRN' => [['Per member present', 50, ['multiplier' => 2]]], // conditional_multiplier
            'ATT' => [['Attendance', 0, ['flat' => 300, 'penalty' => -200, 'metric' => 'present']]],
            'PUN' => [['Punctuality', 0, ['flat' => 100, 'penalty' => -20, 'metric' => 'on_time']]],
            'PIN' => [['Wearing', 100]],
            'ACH' => [['Earned', 100]],
            // TYFCB scales with the ₹ amount: Σ(amount) ÷ 10000 × 100 (1 pt per ₹100),
            // per the Digital Titans workbook. Rate lives in extra_params (BR-SCO-001).
            'TYF' => [['TYFCB amount', 0, ['per_amount' => 10000, 'points_per' => 100]]],
            'JP' => [['Per joint presentation', 100]],
            'SOC' => [['Per member present', 100]],
            'GLD' => [['Per member awarded', 200]],
            // 300/qualifying meeting per Commissioner PPL broadcast (2026-07-13),
            // up from 100. Qualifying = min. 3 members from each team present —
            // not enforced in software (no roster-count field for this category
            // yet); captains self-certify, shown as a reminder on the entry form.
            'TJM' => [['Per qualifying meeting', 300]],
            'THM' => [['Whole team abiding', 200]], // ⚠️ confirm Abiding Theme flat value with LT
            'TST' => [['Per testimonial', 50]],
            'ATR' => [['Per member in attire', 50]],
        ];

        foreach ($rules as $code => $subtypes) {
            $category = Category::where('code', $code)->first();
            if (! $category) {
                continue;
            }

            foreach ($subtypes as $order => $sub) {
                [$label, $points] = $sub;
                $extra = $sub[2] ?? null;

                ScoringRule::updateOrCreate(
                    ['category_id' => $category->id, 'subtype_label' => $label],
                    [
                        'points' => $points,
                        'extra_params' => $extra,
                        'is_active' => true,
                        'display_order' => $order + 1,
                    ]
                );
            }
        }
    }
}
