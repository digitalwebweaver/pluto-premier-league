# 06 — Score Entry (Matchday Scorecard)

## Purpose
The core team-facing screen: a captain records their team's performance for an open meeting across the applicable categories, saving as a draft and submitting to LT. Designed to be filled in fast on a phone.

## User stories
- As a **captain**, I open the current meeting and see all applicable categories as collapsed rows with live subtotals.
- As a **captain**, I expand a category and add rows (member, subtype, count) or check off attendance.
- As a **captain**, I watch the running total update as I enter data.
- As a **captain**, I save a draft and finish later, then review and submit to LT.

## Functional requirements
- **FR-ENT-001** The entry form lists only the categories applicable to that meeting (per `FR-MTG-005`), each as a collapsed accordion showing its live subtotal.
- **FR-ENT-002** `count_subtype` / `amount_subtype` categories present repeatable rows: `[member ▾][subtype ▾][count]` (+ amount for TYFCB); rows can be added/removed.
- **FR-ENT-003** Subtype dropdowns are populated live from active `scoring_rules` for that category.
- **FR-ENT-004** Member dropdowns list only the captain's active team members.
- **FR-ENT-005** Attendance/punctuality render as a roster checklist (present/absent, on-time/late); flat-vs-penalty points compute automatically and display live.
- **FR-ENT-006** Binary categories render as a single toggle.
- **FR-ENT-007** A sticky running total sums all categories in real time (client-side convenience) with a subtle pulse on change.
- **FR-ENT-008** "Save draft" persists the entry with status `draft`; the captain can return and continue editing.
- **FR-ENT-009** A pre-submit review screen shows every category's entered data and the total before "Submit to LT".
- **FR-ENT-010** On submit, the server recomputes all points authoritatively, sets status `submitted`, and records submitted_at.
- **FR-ENT-011** A submitted entry remains editable by the team until LT approves it (per workflow) — re-submitting updates the record.
- **FR-ENT-012** Server-side validation enforces sane inputs (non-negative counts, member belongs to team, subtype belongs to category, at least one attendance mark before submit).
- **FR-ENT-013** One entry per (team, meeting); reopening edits the same record, never creates duplicates.

## Business rules
- **BR-ENT-001** The client-side running total is never trusted for scoring; the server's recomputation is authoritative (`FR-SCO-011`).
- **BR-ENT-002** A captain can only enter scores for their own team and only for open meetings.
- **BR-ENT-003** Draft data is private to the team until submitted (LT sees it only once submitted, or via team drill-in for support).

## Key screens / flows
Submit-scores meeting list · Meeting entry form (+ category panels + attendance sub-view) · Pre-submit review.
(Page inventory B2, B3, B4, B5, B6.)

## Data touched
`meeting_entries` (header: team, meeting, status, totals) · `entry_lines` (per category/subtype/member rows) · `entry_attendance` (roster marks).

## Edge cases
- Meeting closed while editing → save allowed, submit blocked with message.
- Duplicate member rows in one category → allowed (e.g. same member, two visitors) and summed.
- All-zero submission → allowed but flagged in review ("This will submit 0 points for most categories — continue?").
- Network drop on mobile → draft autosave / explicit save prevents data loss (autosave is a nice-to-have; explicit save is required).

## Open questions / to clarify
- Is autosave required or is explicit "Save draft" sufficient for v1? (Assumed explicit save required; autosave deferred.)
- Photo evidence upload for Team/Joint Meeting — required or optional field? (Assumed optional v1.)
