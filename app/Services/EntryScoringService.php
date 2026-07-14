<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MeetingEntry;
use Illuminate\Support\Collection;

/**
 * Authoritative server-side recompute for a whole meeting entry (FR-SCO-010/011,
 * BR-ENT-001). Reads the persisted lines/attendance, delegates the per-category
 * math to {@see ScoringService}, writes each line's `computed_points`, and
 * returns the meeting total. The client running total is never trusted.
 *
 * Covers all five input shapes.
 */
class EntryScoringService
{
    public function __construct(private ScoringService $scoring) {}

    /** Recompute + persist per-line points and the entry total. Returns the total. */
    public function recompute(MeetingEntry $entry): int
    {
        return $this->computeAll($entry)['total'];
    }

    /**
     * Recompute and return a per-category breakdown for the review screen.
     *
     * @return array{total: int, categories: array<int, array<string, mixed>>}
     */
    public function breakdown(MeetingEntry $entry): array
    {
        return $this->computeAll($entry);
    }

    /**
     * @return array{total: int, categories: array<int, array<string, mixed>>}
     */
    private function computeAll(MeetingEntry $entry): array
    {
        $entry->loadMissing(['lines', 'attendance', 'meeting']);

        $applicable = $entry->meeting->categories()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->with(['scoringRules' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $linesByCategory = $entry->lines->groupBy('category_id');
        $total = 0;
        $categories = [];

        foreach ($applicable as $category) {
            $rules = $category->scoringRules;
            $lines = $linesByCategory->get($category->id, collect());

            $points = match ($category->input_shape) {
                Category::COUNT_SUBTYPE => $this->scoreCountLike($rules, $lines),
                Category::AMOUNT_SUBTYPE => $this->scoreAmount($rules, $lines),
                Category::ROSTER_FLAT_PENALTY => $this->scoreRoster($rules, $entry->attendance),
                Category::BINARY_FLAT => $this->scoreBinary($rules, $lines),
                Category::CONDITIONAL_MULTIPLIER => $this->scoreMultiplier($rules, $lines),
                default => 0,
            };

            $categories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'input_shape' => $category->input_shape,
                'points' => $points,
            ];
            $total += $points;
        }

        $entry->update(['computed_total' => $total]);

        return ['total' => $total, 'categories' => $categories];
    }

    /**
     * Score + stamp each count/amount line, returning the category subtotal.
     *
     * @param  Collection<int, \App\Models\ScoringRule>  $rules
     * @param  Collection<int, \App\Models\EntryLine>  $lines
     */
    private function scoreCountLike(Collection $rules, Collection $lines): int
    {
        $byId = $rules->keyBy('id');
        $subtotal = 0;

        foreach ($lines as $line) {
            $rulePoints = (int) ($byId->get($line->scoring_rule_id)->points ?? 0);
            $points = $this->scoring->countSubtype([['count' => (int) $line->count, 'points' => $rulePoints]]);

            if ((int) $line->computed_points !== $points) {
                $line->update(['computed_points' => $points]);
            }

            $subtotal += $points;
        }

        return $subtotal;
    }

    /**
     * amount_subtype (TYFCB): the category subtotal is derived from the SUM of
     * the line amounts (sum first, then divide once — matching the workbook),
     * not from a per-line count. The whole subtotal is stamped on the first
     * line and 0 on the rest so per-line computed_points still reconciles to
     * the category total; each line keeps its own `amount` for reporting.
     *
     * @param  Collection<int, \App\Models\ScoringRule>  $rules
     * @param  Collection<int, \App\Models\EntryLine>  $lines
     */
    private function scoreAmount(Collection $rules, Collection $lines): int
    {
        $rule = $rules->first();
        if (! $rule) {
            return 0;
        }

        $subtotal = $this->scoring->amountSubtype(
            (float) $lines->sum('amount'),
            (float) ($rule->param('per_amount', 10000) ?? 10000),
            (int) ($rule->param('points_per', 100) ?? 100),
        );

        $first = true;
        foreach ($lines as $line) {
            $target = $first ? $subtotal : 0;
            if ((int) $line->computed_points !== $target) {
                $line->update(['computed_points' => $target]);
            }
            $first = false;
        }

        return $subtotal;
    }

    /**
     * Roster flat/penalty for Attendance (metric=present → absences) or
     * Punctuality (metric=on_time → latecomers). Params come from the rule's
     * extra_params (flat/penalty/metric).
     *
     * @param  Collection<int, \App\Models\ScoringRule>  $rules
     * @param  Collection<int, \App\Models\EntryAttendance>  $attendance
     */
    private function scoreRoster(Collection $rules, Collection $attendance): int
    {
        $rule = $rules->first();
        if (! $rule) {
            return 0;
        }

        $metric = $rule->param('metric', 'present');
        $offenders = $metric === 'on_time'
            ? $attendance->where('is_on_time', false)->count()
            : $attendance->where('is_present', false)->count();

        return $this->scoring->rosterFlatPenalty(
            $offenders,
            (int) ($rule->param('flat', 0) ?? 0),
            (int) ($rule->param('penalty', 0) ?? 0),
        );
    }

    /**
     * binary_flat: a single line with count>0 means "on".
     *
     * @param  Collection<int, \App\Models\ScoringRule>  $rules
     * @param  Collection<int, \App\Models\EntryLine>  $lines
     */
    private function scoreBinary(Collection $rules, Collection $lines): int
    {
        $rule = $rules->first();
        $on = $lines->sum('count') > 0;
        $points = $this->scoring->binaryFlat($on, (int) ($rule->points ?? 0));

        foreach ($lines as $line) {
            if ((int) $line->computed_points !== ($on ? $points : 0)) {
                $line->update(['computed_points' => $on ? $points : 0]);
            }
        }

        return $points;
    }

    /**
     * conditional_multiplier (Trainings): line.count = members present,
     * line.whole_team doubles the per-member points.
     *
     * @param  Collection<int, \App\Models\ScoringRule>  $rules
     * @param  Collection<int, \App\Models\EntryLine>  $lines
     */
    private function scoreMultiplier(Collection $rules, Collection $lines): int
    {
        $rule = $rules->first();
        $line = $lines->first();
        if (! $rule || ! $line) {
            return 0;
        }

        $points = $this->scoring->conditionalMultiplier(
            (int) $line->count,
            (bool) $line->whole_team,
            (int) $rule->points,
            (float) ($rule->param('multiplier', 2) ?? 2),
        );

        if ((int) $line->computed_points !== $points) {
            $line->update(['computed_points' => $points]);
        }

        return $points;
    }
}

