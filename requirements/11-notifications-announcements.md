# 11 — Notifications & Announcements (Phase 2)

## Purpose
Keep teams informed of workflow events (approved, sent back, new meeting opened) and let LT broadcast messages. **Phase 2.**

## User stories
- As a **captain**, I'm notified when my submission is approved or sent back, and when a new meeting opens.
- As **LT**, I can broadcast an announcement to all teams (e.g. "Meeting 6 now open").

## Functional requirements
- **FR-NOT-001** Teams receive in-app notifications for: submission approved, submission sent back (with note), new meeting opened.
- **FR-NOT-002** A notifications screen lists a team's notifications, newest first; viewed items clear.
- **FR-NOT-003** LT can post an announcement broadcast to all active teams.
- **FR-NOT-004** Notification preferences (at least on/off) live on the account page.
- **FR-NOT-005** (Optional) email notification mirrors in-app for key events, using the same mail transport as password resets.

## Business rules
- **BR-NOT-001** Send-back notifications always include LT's note.
- **BR-NOT-002** Notifications are scoped to the recipient team (no cross-team leakage).

## Key screens / flows
Team notifications · LT announcements/broadcast composer.
(Page inventory B16, C24.)

## Data touched
`notifications`, `announcements`.

## Edge cases
- Announcement to a team that later deactivates → retained in history, not shown live.

## Open questions / to clarify
- In-app only, or email too, for v1 of this phase? (Assumed in-app first; email optional.)
