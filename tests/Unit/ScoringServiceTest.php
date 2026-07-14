<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ScoringRule;
use App\Services\ScoringService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Pure unit tests for the scoring engine — no DB, no HTTP. Every point value is
 * supplied as data (BR-SCO-001), mirroring the requirements/05 seed values.
 */
class ScoringServiceTest extends TestCase
{
    private ScoringService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ScoringService;
    }

    // ---- 1. count_subtype ----

    public function test_count_subtype_sums_count_times_points(): void
    {
        // 2 Hot (300) + 1 Open (200) + 3 Closed (50) = 600 + 200 + 150 = 950
        $lines = [
            ['count' => 2, 'points' => 300],
            ['count' => 1, 'points' => 200],
            ['count' => 3, 'points' => 50],
        ];
        $this->assertSame(950, $this->svc->countSubtype($lines));
    }

    public function test_count_subtype_zero_and_repeat_worth_nothing(): void
    {
        $this->assertSame(0, $this->svc->countSubtype([]));
        $this->assertSame(0, $this->svc->countSubtype([['count' => 5, 'points' => 0]])); // Repeat = 0
    }

    public function test_count_subtype_same_member_multiple_rows(): void
    {
        // multi-row same-member visitors still just sum
        $this->assertSame(600, $this->svc->countSubtype([
            ['count' => 1, 'points' => 300],
            ['count' => 1, 'points' => 300],
        ]));
    }

    // ---- 2. amount_subtype (TYFCB) ----

    public function test_amount_subtype_scales_with_total_rupees(): void
    {
        // ₹10,000 ÷ 10000 × 100 = 100 points (the workbook's base rate)
        $this->assertSame(100, $this->svc->amountSubtype(10000, 10000, 100));
        // ₹15,000 → 150 ; ₹100 → 1
        $this->assertSame(150, $this->svc->amountSubtype(15000, 10000, 100));
        $this->assertSame(1, $this->svc->amountSubtype(100, 10000, 100));
        $this->assertSame(0, $this->svc->amountSubtype(0, 10000, 100));
    }

    public function test_amount_subtype_sums_before_dividing_and_rounds(): void
    {
        // ₹12,345 ÷ 10000 × 100 = 123.45 → 123 (rounded once, not per-line)
        $this->assertSame(123, $this->svc->amountSubtype(12345, 10000, 100));
    }

    public function test_dispatch_amount_subtype_sums_line_amounts(): void
    {
        $rules = new Collection([$this->rule(20, 0, ['per_amount' => 10000, 'points_per' => 100])]);
        $input = ['lines' => [
            ['scoring_rule_id' => 20, 'amount' => 12000],
            ['scoring_rule_id' => 20, 'amount' => 8000],
        ]]; // Σ ₹20,000 → 200
        $this->assertSame(200, $this->svc->computeCategory($this->category(Category::AMOUNT_SUBTYPE), $rules, $input));
    }

    // ---- 3. roster_flat_penalty ----

    public function test_attendance_flat_when_no_absences(): void
    {
        $this->assertSame(300, $this->svc->rosterFlatPenalty(0, 300, -200));
    }

    public function test_attendance_penalty_per_absent(): void
    {
        // 2 absent × −200 = −400 (negative totals allowed)
        $this->assertSame(-400, $this->svc->rosterFlatPenalty(2, 300, -200));
    }

    public function test_punctuality_flat_and_penalty(): void
    {
        $this->assertSame(100, $this->svc->rosterFlatPenalty(0, 100, -20));
        $this->assertSame(-60, $this->svc->rosterFlatPenalty(3, 100, -20));
    }

    // ---- 4. binary_flat ----

    public function test_binary_flat_awards_only_when_on(): void
    {
        $this->assertSame(200, $this->svc->binaryFlat(true, 200));
        $this->assertSame(0, $this->svc->binaryFlat(false, 200));
    }

    // ---- 5. conditional_multiplier ----

    public function test_training_base_when_not_whole_team(): void
    {
        // 4 present × 50 = 200
        $this->assertSame(200, $this->svc->conditionalMultiplier(4, false, 50, 2));
    }

    public function test_training_doubles_when_whole_team_present(): void
    {
        // whole team: 4 present × (50×2) = 400
        $this->assertSame(400, $this->svc->conditionalMultiplier(4, true, 50, 2));
        $this->assertSame(0, $this->svc->conditionalMultiplier(0, true, 50, 2));
    }

    // ---- Dispatcher: computeCategory (in-memory models, no DB) ----

    private function rule(int $id, int $points, ?array $extra = null): ScoringRule
    {
        $r = new ScoringRule(['points' => $points, 'extra_params' => $extra]);
        $r->id = $id;

        return $r;
    }

    private function category(string $shape): Category
    {
        return new Category(['input_shape' => $shape]);
    }

    public function test_dispatch_count_subtype_resolves_rule_points_by_id(): void
    {
        $rules = new Collection([$this->rule(10, 300), $this->rule(11, 50)]);
        $input = ['lines' => [
            ['scoring_rule_id' => 10, 'count' => 2], // 600
            ['scoring_rule_id' => 11, 'count' => 4], // 200
        ]];
        $this->assertSame(800, $this->svc->computeCategory($this->category(Category::COUNT_SUBTYPE), $rules, $input));
    }

    public function test_dispatch_roster_uses_extra_params(): void
    {
        $rules = new Collection([$this->rule(1, 0, ['flat' => 300, 'penalty' => -200])]);
        $cat = $this->category(Category::ROSTER_FLAT_PENALTY);

        $this->assertSame(300, $this->svc->computeCategory($cat, $rules, ['offenders' => 0]));
        $this->assertSame(-600, $this->svc->computeCategory($cat, $rules, ['offenders' => 3]));
    }

    public function test_dispatch_binary_uses_first_rule_points(): void
    {
        $rules = new Collection([$this->rule(1, 200)]);
        $cat = $this->category(Category::BINARY_FLAT);

        $this->assertSame(200, $this->svc->computeCategory($cat, $rules, ['on' => true]));
        $this->assertSame(0, $this->svc->computeCategory($cat, $rules, ['on' => false]));
    }

    public function test_dispatch_multiplier_reads_base_and_multiplier(): void
    {
        $rules = new Collection([$this->rule(1, 50, ['multiplier' => 2])]);
        $cat = $this->category(Category::CONDITIONAL_MULTIPLIER);

        $this->assertSame(200, $this->svc->computeCategory($cat, $rules, ['members_present' => 4, 'whole_team' => false]));
        $this->assertSame(400, $this->svc->computeCategory($cat, $rules, ['members_present' => 4, 'whole_team' => true]));
    }

    public function test_editing_a_rule_changes_a_fresh_computation(): void
    {
        $cat = $this->category(Category::COUNT_SUBTYPE);
        $input = ['lines' => [['scoring_rule_id' => 5, 'count' => 2]]];

        $before = $this->svc->computeCategory($cat, new Collection([$this->rule(5, 300)]), $input);
        $after = $this->svc->computeCategory($cat, new Collection([$this->rule(5, 500)]), $input);

        $this->assertSame(600, $before);
        $this->assertSame(1000, $after); // LT bumped Hot 300 → 500
    }
}
