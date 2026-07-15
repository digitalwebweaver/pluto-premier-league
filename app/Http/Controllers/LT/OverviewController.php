<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Services\StandingsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT landing dashboard — the four KPI cards, "needs your attention" queue
 * preview, and recent-approvals feed all read real data (queue counts,
 * team/meeting state, current standings leader), replacing the static
 * placeholder from Phase 0C.
 */
class OverviewController extends Controller
{
    public function index(StandingsService $standings): Response
    {
        $season = Season::current();

        $meetingCounts = $season
            ? $season->meetings()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')
            : collect();

        $leader = null;
        if ($season) {
            $row = collect($standings->forSeason($season)['rows'])->first(fn ($r) => $r['total'] > 0);
            if ($row) {
                $leader = ['name' => $row['team']['name'], 'total' => $row['total']];
            }
        }

        return Inertia::render('LT/Overview', [
            'season' => $season?->only('name'),
            'kpis' => [
                'pending_approvals' => MeetingEntry::where('status', MeetingEntry::SUBMITTED)->count(),
                'active_teams' => Team::active()->count(),
                'meetings_open' => (int) ($meetingCounts['open'] ?? 0),
                'meetings_total' => $season ? $season->meetings()->count() : 0,
                'leader' => $leader,
            ],
            'needsAttention' => MeetingEntry::where('status', MeetingEntry::SUBMITTED)
                ->with(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no'])
                ->orderBy('submitted_at')
                ->limit(6)
                ->get()
                ->map(fn (MeetingEntry $e) => [
                    'id' => $e->id,
                    'computed_total' => $e->computed_total,
                    'submitted_at' => optional($e->submitted_at)->toIso8601String(),
                    'team' => [
                        'name' => $e->team->name,
                        'short_code' => $e->team->short_code,
                        'crest_color' => $e->team->crest_color,
                    ],
                    'meeting' => ['sequence_no' => $e->meeting->sequence_no],
                ]),
            'recentlyApproved' => MeetingEntry::where('status', MeetingEntry::APPROVED)
                ->with(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no', 'approver:id,name'])
                ->orderByDesc('approved_at')
                ->limit(5)
                ->get()
                ->map(fn (MeetingEntry $e) => [
                    'id' => $e->id,
                    'computed_total' => $e->computed_total,
                    'approved_at' => optional($e->approved_at)->toIso8601String(),
                    'approved_by' => $e->approver?->name,
                    'team' => [
                        'name' => $e->team->name,
                        'short_code' => $e->team->short_code,
                        'crest_color' => $e->team->crest_color,
                    ],
                    'meeting' => ['sequence_no' => $e->meeting->sequence_no],
                ]),
        ]);
    }
}
