<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use App\Services\ReportService;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of the reports (Phase 6C / FR-RPT-008). LT-only (guard:lt). Uses a
 * streamed download so no temp files or extra packages are needed; every export
 * mirrors the on-screen data (approved-only, via the same services).
 * PDF (league-branded) is an optional follow-up (needs a PDF package).
 */
class ExportController extends Controller
{
    public function standings(StandingsService $standings): StreamedResponse
    {
        $season = Season::current();
        $rows = $season ? $standings->forSeason($season)['rows'] : [];

        return $this->csv('league-standings.csv', ['Rank', 'Team', 'Points', 'Meetings approved'],
            array_map(fn ($r) => [$r['rank'], $r['team']['name'], $r['total'], $r['meetings_approved']], $rows));
    }

    public function season(StandingsService $standings): StreamedResponse
    {
        $season = Season::current();
        $grid = $season ? $standings->seasonGrid($season) : ['meetings' => [], 'rows' => []];

        $header = array_merge(['Team'], array_map(fn ($m) => "M{$m['sequence_no']}", $grid['meetings']), ['Total']);
        $rows = array_map(function ($r) {
            return array_merge(
                [$r['team']['name']],
                array_map(fn ($c) => $c['points'] === null ? '' : $c['points'], $r['cells']),
                [$r['total']],
            );
        }, $grid['rows']);

        return $this->csv('season-summary.csv', $header, $rows);
    }

    public function categoryLeaders(ReportService $reports): StreamedResponse
    {
        $season = Season::current();
        $cats = $season ? $reports->categoryLeaders($season) : [];

        return $this->csv('category-leaders.csv', ['Code', 'Category', 'Total points', 'Leader', 'Leader points'],
            array_map(fn ($c) => [$c['code'], $c['name'], $c['total'], $c['leader']['name'] ?? '', $c['leader']['points'] ?? ''], $cats));
    }

    public function team(Team $team, ReportService $reports): StreamedResponse
    {
        $season = Season::current();
        $report = $season ? $reports->teamReport($season, $team) : ['by_category' => [], 'total' => 0];

        $rows = array_map(fn ($c) => [$c['name'], $c['points']], $report['by_category']);
        $rows[] = ['Total', $report['total']];

        return $this->csv("team-{$team->short_code}-report.csv", ['Category', 'Points'], $rows);
    }

    public function mvp(Request $request, ReportService $reports): StreamedResponse
    {
        $season = Season::current();
        $leaders = $season ? $reports->mvpLeaderboard($season, $request->query('category'), 100) : [];

        return $this->csv('mvp-leaderboard.csv', ['Rank', 'Member', 'Team', 'Points'],
            array_map(fn ($m, $i) => [$i + 1, $m['name'], $m['team'], $m['points']], $leaders, array_keys($leaders)));
    }

    /**
     * @param  list<string>  $header
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
