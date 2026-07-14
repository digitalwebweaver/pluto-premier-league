# 02 — Team Management

## Purpose
Manage the teams that compete in the league, their identity (name, crest color), and the login credentials LT issues to each team captain.

## User stories
- As **LT**, I create a team, give it a name and crest color, and issue a captain login.
- As **LT**, I deactivate a team that leaves the league without deleting its historical scores.
- As a **team captain**, I can view and lightly edit my own team's profile (crest color, captain contact) but not create teams.

## Functional requirements
- **FR-TEAM-001** LT can create a team with: name (unique), crest color (from the palette), optional short code/initials for the crest.
- **FR-TEAM-002** LT can edit a team's name, color, and status.
- **FR-TEAM-003** LT can set a team `active` / `inactive`; inactive teams are excluded from new meetings and the live table but retain history.
- **FR-TEAM-004** LT issues a captain login for a team (creates a `team_users` record linked to the team, flagged `must_set_password`).
- **FR-TEAM-005** LT can reset a team captain's password (admin-mediated).
- **FR-TEAM-006** A team captain can view their team profile and edit crest color and captain contact details only.
- **FR-TEAM-007** Team initials/short code auto-derive from the name if not supplied (e.g. "Digital Titans" → "DT").
- **FR-TEAM-008** LT sees an all-teams list with each team's current standing and submission status at a glance.

## Business rules
- **BR-TEAM-001** A team is never hard-deleted once it has scored meetings; only deactivated (preserves league history and audit).
- **BR-TEAM-002** Crest color should be unique enough to distinguish teams on the league table; LT is warned on duplicate color but not blocked.
- **BR-TEAM-003** Exactly one captain login per team in v1 (multiple captains deferred).

## Key screens / flows
All teams list (LT) · Create/edit team (LT) · Team detail drill-in (LT) · Team profile & crest settings (captain).
(Page inventory C7, C8, C15, B15.)

## Data touched
`teams`, `team_users`.

## Edge cases
- Deactivating a team mid-season → its already-approved scores stay on historical tables; it drops off the live standings.
- Renaming a team → historical references use the FK, so the rename propagates without breaking past meetings.

## Open questions / to clarify
- Can a team have more than one captain login? (Assumed no for v1 — confirm.)
- How many teams are expected in the league? (Affects table pagination defaults — not stated.)
