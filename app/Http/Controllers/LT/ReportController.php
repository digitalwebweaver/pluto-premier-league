<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT reports (Phase 6A) — approved-data-only aggregations. LT-only (guard:lt).
 */
class ReportController extends Controller
{
    public function index(ReportService $reports): Response
    {
        $season = Season::current();

        return Inertia::render('LT/Reports/Index', [
            'season' => $season?->only('name'),
            'teams' => $season ? $reports->teamsWithData($season) : [],
        ]);
    }

    public function team(Team $team, ReportService $reports): Response
    {
        $season = Season::current();
        $report = $season ? $reports->teamReport($season, $team) : ['by_category' => [], 'by_meeting' => [], 'total' => 0];

        return Inertia::render('LT/Reports/Team', [
            'team' => $team->only('id', 'name', 'short_code', 'crest_color'),
            'byCategory' => $report['by_category'],
            'byMeeting' => $report['by_meeting'],
            'total' => $report['total'],
        ]);
    }

    public function categories(ReportService $reports): Response
    {
        $season = Season::current();

        return Inertia::render('LT/Reports/Categories', [
            'categories' => $season ? $reports->categoryLeaders($season) : [],
        ]);
    }

    public function mvp(Request $request, ReportService $reports): Response
    {
        $season = Season::current();
        $code = $request->query('category');

        return Inertia::render('LT/Reports/Mvp', [
            'filter' => $code,
            'categories' => $reports->memberCategories(),
            'leaders' => $season ? $reports->mvpLeaderboard($season, $code) : [],
        ]);
    }
}
