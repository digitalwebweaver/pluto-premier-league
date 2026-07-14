# 12 — Public Display (Phase 2)

## Purpose
Read-only, no-login views of the standings suitable for sharing or showing on the meeting projector — a natural tie-in to the existing LVB Pluto presenter tool. **Phase 2.**

## User stories
- As LT, I share a public standings link or show it full-screen at a meeting.
- As a viewer, I see the current league table without logging in.

## Functional requirements
- **FR-PUB-001** A public league table page shows current standings (approved data only), no login required, no edit controls.
- **FR-PUB-002** A public season summary page shows the season grid and champion.
- **FR-PUB-003** A live standings display mode renders full-screen and auto-refreshes for projector use.
- **FR-PUB-004** Public pages expose no member-level personal data beyond names already shown on standings; no raw entry detail.
- **FR-PUB-005** Public access is via an unguessable/tokenized link or a deliberately public route, per LT preference.

## Business rules
- **BR-PUB-001** Public views are strictly read-only and show only approved, published data.
- **BR-PUB-002** No draft, submitted, or sent-back data is ever visible publicly.

## Key screens / flows
Public league table · Public season summary · Live full-screen standings.
(Page inventory E1, E2, E3.)

## Data touched
Read-only approved standings aggregation.

## Edge cases
- Season not yet started → public table shows teams at 0 with a "season starting soon" note.

## Open questions / to clarify
- Fully public URL vs tokenized share link? (Assumed tokenized/publishable per LT choice.)
- Should the live display reuse the presenter tool's visual theme directly? (Design already shares tokens; confirm depth of reuse.)
