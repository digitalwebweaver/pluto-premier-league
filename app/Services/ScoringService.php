<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ScoringRule;
use Illuminate\Support\Collection;

/**
 * The scoring engine (Phase 2E / requirements 05, plan 05).
 *
 * NO point value is hardcoded here (BR-SCO-001) — every number comes from the
 * passed `scoring_rules` (points + extra_params). The methods are pure w.r.t.
 * their inputs (no DB writes), so they unit-test without HTTP. Controllers call
 * `computeCategory()` on save/submit/approve; approved entries snapshot the
 * result so later rule edits never rewrite history (BR-SCO-003).
 */
class ScoringService
{
    /**
     * count_subtype / amount_subtype: Σ (count × rule.points).
     *
     * @param  array<int, array{count?: int, points?: int}>  $lines
     */
    public function countSubtype(array $lines): int
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += (int) ($line['count'] ?? 0) * (int) ($line['points'] ?? 0);
        }

        return $total;
    }

    /**
     * roster_flat_penalty: flat award when there are no offenders, otherwise
     * offenders × penalty (penalty is negative). Attendance: absent count,
     * flat 300 / −200. Punctuality: late count, flat 100 / −20.
     */
    public function rosterFlatPenalty(int $offenders, int $flat, int $penalty): int
    {
        return $offenders === 0 ? $flat : $offenders * $penalty;
    }

    /** binary_flat: awards the flat points when toggled on, else 0. */
    public function binaryFlat(bool $on, int $points): int
    {
        return $on ? $points : 0;
    }

    /**
     * amount_subtype (TYFCB): points scale with the total ₹ amount, not a count.
     * Source workbook: points = Σ(amount) ÷ per_amount × points_per — e.g.
     * 10000 → 100, i.e. 1 point per ₹100. `per_amount` / `points_per` come from
     * the rule's extra_params so nothing is hardcoded (BR-SCO-001). The sum is
     * taken first, then divided once (matching the sheet), then rounded to int.
     */
    public function amountSubtype(float $totalAmount, float $perAmount, int $pointsPer): int
    {
        if ($perAmount <= 0) {
            return 0;
        }

        return (int) round($totalAmount / $perAmount * $pointsPer);
    }

    /**
     * conditional_multiplier (Trainings): per-member points double when the
     * whole team is present; total = members_present × per_member.
     */
    public function conditionalMultiplier(int $membersPresent, bool $wholeTeam, int $base, float $multiplier = 2.0): int
    {
        $perMember = $wholeTeam ? (int) round($base * $multiplier) : $base;

        return $membersPresent * $perMember;
    }

    /**
     * Dispatch by a category's input shape. Returns computed points.
     *
     * @param  Collection<int, ScoringRule>  $rules  the category's active rules
     * @param  array<string, mixed>  $input  shape-specific payload:
     *   count_subtype/amount_subtype: ['lines' => [['scoring_rule_id'=>id, 'count'=>n], ...]]
     *   roster_flat_penalty:          ['offenders' => int]
     *   binary_flat:                  ['on' => bool]
     *   conditional_multiplier:       ['members_present' => int, 'whole_team' => bool]
     */
    public function computeCategory(Category $category, Collection $rules, array $input): int
    {
        return match ($category->input_shape) {
            Category::COUNT_SUBTYPE => $this->computeCountLike($rules, $input),
            Category::AMOUNT_SUBTYPE => $this->computeAmount($rules, $input),
            Category::ROSTER_FLAT_PENALTY => $this->computeRoster($rules, $input),
            Category::BINARY_FLAT => $this->binaryFlat(
                (bool) ($input['on'] ?? false),
                (int) ($rules->first()->points ?? 0)
            ),
            Category::CONDITIONAL_MULTIPLIER => $this->computeMultiplier($rules, $input),
            default => 0,
        };
    }

    /**
     * @param  Collection<int, ScoringRule>  $rules
     * @param  array<string, mixed>  $input
     */
    private function computeCountLike(Collection $rules, array $input): int
    {
        $byId = $rules->keyBy('id');
        $lines = [];

        foreach ($input['lines'] ?? [] as $line) {
            $rule = $byId->get($line['scoring_rule_id'] ?? null);
            $lines[] = [
                'points' => (int) ($rule->points ?? 0),
                'count' => (int) ($line['count'] ?? 0),
            ];
        }

        return $this->countSubtype($lines);
    }

    /**
     * amount_subtype (TYFCB): sum the line amounts, then apply the rule's rate.
     *
     * @param  Collection<int, ScoringRule>  $rules
     * @param  array<string, mixed>  $input  ['lines' => [['amount' => float], ...]]
     */
    private function computeAmount(Collection $rules, array $input): int
    {
        $rule = $rules->first();
        $total = 0.0;

        foreach ($input['lines'] ?? [] as $line) {
            $total += (float) ($line['amount'] ?? 0);
        }

        return $this->amountSubtype(
            $total,
            (float) ($rule?->param('per_amount', 10000) ?? 10000),
            (int) ($rule?->param('points_per', 100) ?? 100),
        );
    }

    /**
     * @param  Collection<int, ScoringRule>  $rules
     * @param  array<string, mixed>  $input
     */
    private function computeRoster(Collection $rules, array $input): int
    {
        $rule = $rules->first();

        return $this->rosterFlatPenalty(
            (int) ($input['offenders'] ?? 0),
            (int) ($rule?->param('flat', 0) ?? 0),
            (int) ($rule?->param('penalty', 0) ?? 0),
        );
    }

    /**
     * @param  Collection<int, ScoringRule>  $rules
     * @param  array<string, mixed>  $input
     */
    private function computeMultiplier(Collection $rules, array $input): int
    {
        $rule = $rules->first();

        return $this->conditionalMultiplier(
            (int) ($input['members_present'] ?? 0),
            (bool) ($input['whole_team'] ?? false),
            (int) ($rule?->points ?? 0),
            (float) ($rule?->param('multiplier', 2) ?? 2),
        );
    }
}
