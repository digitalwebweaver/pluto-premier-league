# Plan — 10 Phase Roadmap

## Working rule
Top-to-bottom, **sub-phase by sub-phase**. Do not start the next phase until the current phase's sub-phases are functionally complete and their smoke tests pass. Each sub-phase = one working session producing a working, testable increment. After each sub-phase, update all three `dev/` files.

## Cross-cutting rules (apply to every sub-phase)
Architecture-first · schema/code column sync · security · design consistency · premium design · responsive (light, mobile-first) · scope awareness · smoke-test before done. (Full text in `CLAUDE.md`.)

## Phases

### Phase 0 — Foundation (`phases/phase-0-foundation.md`)
Solid architecture + scaffold before features: Laravel 11 + Inertia + Vue 3 + Tailwind wired; MariaDB connected; design tokens + core components; base layout/AppShell; seeders skeleton; optional CI. **Implements:** architecture/design groundwork underpinning all FRs.

### Phase 1 — Auth & User Management (`phases/phase-1-auth-user-management.md`) — PRIORITY + GATE
Two guards, login (tabbed), role dashboards, account lifecycle, password/session handling, RBAC scaffolding, LT/team login management. **Implements:** `FR-AUTH-*`, `FR-ROLE-*`, `14`. **Ends with an explicit owner manual-test sign-off checkpoint before Phase 2.**

### Phase 2 — Core Domain (`phases/phase-2-core-domain.md`)
Teams, members/roster, meetings (lifecycle + applicable categories), and the scoring-rules engine (categories, subtypes, seed, LT admin screens). **Implements:** `FR-TEAM-*`, `FR-MBR-*`, `FR-MTG-*`, `FR-SCO-*`.

### Phase 3 — Score Entry (`phases/phase-3-score-entry.md`)
Matchday scorecard: applicable categories, subtype rows, attendance checklist, running total, draft + submit; authoritative server compute. **Implements:** `FR-ENT-*`, `FR-SCO-011`.

### Phase 4 — Approval Workflow (`phases/phase-4-approval-workflow.md`)
LT queue, review, approve/lock/snapshot, send-back with note, unlock; status history/audit. **Implements:** `FR-APR-*`.

### Phase 5 — Standings & Season (`phases/phase-5-standings-season.md`)
League table (movement, meeting dots, rings), embedded mini-table, season summary grid + champion; recompute on approve/unlock. **Implements:** `FR-LGT-*`, `FR-SSN-*`.

### Phase 6 — Phase 2 Extras (`phases/phase-6-reports-notifications-public.md`)
Reports + individual leaderboards, notifications + announcements, public/projector display. **Implements:** `FR-RPT-*`, `FR-NOT-*`, `FR-PUB-*`.

## Sequencing dependencies
- Phase 1 gates everything (foundation + sign-off).
- Phase 2 domain objects must exist before Phase 3 entries can reference them.
- Phase 3 entries must exist before Phase 4 approval.
- Phase 4 approval must exist before Phase 5 standings are meaningful (only approved counts).
- Phase 6 is additive; depends on 3–5 data.

## MVP line
Phases 0–5 = runnable first season (~28–30 pages). Phase 6 is Phase-2 scope.

## Open decisions / revisit later
- Unified vs split user tables (`04-auth-user-management.md`).
- `standings_snapshots` now vs later.
- Confirm TYFCB / Golden Mic / Abiding Theme exact point values with LT.
- Tiebreak rule confirmation (`08`).
- Auto-close meetings vs manual (`04`).
