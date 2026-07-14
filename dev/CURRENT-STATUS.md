# CURRENT STATUS — read this first

**Last updated:** 2026-07-11
**Current phase/sub-phase:** **Phase 0 ✅ · Phase 1 ✅ (owner authorized proceeding 2026-07-11).** **Phase 0 ✅ · Phase 1 ✅ · Phase 2 ✅.** **Phase 0 ✅ · Phase 1 ✅ · Phase 2 ✅ · Phase 3 ✅.** **🏁 ENTIRE BUILD COMPLETE — Phases 0–6 all ✅.** The full Pluto Premier League product is built, tested (195 passing), and on-brand. Remaining work is optional polish + owner acceptance testing.

## Where things stand
- Planning scaffold fully written (requirements/plan/design/dev).
- **Phase 0A complete:** Laravel 11 + Inertia (server + Vue 3 client) + Vite + Tailwind 3.4 + Ziggy, on local `pluto_league` MariaDB. `AppServiceProvider` sets `defaultStringLength(191)`; `HandleInertiaRequests` on the web group shares `appName` + `flash`; `/` renders `Welcome.vue`.
- **Phase 0B complete:** all `design.md` tokens in `tailwind.config.js` (palette, fonts, radius, the 2 shadows, tap targets), fonts via bunny.net, base CSS layer (paper bg / reduced-motion). Core components built: `StatusPill`, `TeamCrest`, `NumberStepper`, `EmptyState`, `AppButton`. `/design` sandbox route previews them; verified in-browser at 375/768/1280, 0 errors.
- **Phase 0C complete:** persistent `Layouts/AppLayout.vue` (ink top bar + role switch + identity; sidebar ≥680px / bottom-tab <680px via the `nav:` breakpoint); shared nav config `resources/js/nav.js`; placeholder `/team` + `/lt` dashboards and wired sub-routes (`Placeholder.vue`); on-brand `Error.vue` (403/404/419/429/500/503) wired via `bootstrap/app.php` `withExceptions` for production. Active nav follows Inertia visits; verified responsive + on-brand in-browser.
- **Inertia is v3** (server `inertia-laravel v3.1.1` + client `@inertiajs/vue3 v3.6.1`) — both majors MUST stay aligned or pages mount blank. See `dev/SESSION-LOG.md` 0B entry.
- Stack locked: **Laravel 11 · Vue 3 + Inertia · Tailwind (custom components) · MariaDB · Vite**; guards `team` + `lt` (Phase 1).
- Design source of truth: `design.md`.

## Environment (local dev machine)
- XAMPP: PHP 8.2.12, MariaDB on `127.0.0.1:3306`, all needed extensions (`pdo_mysql`, `gd`, `mbstring`, `zip`, …).
- DB `pluto_league` created; credentials **root / `Dww@123`** (in `.env`, NOT in `.env.example`).
- Node v22.16, npm 10.5, Composer 2.7.4.

## Auth foundation so far (Phase 1)
- **1A done:** split guards — `team`→`team_users`/`TeamUser`, `lt`→`lt_users`/`LtUser` (email login, hashed, bool casts). `config/auth.php` guards/providers/brokers; default guard `team`. Default `users` table/model removed; `sessions` + `password_reset_tokens` kept.
- **1B done:** tabbed `/login` (Team/LT), `LoginRequest` (non-enumerating + 5-attempt throttle), `AuthenticatedSessionController` → `redirect()->intended(dashboard)`, `guest` middleware, `POST /logout`. `Guards` support class holds the guard→dashboard map. Inertia shares `auth.guard` + slim `auth.user`. **You can now actually log in.**
- **1C done:** `EnsureGuard` middleware (`guard` alias) protects `/team*` (`guard:team`), `/lt*` (`guard:lt`), `/league` (`guard:team,lt`). Cross-guard access → **403**; guest → login redirect. `BelongsToTeam` scoping trait groundwork for Phase 2.
- **1D done:** forgot/reset by email token (guard-aware reset URL, non-enumerating, single-use), forced first-login **set-password** (`EnsurePasswordIsSet`), **change-password** (`/settings/password`). Policy ≥8+letter+number. Mail = `log` locally.
- **1E done:** session re-auth preserves the intended URL; **account page** `/account`; AppLayout shows the **real** signed-in identity.
- **1F done:** LT `/lt/logins` — issue/reset captain + LT logins (temp password relayed via a one-time banner; `must_set_password` forces set on first login). Unique-email per table. Captains get **403**.

