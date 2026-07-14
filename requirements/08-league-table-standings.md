# 08 — League Table & Standings

## Purpose
The signature output: an auto-computed ranking of all active teams by approved points, with position movement versus the previous meeting and a per-meeting approval-status indicator.

## User stories
- As anyone with access, I see the current standings ranked by total approved points.
- As a viewer, I see whether each team moved up/down since last meeting.
- As a viewer, I see at a glance which meetings each team has had approved.

## Functional requirements
- **FR-LGT-001** Standings rank active teams by total **approved** points, descending.
- **FR-LGT-002** Each row shows: position, movement chevron (▲/▼/– vs previous meeting's standings), team crest, running total (mono), and a dot per meeting (filled gold = approved, hollow grey = pending/not submitted).
- **FR-LGT-003** Positions 1–3 get gold/silver/bronze crest rings; others neutral.
- **FR-LGT-004** The table recomputes automatically whenever an entry is approved or unlocked.
- **FR-LGT-005** A team viewing the table sees their own row highlighted.
- **FR-LGT-006** The table is available embedded (compact, on dashboards) and full-screen (its own page).
- **FR-LGT-007** Ties are broken by a defined rule (see BR) and shown with equal position or a tiebreak indicator.
- **FR-LGT-008** Movement is computed by comparing current rank to rank after the previous approved meeting.

## Business rules
- **BR-LGT-001** Only `approved` entries count toward totals.
- **BR-LGT-002** Tiebreak order (proposed): (1) higher approved total, (2) more meetings approved, (3) alphabetical — confirm with LT.
- **BR-LGT-003** Inactive teams are excluded from the live table but their history remains in season records.

## Key screens / flows
League table (team view) · League table (LT manage/publish) · embedded dashboard mini-table.
(Page inventory B13, C18, plus dashboard embeds.)

## Data touched
Read-only aggregation over `meeting_entries` (approved) grouped by team; optional materialized `standings_snapshots` per meeting for movement history.

## Edge cases
- No meetings approved yet → table shows all teams at 0, no movement.
- Mid-season team activation/deactivation → table reflects only active teams.
- Unlock that drops a team's total → movement chevrons recompute for affected rows.

## Open questions / to clarify
- Exact tiebreak rule (proposed above) — confirm with LT.
- Should movement compare to the immediately previous meeting or previous *approved* state? (Assumed previous approved standings snapshot.)
