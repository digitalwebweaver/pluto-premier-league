<?php

namespace App\Services;

use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * League standings (Phase 5 / requirements 08). Ranks active teams by total
 * APPROVED points (BR-LGT-001) with the tiebreak: total desc → meetings
 * approved desc → name asc (BR-LGT-002). Movement compares the current rank to
 * the rank as of the meeting before the latest approved one (computed on the
 * fly; `standings_snapshots` deferred). Recomputes naturally on approve/unlock
 * since it only reads `approved` entries (FR-LGT-004).
 */
class StandingsService
{
    /**
     * @return array{meetings: array<int, array<string,mixed>>, rows: array<int, array<string,mixed>>}
     */
    public function forSeason(Season $season, ?int $viewerTeamId = null): array
    {
        $teams = Team::active()->orderBy('name')->get();
        $meetings = $season->meetings()->orderBy('sequence_no')->get();
        $seqById = $meetings->pluck('sequence_no', 'id');

        $approved = MeetingEntry::where('status', MeetingEntry::APPROVED)
            ->whereIn('meeting_id', $meetings->pluck('id'))
            ->get(['team_id', 'meeting_id', 'computed_total']);

        $byTeam = $approved->groupBy('team_id');
        $latestSeq = $meetings->whereIn('id', $approved->pluck('meeting_id')->unique())->max('sequence_no');

        // Per-team aggregates.
        $agg = $teams->map(function (Team $team) use ($byTeam, $seqById, $latestSeq) {
            $entries = $byTeam->get($team->id, collect());

            return [
                'team' => $team,
                'total' => (int) $entries->sum('computed_total'),
                'meetings_approved' => $entries->count(),
                'approved_meeting_ids' => $entries->pluck('meeting_id')->all(),
                // Total as of before the latest approved meeting (for movement).
                'prev_total' => $latestSeq === null ? 0 : (int) $entries
                    ->filter(fn ($e) => $seqById[$e->meeting_id] < $latestSeq)
                    ->sum('computed_total'),
            ];
        });

        $ranked = $agg->sort($this->comparator('total'))->values();
        $prevRankByTeam = $this->rankMap($agg->sort($this->comparator('prev_total'))->values());

        $rows = $ranked->map(function (array $r, int $i) use ($meetings, $prevRankByTeam, $latestSeq, $viewerTeamId) {
            $rank = $i + 1;
            $prevRank = $prevRankByTeam[$r['team']->id] ?? $rank;
            $delta = $prevRank - $rank; // positive = moved up

            return [
                'rank' => $rank,
                'movement' => $latestSeq === null ? 'flat' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat')),
                'movement_by' => abs($delta),
                'ring' => [1 => 'gold', 2 => 'silver', 3 => 'bronze'][$rank] ?? null,
                'total' => $r['total'],
                'meetings_approved' => $r['meetings_approved'],
                'is_current' => $viewerTeamId !== null && $r['team']->id === $viewerTeamId,
                'team' => [
                    'id' => $r['team']->id,
                    'name' => $r['team']->name,
                    'short_code' => $r['team']->short_code,
                    'crest_color' => $r['team']->crest_color,
                    'logo_path' => $r['team']->logo_path,
                ],
                'dots' => $meetings->map(fn ($m) => [
                    'seq' => $m->sequence_no,
                    'approved' => in_array($m->id, $r['approved_meeting_ids'], true),
                ])->all(),
            ];
        });

        return [
            'meetings' => $meetings->map(fn ($m) => ['sequence_no' => $m->sequence_no])->all(),
            'rows' => $rows->all(),
        ];
    }

    /**
     * Season summary grid (Phase 5B / requirements 09): teams × meetings, each
     * cell the team's APPROVED points for that meeting (null = pending, shown
     * "—" not 0 — BR-SSN edge case), a season-total column, and champion/leader
     * emphasis on the top total.
     *
     * @return array{meetings: array<int, array<string,mixed>>, rows: array<int, array<string,mixed>>}
     */
    public function seasonGrid(Season $season, ?int $viewerTeamId = null): array
    {
        $teams = Team::active()->orderBy('name')->get();
        $meetings = $season->meetings()->orderBy('sequence_no')->get();

        $approved = MeetingEntry::where('status', MeetingEntry::APPROVED)
            ->whereIn('meeting_id', $meetings->pluck('id'))
            ->get(['team_id', 'meeting_id', 'computed_total']);

        // "teamId-meetingId" => approved points (may be 0).
        $cellMap = $approved->mapWithKeys(fn ($e) => ["{$e->team_id}-{$e->meeting_id}" => (int) $e->computed_total]);

        $agg = $teams->map(function (Team $team) use ($meetings, $cellMap) {
            $cells = $meetings->map(function ($m) use ($team, $cellMap) {
                $key = "{$team->id}-{$m->id}";
                return [
                    'seq' => $m->sequence_no,
                    'points' => $cellMap->has($key) ? $cellMap[$key] : null, // null = pending
                ];
            });

            return [
                'team' => $team,
                'cells' => $cells->all(),
                'total' => (int) $cells->sum(fn ($c) => $c['points'] ?? 0),
                'meetings_approved' => $cells->filter(fn ($c) => $c['points'] !== null)->count(),
            ];
        });

        $ranked = $agg->sort($this->comparator('total'))->values();

        $rows = $ranked->map(fn (array $r, int $i) => [
            'team' => [
                'id' => $r['team']->id,
                'name' => $r['team']->name,
                'short_code' => $r['team']->short_code,
                'crest_color' => $r['team']->crest_color,
                'logo_path' => $r['team']->logo_path,
            ],
            'cells' => $r['cells'],
            'total' => $r['total'],
            'is_champion' => $i === 0 && $r['total'] > 0,
            'is_current' => $viewerTeamId !== null && $r['team']->id === $viewerTeamId,
        ]);

        return [
            'meetings' => $meetings->map(fn ($m) => [
                'sequence_no' => $m->sequence_no,
                'meeting_date' => $m->meeting_date->toDateString(),
            ])->all(),
            'rows' => $rows->all(),
        ];
    }

    /** The viewer team's row (for a dashboard hero), or null. */
    public function forTeam(Season $season, int $teamId): ?array
    {
        $table = $this->forSeason($season, $teamId);

        return collect($table['rows'])->firstWhere('team.id', $teamId)
            ?? collect($table['rows'])->first(fn ($r) => $r['team']['id'] === $teamId);
    }

    private function comparator(string $totalKey): callable
    {
        return fn (array $a, array $b): int => ($b[$totalKey] <=> $a[$totalKey])
            ?: ($b['meetings_approved'] <=> $a['meetings_approved'])
            ?: ($a['team']->name <=> $b['team']->name);
    }

    /**
     * @param  Collection<int, array<string,mixed>>  $ranked
     * @return array<int,int>
     */
    private function rankMap(Collection $ranked): array
    {
        $map = [];
        foreach ($ranked as $i => $r) {
            $map[$r['team']->id] = $i + 1;
        }

        return $map;
    }
}
