<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, StandingsService $standings): Response
    {
        $captain = $request->user('team');
        $season = Season::current();

        $standing = ($season && $captain->team_id)
            ? $standings->forTeam($season, $captain->team_id)
            : null;

        return Inertia::render('Team/Dashboard', [
            'season' => $season?->only('name'),
            'standing' => $standing,
            'teamCount' => \App\Models\Team::active()->count(),
        ]);
    }
}
