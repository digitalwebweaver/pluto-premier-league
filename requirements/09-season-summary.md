# 09 — Season Summary

## Purpose
A computed season recap that mirrors the source workbook's "Summery" sheet — total points per team per meeting in a grid, with the season champion highlighted. Replaces the manually-typed summary.

## User stories
- As a **captain**, I see my team's points per meeting and where I stand for the season.
- As **LT**, I see the full grid of all teams × all meetings and the current/seasonal champion.

## Functional requirements
- **FR-SSN-001** A season groups a set of meetings; the app supports at least one active season.
- **FR-SSN-002** The season summary shows a grid: rows = teams, columns = meetings, cells = that team's approved points for that meeting, with a season total column.
- **FR-SSN-003** Only approved points populate cells; pending cells show a clear "pending/—" marker.
- **FR-SSN-004** The season champion (top season total) is visually emphasized (gold ring, subtle top border) once the season is complete or as a live leader before then.
- **FR-SSN-005** The summary recomputes automatically as entries are approved/unlocked.
- **FR-SSN-006** Team view is scoped to that team's row with league context; LT view shows the full grid.

## Business rules
- **BR-SSN-001** Season totals = Σ approved meeting points per team.
- **BR-SSN-002** A season can be marked complete by LT, freezing the champion designation.

## Key screens / flows
Season summary (team view) · Season summary (LT full grid).
(Page inventory B14, C19.)

## Data touched
`seasons`, aggregation over `meetings` + approved `meeting_entries`.

## Edge cases
- Meeting with no approved entries for a team → cell shows pending, not 0, to distinguish "not yet scored" from "scored 0".
- Season with a single meeting → summary still renders (degenerate but valid).

## Open questions / to clarify
- Are there multiple concurrent seasons, or one at a time? (Assumed one active season v1; multi-season history deferred.)
- Champion criteria if season ends on a tie → falls back to `08` tiebreak rule.
