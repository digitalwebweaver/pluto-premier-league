# Plan — 08 Testing & Smoke-Test Strategy

## Layers
- **Unit** (PHPUnit) — `ScoringService` (all five input shapes + edge cases), `ApprovalService` (state machine transitions), `StandingsService` (ranking, movement, tiebreak).
- **Feature/integration** (Laravel HTTP tests) — auth redirects per role, guard isolation (team→LT = 403), entry create/draft/submit, approve/send-back/unlock, standings recompute on approval.
- **Smoke test** (mandatory per sub-phase) — app builds and starts (`php artisan serve`, `npm run dev` / `npm run build`), key routes return expected status, feature happy-path works end-to-end.

## Mandatory smoke-test rule
A sub-phase is **not done** until its smoke test passes, and the result is recorded in `SESSION-LOG.md` (CLAUDE.md rule 8).

## Example acceptance/smoke assertions
- `POST /login` (team) → 302 to `team.dashboard`; (lt) → 302 to `lt.overview`.
- `GET /lt/queue` as team guard → 403.
- Create entry, add a Visitors line (Hot ×1) → server total = 300 (not client-trusted).
- Attendance with 2 absent → category points = −400; 0 absent → +300.
- Trainings whole-team present → per-member 100.
- Approve entry → appears in standings; unlock → standings recompute.
- Approved entry rule edit → approved total unchanged (snapshot honored).

## Test data
Seeders provide: one season, a few teams with rosters, the default category/rule set, and one open meeting — enough to smoke every flow.

## CI (optional, phase 0)
A lightweight GitHub Actions workflow running `composer install`, `php artisan test`, `npm ci && npm run build`. If CI isn't set up initially, smoke tests are run locally and logged.

## Open decisions / revisit later
- Browser E2E (Dusk/Playwright) — deferred; manual E2E logged in SESSION-LOG for now.
