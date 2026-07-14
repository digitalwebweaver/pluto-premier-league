# Plan — 01 Architecture

## Style
A **Laravel monolith with an Inertia-driven Vue frontend**. No separate REST API in v1: Laravel controllers return `Inertia::render()` responses with props; Vue pages consume them. This keeps auth server-driven (session guards, CSRF) and eliminates token/state juggling.

## Request flow
```
Browser (Vue page)
   │  Inertia visit (XHR w/ CSRF)
   ▼
routes/web.php ─▶ middleware (guard: team|lt, policy) ─▶ Controller
   │                                                         │
   │                                     Form Request validation
   │                                     Service (scoring/approval)
   │                                     Eloquent ─▶ MariaDB
   ▼
Inertia::render('Page', props) ─▶ Vue renders
```

## Layering
- **Controllers** — thin; delegate to services for scoring/approval logic.
- **Form Requests** — all validation + authorization gate.
- **Services** — `ScoringService` (compute points from rules), `ApprovalService` (state machine), `StandingsService` (aggregate table). Keeps business rules out of controllers and testable.
- **Policies** — per-model authorization (TeamPolicy, MeetingEntryPolicy, ScoringRulePolicy…).
- **Eloquent models** — relationships + query scopes (e.g. `scopeApproved`, `scopeForTeam`).

## Guards & routing split
- `routes/web.php` groups: `guest`, `team` (captain area), `lt` (leadership area), and (P2) `public` standings.
- After login, a redirect resolver sends each guard to its dashboard (`FR-AUTH-003`).

## Frontend structure
```
resources/js/
  Pages/            # Inertia pages, mirrors the page inventory
    Auth/           # login, reset, set-password
    Team/           # dashboard, submit, entry, roster, ...
    LT/             # overview, queue, review, rules, meetings, ...
  Components/       # custom Tailwind components from design.md
    AppShell.vue  StatusPill.vue  TeamCrest.vue  LeagueTable.vue
    MeetingTicket.vue  CategoryAccordion.vue  SubtypeRow.vue
    RosterChecklist.vue  RunningTotal.vue  NumberStepper.vue  EmptyState.vue
  Layouts/          # persistent AppShell layout
  composables/      # useRunningTotal, useScoringRules, ...
```

## Scoring authority
The Vue running total is **display only**. `ScoringService` recomputes authoritatively on save, submit, and approve (`FR-SCO-011`). Approved entries **snapshot** their points so later rule edits don't rewrite history (`BR-SCO-003`).

## Key decisions (for future sessions)
- **Inertia over API** — chosen for speed and server-driven auth; a Sanctum API can be added later *alongside* for a mobile app without rewriting the web app.
- **Services layer** — scoring and approval are the two areas most likely to change/have bugs; isolating them makes them unit-testable independent of HTTP.
- **Snapshotting approved points** — protects league integrity when rules evolve mid-season.

## Open decisions / revisit later
- Introduce `standings_snapshots` for movement history now or compute on the fly? (Start on-the-fly; add snapshots if perf/movement accuracy needs it.)
- Queue/jobs for notifications (P2) — likely Laravel queue with database driver.