## Phase 2 so far
- **2A done:** `teams` + `team_users.team_id` FK; LT `/lt/teams` CRUD; captain `/team/profile` (crest only). 8 teams seeded (**dynamic/DB-backed — LT-managed, not hardcoded**); `captain@pluto.local` → Apex Alliance.
- **2B done:** `members` table + `Member` model (`BelongsToTeam`); captain `/team/roster` CRUD (own-team only, cross-team → 403). Apex Alliance seeded 8 members.
- **2C done:** `seasons` + `meetings` (scheduled|open|closed). `Season::current()`, `Meeting::isOpen()`/`scopeOpen`. LT `/lt/meetings` create + open/close. Seeded Season 4 + 7 meetings.
- **2D done:** `categories` (18, 5 input_shapes) + `meeting_categories` pivot; LT `/lt/categories` + per-meeting selection.
- **2E done:** `scoring_rules` (points + `extra_params` json) — DB-backed, LT-editable at `/lt/scoring`. **`ScoringService`** computes all 5 shapes (unit-tested, no hardcoded values). Seeded 26 default rules from requirements/05. ⚠️ TYFCB/Golden Mic/Abiding-Theme values are seeded defaults (250/200/200) — **confirm real values with LT** (editable in-app). Specific Ask = clean ×200 (stray ×76 not reproduced).

## Phase 3 so far
- **3A done:** entry tables/models + create-or-load single draft + Submit list + scorecard shell.
- **3B done:** interactive count/amount accordion + `saveDraft` persists `entry_lines`, recomputes server-side.
- **3C done:** `RosterChecklist` Attendance/Punctuality → `entry_attendance` (flat/penalty by `metric`).
- **3D done:** binary toggle + Trainings multiplier UI, pre-submit **review** (`Team/Review.vue` — server-recomputed breakdown), **authoritative submit** (`EntryScoringService::recompute`; sets `submitted`+`submitted_at`; ≥1 attendance mark required; client total ignored). `EntryScoringService` now covers all 5 shapes + a `breakdown()`.

- **4A done:** `ApprovalService` (submit/approve/sendBack/unlock, validated + audited + snapshot-on-approve). Snapshot immutability tested.
- **4B done:** LT `/lt/queue` + read-only review + live `pendingApprovals` badge.
- **4C done:** Approve (locks+snapshot; team edit → 403) + Send back (required note) + team resubmit.
- **4D done:** `/lt/recent` (recently-approved) + guarded **Unlock** modal (approved→submitted, back to queue) + **stale-version guard** on approve.

- **5A done:** `StandingsService` + signature **League Table** (`/league`) + dashboard hero. Tiebreak = total → meetings-approved → alphabetical.
- **5B done:** `seasonGrid()` + **Season summary** (`/season`, shared) — teams × meetings, pending "—" (not 0), champion/leader emphasis. All Placeholder routes now real.

## 🎉 MVP is complete — the whole loop works end-to-end
Auth (team + LT guards) → LT manages teams / rosters / meetings / categories / **scoring rules (DB-configurable)** → captains fill & submit the **scorecard** (all 5 input shapes, server-authoritative) → LT **queue → review → approve (lock+snapshot) / send-back / unlock** → **League Table + Season summary** compute live from approved data. **172 tests, 641 assertions, all green.** Seeded data makes every screen render populated.

- **6A done:** `ReportService` + LT `/lt/reports` hub, category leaders, per-team report.
- **6B done:** `mvpLeaderboard()` + `/lt/reports/mvp`.
- **6C done:** `ExportController` — CSV downloads of every report + buttons.
- **6D done:** `notifications`+`announcements`; fires on approve/send-back/new-meeting; LT broadcast; captain bell badge.
- **6E done:** public no-login `/public/league`, `/public/season`, `/public/live` (projector, auto-refresh) — approved-only, no private-detail leakage.

## Test totals
`php artisan test` → **195 passing (758 assertions)**, all green.

## 🏁 The build is complete (Phases 0–6)
| Phase | What it delivers |
|---|---|
| 0 Foundation | Laravel 11 + Inertia v3 + Vue 3 + Tailwind on MariaDB; design tokens + component library; responsive AppShell; error pages; test harness |
| 1 Auth | Two guards (team/lt), tabbed login + redirect, reset/set/change password, session re-auth, LT login management — **owner-signed-off** |
| 2 Core domain | Teams, rosters, seasons/meetings, categories, **DB-configurable scoring rules** + `ScoringService` (5 shapes, unit-tested) |
| 3 Score entry | The scorecard — all 5 input shapes, live totals, review, server-authoritative submit |
| 4 Approval | Queue → review → approve (lock+snapshot) / send-back / unlock, audited + stale-guarded |
| 5 Standings | Signature League Table + Season summary from approved data |
| 6 Reports+ | Reports hub, MVP leaderboard, CSV export, notifications/announcements, public projector display |

## Optional follow-ups (not built — flagged deferrals)
PDF export (needs dompdf) · email notifications (respect `notification_pref`) · LT "mark season complete" toggle · photo-evidence upload on Team/Joint Meeting · scorecard autosave · public share-token gating · LT all-teams standing column.

