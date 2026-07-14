<?php

namespace Tests\Feature\Meetings;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\Season;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_list_renders_for_lt(): void
    {
        Category::factory()->count(4)->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/categories')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Categories/Index')->has('categories', 4));
    }

    public function test_lt_adds_a_category(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/categories', [
                'name' => 'New Cat',
                'code' => 'NEW',
                'input_shape' => Category::COUNT_SUBTYPE,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['code' => 'NEW', 'is_active' => true]);
    }

    public function test_category_code_is_unique_and_shape_validated(): void
    {
        Category::factory()->create(['code' => 'DUP']);
        $lt = LtUser::factory()->create();

        $this->actingAs($lt, 'lt')->post('/lt/categories', [
            'name' => 'x', 'code' => 'DUP', 'input_shape' => Category::COUNT_SUBTYPE,
        ])->assertSessionHasErrors('code');

        $this->actingAs($lt, 'lt')->post('/lt/categories', [
            'name' => 'y', 'code' => 'YY', 'input_shape' => 'not_a_shape',
        ])->assertSessionHasErrors('input_shape');
    }

    public function test_lt_toggles_category_active(): void
    {
        $cat = Category::factory()->create(['is_active' => true]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->patch("/lt/categories/{$cat->id}/toggle");

        $this->assertFalse($cat->fresh()->is_active);
    }

    public function test_lt_reorders_categories(): void
    {
        $a = Category::factory()->create(['display_order' => 1]);
        $b = Category::factory()->create(['display_order' => 2]);
        $c = Category::factory()->create(['display_order' => 3]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/categories/reorder', ['ids' => [$c->id, $a->id, $b->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $c->fresh()->display_order);
        $this->assertSame(2, $a->fresh()->display_order);
        $this->assertSame(3, $b->fresh()->display_order);
    }

    public function test_new_meeting_defaults_to_full_active_category_set(): void
    {
        $season = Season::factory()->active()->create();
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->inactive()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/meetings', ['meeting_date' => '2026-10-01']);

        $meeting = Meeting::latest('id')->first();
        $this->assertSame(3, $meeting->categories()->count()); // only active
    }

    public function test_lt_sets_applicable_categories_for_a_meeting(): void
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->create(['season_id' => $season->id]);
        $cats = Category::factory()->count(4)->create();
        $meeting->categories()->sync($cats->pluck('id'));

        // Reduce to just 2 (mirrors the Meeting-1 reduced set).
        $keep = $cats->take(2)->pluck('id')->all();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/meetings/{$meeting->id}/categories", ['category_ids' => $keep])
            ->assertRedirect(route('lt.meetings'));

        $this->assertSame(2, $meeting->categories()->count());
    }

    public function test_captain_cannot_reach_category_management(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/categories')->assertForbidden();
    }
}
