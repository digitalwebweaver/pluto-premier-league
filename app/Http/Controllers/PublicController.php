<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\StandingsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, no-login standings views (Phase 6E / requirements 12). Strictly
 * read-only and approved-only — `StandingsService` reads only `approved`
 * entries, so no draft/submitted/sent-back data can leak (BR-PUB-001/002).
 * No controls, no member-level detail. A deliberately public route (v1);
 * a share token can be layered on later.
 */
class PublicController extends Controller
{
    public function league(StandingsService $standings): Response
    {
        return Inertia::render('Public/League', $this->standings($standings));
    }

    public function season(StandingsService $standings): Response
    {
        $season = Season::current();
        $grid = $season ? $standings->seasonGrid($season, null) : ['meetings' => [], 'rows' => []];

        return Inertia::render('Public/Season', [
            'season' => $season?->only('name', 'is_complete'),
            'meetings' => $grid['meetings'],
            'rows' => $grid['rows'],
        ]);
    }

    /** Full-screen projector mode (auto-refreshes client-side). */
    public function live(StandingsService $standings): Response
    {
        return Inertia::render('Public/Live', $this->standings($standings));
    }

    /**
     * @return array<string, mixed>
     */
    private function standings(StandingsService $standings): array
    {
        $season = Season::current();
        $table = $season ? $standings->forSeason($season, null) : ['meetings' => [], 'rows' => []];

        return [
            'season' => $season?->only('name'),
            'meetings' => $table['meetings'],
            'rows' => $table['rows'],
        ];
    }
}
