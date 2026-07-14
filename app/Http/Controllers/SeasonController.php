<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Season summary grid (design.md §; requirements 09). Shared route — captain
 * sees their own row highlighted with league context; LT sees the full grid.
 */
class SeasonController extends Controller
{
    public function index(Request $request, StandingsService $standings): Response
    {
        $season = Season::current();
        $viewerTeamId = $request->user('team')?->team_id;

        $grid = $season
            ? $standings->seasonGrid($season, $viewerTeamId)
            : ['meetings' => [], 'rows' => []];

        return Inertia::render('Season', [
            'role' => $request->user('lt') ? 'lt' : 'captain',
            'season' => $season?->only('name', 'is_complete'),
            'meetings' => $grid['meetings'],
            'rows' => $grid['rows'],
        ]);
    }
}
