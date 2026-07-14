<?php

namespace Tests\Feature\Scoring;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\ScoringRule;
use App\Models\TeamUser;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ScoringRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoring_screen_renders_categories_with_rules(): void
    {
        $cat = Category::factory()->create();
        ScoringRule::factory()->count(3)->create(['category_id' => $cat->id]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/scoring')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Scoring/Index')->has('categories'));
    }

    public function test_lt_adds_a_subtype(): void
    {
        $cat = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/scoring', [
                'category_id' => $cat->id,
                'subtype_label' => 'Super Hot',
                'points' => 400,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('scoring_rules', [
            'category_id' => $cat->id,
            'subtype_label' => 'Super Hot',
            'points' => 400,
        ]);
    }

    public function test_lt_edits_a_rule_points(): void
    {
        $cat = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $rule = ScoringRule::factory()->create(['category_id' => $cat->id, 'points' => 300]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/scoring/{$rule->id}", ['subtype_label' => 'Hot', 'points' => 500])
            ->assertRedirect(route('lt.scoring'));

        $this->assertSame(500, $rule->fresh()->points);
    }

    public function test_editing_a_roster_rule_persists_flat_and_penalty(): void
    {
        $cat = Category::factory()->create(['input_shape' => Category::ROSTER_FLAT_PENALTY]);
        $rule = ScoringRule::factory()->create(['category_id' => $cat->id]);

        $this->actingAs(LtUser::factory()->create(), 'lt')->put("/lt/scoring/{$rule->id}", [
            'subtype_label' => 'Attendance',
            'points' => 0,
            'flat' => 350,
            'penalty' => -250,
        ]);

        $this->assertSame(['flat' => 350, 'penalty' => -250], $rule->fresh()->extra_params);
    }

    public function test_seeder_loads_the_default_rule_set(): void
    {
        $this->seed([CategorySeeder::class, ScoringRuleSeeder::class]);

        // Visitors subtypes match requirements/05.
        $visitors = Category::where('code', 'VIS')->first();
        $this->assertSame(300, $visitors->scoringRules()->where('subtype_label', 'Hot')->value('points'));
        $this->assertSame(0, $visitors->scoringRules()->where('subtype_label', 'Repeat')->value('points'));

        // Attendance flat/penalty in extra_params (BR-SCO-004).
        $att = Category::where('code', 'ATT')->first()->scoringRules()->first();
        $this->assertSame(300, $att->extra_params['flat']);
        $this->assertSame(-200, $att->extra_params['penalty']);
        $this->assertSame('present', $att->extra_params['metric']);

        // Trainings carries the multiplier.
        $trn = Category::where('code', 'TRN')->first()->scoringRules()->first();
        $this->assertSame(2, (int) $trn->extra_params['multiplier']);
    }

    public function test_specific_ask_is_clean_200_not_x76(): void
    {
        $this->seed([CategorySeeder::class, ScoringRuleSeeder::class]);

        $ask = Category::where('code', 'ASK')->first()->scoringRules()->first();
        $this->assertSame(200, $ask->points); // BR-SCO-002: stray ×76 not reproduced
    }

    public function test_captain_cannot_reach_scoring_rules(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/scoring')->assertForbidden();
    }
}
