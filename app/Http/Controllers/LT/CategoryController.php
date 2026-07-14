<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT category catalog management (FR-SCO-001). LT enables/disables and orders
 * categories; point values live in scoring_rules (Phase 2E). LT-only (guard:lt).
 */
class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LT/Categories/Index', [
            'categories' => Category::ordered()->withCount('scoringRules')->get()->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'input_shape' => $c->input_shape,
                'display_order' => $c->display_order,
                'is_active' => $c->is_active,
                'rules_count' => $c->scoring_rules_count,
            ]),
            'inputShapes' => Category::INPUT_SHAPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:8', Rule::unique('categories', 'code')],
            'input_shape' => ['required', Rule::in(Category::INPUT_SHAPES)],
        ]);

        Category::create($data + [
            'display_order' => (Category::max('display_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', "Category “{$data['name']}” added.");
    }

    public function toggleActive(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active
            ? "“{$category->name}” enabled."
            : "“{$category->name}” disabled.");
    }

    /** Persist a new ordering from the drag/up-down list. */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($data['ids'] as $order => $id) {
            Category::whereKey($id)->update(['display_order' => $order + 1]);
        }

        return back()->with('success', 'Order updated.');
    }
}
