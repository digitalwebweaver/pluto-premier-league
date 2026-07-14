<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Groundwork for team-scoped models (FR-ROLE-002). Phase 2 models that carry a
 * `team_id` (members, meeting_entries, …) `use` this trait to gain a
 * `->forTeam()` scope that defaults to the authenticated captain's team, so
 * queries are always scoped server-side and never leak across teams.
 *
 * Example (Phase 2): `Member::forTeam()->get()` or `->forTeam($id)`.
 */
trait BelongsToTeam
{
    public function scopeForTeam(Builder $query, ?int $teamId = null): Builder
    {
        $teamId ??= Auth::guard('team')->user()?->team_id;

        return $query->where($query->getModel()->getTable().'.team_id', $teamId);
    }
}
