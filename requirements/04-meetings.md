# 04 — Meetings

## Purpose
Meetings are the league's scoring periods (fortnightly, e.g. 1-7-26, 15-7-26, …). LT defines them centrally; every team scores the same meeting. Which categories apply can vary per meeting (the source workbook's Meeting 1 used a reduced category set).

## User stories
- As **LT**, I create the season's meeting dates.
- As **LT**, I open a meeting for submissions and close it when the window ends.
- As **LT**, I choose which scoring categories apply to a given meeting.
- As a **team captain**, I can only enter/submit scores for meetings that are currently open.

## Functional requirements
- **FR-MTG-001** LT can create a meeting with a date and sequence number (Meeting 1, 2, …).
- **FR-MTG-002** LT can edit a meeting's date/label before it has approved submissions.
- **FR-MTG-003** A meeting has a status: `scheduled` → `open` (accepting submissions) → `closed` (locked from new submissions).
- **FR-MTG-004** LT can toggle a meeting open/closed.
- **FR-MTG-005** LT can set which categories apply to a meeting (subset of all categories); the entry form for that meeting shows only applicable categories.
- **FR-MTG-006** Teams can only create/edit/submit entries for `open` meetings; closed meetings are read-only to teams.
- **FR-MTG-007** Meetings belong to a season (see `09-season-summary.md`).
- **FR-MTG-008** A meeting list (LT) shows each meeting's status and how many teams have submitted/been approved.

## Business rules
- **BR-MTG-001** A meeting cannot be deleted once any team has an approved entry for it; it can be closed instead.
- **BR-MTG-002** Default applicable-categories set = the full ~17; LT explicitly removes any not used (mirrors Meeting-1 reduced set).
- **BR-MTG-003** Closing a meeting does not auto-approve pending submissions; LT must still action them (or they don't count).

## Key screens / flows
Meetings list · Create/edit meeting · Open/close control.
(Page inventory C9, C10, C11.)

## Data touched
`seasons`, `meetings`, `meeting_categories` (which categories apply to a meeting).

## Edge cases
- A team mid-draft when LT closes the meeting → draft is preserved but can no longer be submitted until/unless reopened (message shown).
- Adding a category to a meeting after some teams submitted → their totals recompute against the new applicable set (flag to LT; may require send-back).

## Open questions / to clarify
- Fixed fortnightly cadence, or arbitrary LT-chosen dates? (Assumed LT-chosen dates; cadence not enforced.)
- Should there be a hard submission deadline (auto-close at a datetime) or manual close only? (Assumed manual; auto-close deferred.)
