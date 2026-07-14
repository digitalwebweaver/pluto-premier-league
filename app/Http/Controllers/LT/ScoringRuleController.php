<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ScoringRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT scoring-rules management (FR-SCO-002, 003). The flexibility engine screen:
 * points and subtypes are DB-backed and edited here — never in code. LT-only.
 */
class ScoringRuleController extends Controller
{
    public function index(): Response
    {
        $categories = Category::ordered()->with(['scoringRules' => fn ($q) => $q->orderBy('display_order')->orderBy('id')])->get();

        return Inertia::render('LT/Scoring/Index', [
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'input_shape' => $c->input_shape,
                'is_active' => $c->is_active,
                'rules' => $c->scoringRules->map($this->presentRule(...)),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subtype_label' => ['required', 'string', 'max:191'],
            'points' => ['required', 'integer'],
        ]);

        ScoringRule::create($data + [
            'is_active' => true,
            'display_order' => (ScoringRule::where('category_id', $data['category_id'])->max('display_order') ?? 0) + 1,
        ]);

        return back()->with('success', 'Subtype added.');
    }

    public function edit(ScoringRule $scoringRule): Response
    {
        $scoringRule->load('category');

        return Inertia::render('LT/Scoring/Edit', [
            'rule' => [
                'id' => $scoringRule->id,
                'subtype_label' => $scoringRule->subtype_label,
                'points' => $scoringRule->points,
                'flat' => $scoringRule->param('flat'),
                'penalty' => $scoringRule->param('penalty'),
                'multiplier' => $scoringRule->param('multiplier'),
                'is_active' => $scoringRule->is_active,
            ],
            'category' => [
                'name' => $scoringRule->category->name,
                'input_shape' => $scoringRule->category->input_shape,
            ],
        ]);
    }

    public function update(Request $request, ScoringRule $scoringRule): RedirectResponse
    {
        $scoringRule->load('category');
        $shape = $scoringRule->category->input_shape;

        $data = $request->validate([
            'subtype_label' => ['required', 'string', 'max:191'],
            'points' => ['required', 'integer'],
            'flat' => ['nullable', 'integer'],
            'penalty' => ['nullable', 'integer'],
            'multiplier' => ['nullable', 'numeric', 'min:1'],
        ]);

        // Only persist the extra_params relevant to this category's shape.
        $extra = match ($shape) {
            Category::ROSTER_FLAT_PENALTY => ['flat' => (int) $data['flat'], 'penalty' => (int) $data['penalty']],
            Category::CONDITIONAL_MULTIPLIER => ['multiplier' => (float) $data['multiplier']],
            default => null,
        };

        $scoringRule->update([
            'subtype_label' => $data['subtype_label'],
            'points' => $data['points'],
            'extra_params' => $extra,
        ]);

        return redirect()->route('lt.scoring')->with('success', 'Rule updated.');
    }

    public function toggleActive(ScoringRule $scoringRule): RedirectResponse
    {
        $scoringRule->update(['is_active' => ! $scoringRule->is_active]);

        return back()->with('success', 'Rule updated.');
    }

    private function presentRule(ScoringRule $r): array
    {
        return [
            'id' => $r->id,
            'subtype_label' => $r->subtype_label,
            'points' => $r->points,
            'extra_params' => $r->extra_params,
            'is_active' => $r->is_active,
        ];
    }
}
