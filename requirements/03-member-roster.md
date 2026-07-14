# 03 — Member Roster

## Purpose
Each team maintains a roster of its members. Members are the subjects of most scoring entries (who invited a visitor, who gave a referral) and of attendance/punctuality. Member-level data enables individual recognition later.

## User stories
- As a **team captain**, I add my members with their name and business category.
- As a **captain**, I mark a member inactive when they leave, without losing their historical contributions.
- As **LT**, I can view any team's roster when reviewing a submission.
- As a **captain**, I can view a member's season contribution history.

## Functional requirements
- **FR-MBR-001** A captain can add a member with: full name, business/professional category (free text or picklist), optional photo and crest color for avatars.
- **FR-MBR-002** A captain can edit a member's details.
- **FR-MBR-003** A captain can set a member `active` / `inactive`; inactive members do not appear in new meeting entry forms but remain in history.
- **FR-MBR-004** Members belong to exactly one team (scoped by `team_id`).
- **FR-MBR-005** Member dropdowns in the entry form list only active members of the captain's own team.
- **FR-MBR-006** A member detail view shows that member's per-category, per-meeting contribution across the season.
- **FR-MBR-007** Initials for avatars auto-derive from the member name.

## Business rules
- **BR-MBR-001** A member with historical entries is never hard-deleted, only deactivated.
- **BR-MBR-002** Attendance/punctuality checklists include all active members of the team for the meeting being scored.
- **BR-MBR-003** A captain can only ever see/edit members of their own team (server-side enforced).

## Key screens / flows
My roster list · Add/edit member · Member detail & scoring history.
(Page inventory B10, B11, B12.)

## Data touched
`members` (belongs to `teams`).

## Edge cases
- A member added mid-season → appears only from the meeting they were added onward; earlier meetings unaffected.
- Deactivating a member who is set as a featured/relevant subject in a draft → handled gracefully (draft retains them; new forms exclude them).

## Open questions / to clarify
- Is business category a free-text field or a controlled picklist? (Assumed free text v1; picklist deferred.)
- Is there a per-team member cap tied to league rules? (Not discussed.)
