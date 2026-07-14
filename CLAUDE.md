# CLAUDE.md — Pluto Premier League

Multi-team meeting scoring platform for LVB Pluto's **Pluto Premier League**: teams log their fortnightly meeting performance across ~17 scoring categories; a Leadership Team (LT) reviews and approves each submission before it counts on the league table.

**Stack:** Laravel 11 (PHP 8.2+) · Vue 3 (Composition API) + Inertia.js · Tailwind CSS (custom components, no UI library) · MariaDB (local via phpMyAdmin, Laravel `mysql` driver) · Vite. Two auth guards: `team` (captains) and `lt` (leadership).

## Start here
👉 **Read `dev/CURRENT-STATUS.md` first** — it tells you the current phase/sub-phase and how to resume.

## Authoritative requirements
`requirements/` is the source of truth for WHAT to build. Every acceptance criterion in the phase docs references a requirement ID (e.g. `FR-SCO-004`). Never invent scope — if something is unspecified, add it to "Open questions" in the relevant file, don't build it.

## Design source of truth
`design.md` (repo root) defines tokens, layout, and component conventions. It is derived from the fuller design system in `CLAUDE_DESIGN.md`. Every screen must follow `design.md` — reuse components, no one-off styling.

## Working rule
Work **top-to-bottom, sub-phase by sub-phase**. Do NOT start the next phase until the current phase's sub-phases are functionally complete and their smoke tests pass. Each sub-phase is one working session and must produce a working, testable increment.

## Update rule
After **every** sub-phase, update all three `dev/` files: mark status in `PROGRESS.md`, refresh `CURRENT-STATUS.md`, and append a new entry to the top of `SESSION-LOG.md` (including smoke-test results).

---

## Cross-cutting rules (enforce on every sub-phase)

1. **Architecture-first.** Establish solid system architecture in Phase 0 before any feature work.
2. **Schema/code column sync.** Verify column names against the real schema (migrations/DB) before writing models, queries, or Inertia props. Never guess a column name — prevents "unknown column" errors.
3. **Security.** Input validation (Form Requests), Eloquent/query-builder bindings only (no raw string-interpolated SQL), Blade/Vue output escaping (XSS), server-side authz on every route (Policies/middleware, never trust the client), secrets in `.env` only, safe file handling for uploads. Consistent with `requirements/13-non-functional.md`.
4. **Design consistency.** Every screen follows `design.md` and reuses existing custom components — no one-off styling.
5. **Premium design standard.** Refined spacing, hierarchy, typography, interaction detail. Never a basic/templated look.
6. **Responsive everywhere.** Every page fully responsive across mobile, tablet, desktop. **Light theme, mobile-first** (captains enter scores on a phone right after the meeting).
7. **Scope awareness.** Build only what the current sub-phase specifies. Anything extra goes to "Open decisions / revisit later" — never build unprompted.
8. **Smoke-test after every feature.** Before marking a sub-phase done: confirm the app builds and starts (`php artisan serve`, `npm run dev`), key routes load without errors, and the feature's happy-path works end-to-end. Record the smoke-test result in `SESSION-LOG.md`. A sub-phase is not done until its smoke test passes.

## Special foundation gate
**User management & auth is a priority foundation and a review checkpoint.** It is built deep and early (Phase 1), with robust RBAC, account lifecycle, and password/session handling. Every user is redirected to their role-appropriate dashboard after login. Phase 1 completion is an **explicit checkpoint awaiting the owner's manual testing and sign-off** before later phases begin.

## Scoring engine note
Point values are **never hardcoded**. They live in a `scoring_rules` table (category → subtype → points), LT-managed. All entry forms read subtypes live from this table. Special-case math (attendance flat/penalty, punctuality, training doubling, TYFCB amount) is documented in `plan/05-scoring-engine-design.md` and `requirements/05-scoring-engine.md`.
