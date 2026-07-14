# Plan — 07 Responsive & Design Application

Applies `design.md` (and `CLAUDE_DESIGN.md`) across the build. Enforced by CLAUDE.md rules 4–6.

## Principles
- **Light theme only, mobile-first.** Captains enter scores on a phone right after the meeting.
- Every page fully responsive across mobile / tablet / desktop.
- Reuse the custom component set (`design.md` §5); no one-off styling.

## Breakpoint behavior
- **< 680px:** single column; `AppShell` nav collapses to a bottom tab bar; entry rows stack; 44px min tap targets.
- **≥ 680px:** sidebar nav; multi-column dashboards; league table full width.
- Tailwind breakpoints (`sm/md/lg`) drive layout; test each page at 375px, 768px, 1280px.

## Signature components (build in Phase 0, reuse after)
LeagueTable, StatusPill, TeamCrest, MeetingTicket, CategoryAccordion, SubtypeRow, RosterChecklist, RunningTotal, NumberStepper, EmptyState, AppShell — all from `design.md` §5.

## Tokens
Colors/type/spacing defined once in `tailwind.config.js` + `app.css`; components consume tokens, never raw hex.

## Motion
Accordion 180ms, running-total pulse 200ms, status crossfade 250ms, league reorder 300ms; respect `prefers-reduced-motion` (`design.md` §8).

## Premium standard
Refined spacing/hierarchy/typography; shadows reserved for current-team row + modals; the league table is the one bold signature, everything else quiet around it.

## Per-page checklist (part of each sub-phase smoke test)
- [ ] Renders correctly at 375 / 768 / 1280px.
- [ ] Uses only design.md components/tokens.
- [ ] Status conveyed by color + label + icon.
- [ ] Visible focus states; keyboard reachable.