## Owner acceptance testing
`php artisan serve --port=8000` + `npm run dev`. Logins (all `Password123`): LT `leadership@pluto.local` · captain `captain@pluto.local` (Apex Alliance) · first-login `firstlogin@pluto.local`. Public views need no login: `/public/league`, `/public/live`. Seed = Season 4, 7 meetings (6 open), 8 teams w/ approved history + a per-category breakdown, 18 categories, 26 scoring rules, Apex member lines for the MVP board.

## Owner testing (MVP)
`php artisan serve --port=8000` + `npm run dev`. Logins (all `Password123`): LT `leadership@pluto.local` · captain `captain@pluto.local` (Apex Alliance, 8 members) · first-login `firstlogin@pluto.local`. Seeded: Season 4 + 7 meetings (6 open), 8 teams w/ approved history, 18 categories + 26 scoring rules. Try: captain submits meeting 6 → LT approves/sends-back → watch `/league` + `/season` update.

## Owner testing reference (Phase 1 auth — still valid to spot-check anytime)
Run `php artisan serve --port=8000` + `npm run dev`, then `http://127.0.0.1:8000/login`.
Seeded accounts (all password **`Password123`**): LT `leadership@pluto.local` · captain `captain@pluto.local` · first-login captain `firstlogin@pluto.local`.
Auth checklist:
1. **Login + redirect** — captain login → `/team` dashboard; LT login (Leadership tab) → `/lt` overview.
2. **Cross-guard 403** — while logged in as a captain, visit `/lt/queue` → Forbidden; as LT, visit `/team/submit` → Forbidden.
3. **Bad credentials** — wrong password → generic "These credentials do not match our records" (no hint whether the email exists).
4. **Forgot/reset** — `/login` → Forgot password → submit an email → generic confirmation. (Mail is logged to `storage/logs/laravel.log`; the reset link is in there.) Open the link → set a new password → log in with it.
5. **Forced first login** — log in as `firstlogin@pluto.local` → you're forced to set a password before anything else → then land on the dashboard.
6. **Change password** — top-bar identity → **My account** → Change password (needs current).
7. **Session/re-auth** — sign out, then open a deep link like `/team/roster` → bounced to login → after signing in you return to that page.
8. **LT login management** — as LT, sidebar **Logins** → issue a captain login → relay the shown temp password → that captain logs in and is forced to set their own password. Also try **Reset**.

**What's next (after sign-off):** Phase 2A — Teams (`plan/phases/phase-2-core-domain.md`). Then 2B members/roster, 2C seasons/meetings, 2D categories, 2E scoring rules. Phase 2A also adds the real `teams` table + the deferred `team_users.team_id` FK and `TeamUser::team()` relationship.

## Test harness notes
- Tests run against MariaDB **`pluto_league_test`** (isolated from the dev `pluto_league`). Config in `phpunit.xml`.
- `php artisan test` currently: 7 passing (2 default examples + 5 route smoke tests using Inertia assertions).
- Optional CI at `.github/workflows/ci.yml` — inert until the repo is pushed to GitHub (not yet a git repo).

## Verification commands
```bash
# from repo root (d:\FinalProducts\Projects\ppl)
php artisan serve --port=8000     # backend → http://127.0.0.1:8000
npm run dev                       # Vite dev server (HMR)
php artisan migrate               # against pluto_league MariaDB
php artisan test                  # (harness lands in 0D)
npm run build                     # production asset smoke
```

## How to resume a session
1. Read this file, then `dev/PROGRESS.md` for the next ⬜/🟨 sub-phase.
2. Open that sub-phase's spec in `plan/phases/phase-N-*.md`.
3. Check the requirement IDs it implements in `requirements/`.
4. Confirm design against `design.md` before building any screen.
5. Verify DB column names against real migrations before writing models/queries (CLAUDE.md rule 2).
6. Build only that sub-phase's scope. Run its smoke test.
7. On pass: mark ✅ in PROGRESS, update this file, append SESSION-LOG (newest on top) with smoke-test results.

## Resolved decisions (owner)
- **Login identifier: EMAIL** (confirmed 2026-07-11). Unique `email` on both `team_users` and `lt_users`. Every user has a unique email → self-serve email reset enabled for all; LT-mediated reset also available.

## Open decisions / things to revisit
- Unified `users` vs split `lt_users`/`team_users` (default: **split** — proceeding on this) — `plan/04`.
- Confirm exact point values with LT: TYFCB formula, Golden Mic, Abiding Theme — `requirements/05`.
- League tiebreak rule — `requirements/08`.
- Meeting auto-close vs manual — `requirements/04`.
- `standings_snapshots` now vs later — `plan/01`/`02`.
- Login identifier: username vs email vs team-name — `requirements/01`.
