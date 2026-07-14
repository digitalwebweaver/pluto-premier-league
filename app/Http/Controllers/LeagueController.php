<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signature League Table (design.md §5). Shared route — a captain sees
 * their own row highlighted; the LT sees the full table. Only approved entries
 * count (StandingsService / BR-LGT-001).
 */
class LeagueController extends Controller
{
    public function index(Request $request, StandingsService $standings): Response
    {
        $season = Season::current();
        $viewerTeamId = $request->user('team')?->team_id;

        $table = $season
            ? $standings->forSeason($season, $viewerTeamId)
            : ['meetings' => [], 'rows' => []];

        return Inertia::render('League', [
            'role' => $request->user('lt') ? 'lt' : 'captain',
            'season' => $season?->only('name'),
            'meetings' => $table['meetings'],
            'rows' => $table['rows'],
        ]);
    }
}
