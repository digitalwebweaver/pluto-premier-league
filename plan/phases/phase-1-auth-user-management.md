# Phase 1 — Auth & User Management (Priority Foundation + Sign-off Gate)

Goal: deep, strong authentication, RBAC, and account lifecycle. **Phase 2 does not begin until the owner manually tests and signs off Phase 1.**

---

## 1A — Guards, models & migrations
**Goal:** Two auth guards with their user tables.
**Deliverables:** `lt_users` + `team_users` migrations/models (or unified users per `plan/04` decision); `config/auth.php` guards `team` + `lt`; factories/seeders for one LT user and one team captain.
**Implements:** `FR-AUTH-001`, `14`.
**Key decisions:** split-table guards (default) — team users carry `team_id`.
**Acceptance criteria:**
- Migrations run; seeded LT + team users exist.
- `php artisan test` covers model + guard config.
- **Smoke test:** migrate+seed clean, guards resolve — SESSION-LOG.

## 1B — Login (tabbed) & role redirect
**Goal:** One login screen, two tabs, correct dashboard redirect.
**Deliverables:** login page (Team/LT tabs) per design; auth controllers per guard; post-login resolver → `team.dashboard` / `lt.overview`; non-enumerating errors; throttling.
**Implements:** `FR-AUTH-002`, `FR-AUTH-003`, `FR-AUTH-004`, `FR-AUTH-011`.
**Acceptance criteria:**
- Team login → 302 `team.dashboard`; LT login → 302 `lt.overview` (integration test).
- Bad credentials show generic error; no user enumeration.
- Throttle triggers after N attempts.
- **Smoke test:** both logins land on correct dashboards — SESSION-LOG.

## 1C — Guard isolation & RBAC enforcement
**Goal:** Server-side authorization boundary.
**Deliverables:** route groups per guard; middleware/Policies; team-scoping helper (`forTeam(auth)`); 403 on cross-guard/forbidden.
**Implements:** `FR-ROLE-001`–`FR-ROLE-003`, `NFR-SEC-004`, `NFR-SEC-009`, `BR-AUTH-001`.
**Acceptance criteria:**
- `GET /lt/queue` as team guard → 403 (test).
- `GET /team/...` as LT guard → 403 (test).
- **Smoke test:** cross-guard access blocked — SESSION-LOG.

## 1D — Account lifecycle (reset, set-password, change)
**Goal:** Full credential lifecycle.
**Deliverables:** forgot-password (email token), reset page, forced first-login set-password (`must_set_password`), change-password (requires current); mail transport configured.
**Implements:** `FR-AUTH-005`–`FR-AUTH-009`, `BR-AUTH-002`, `BR-AUTH-003`.
**Acceptance criteria:**
- Reset token flow sets a new password; token single-use + expiring.
- New account forced through set-password before any other route.
- Change-password rejects wrong current password.
- **Smoke test:** all three flows work end-to-end — SESSION-LOG.

## 1E — Session handling & account page
**Goal:** Session robustness + self-service account.
**Deliverables:** idle timeout + re-auth screen preserving intended URL; logout invalidation + CSRF regen; account/profile page (name, contact, notification pref).
**Implements:** `FR-AUTH-010`, `FR-AUTH-012`, `FR-AUTH-013`.
**Acceptance criteria:**
- Expired session → re-auth screen, then returns to intended page.
- Logout invalidates session.
- Account page saves profile fields.
- **Smoke test:** session expiry + logout verified — SESSION-LOG.

## 1F — LT & team login management screens
**Goal:** LT can issue/reset logins.
**Deliverables:** LT screen to create team (stub) + issue captain login with `must_set_password`; reset a captain's password; manage LT logins.
**Implements:** `FR-TEAM-004`, `FR-TEAM-005`, `FR-AUTH-008`; `14` matrix.
**Acceptance criteria:**
- LT issues a captain login → captain can complete set-password and log in.
- LT resets a captain password successfully.
- **Smoke test:** issue + first-login + reset verified — SESSION-LOG.

---

## Phase 1 Exit Checklist
- [x] 1A guards/models/migrations + seeds
- [x] 1B tabbed login + correct role redirects (tested)
- [x] 1C guard isolation 403s (tested)
- [x] 1D reset / set-password / change-password
- [x] 1E session expiry/re-auth + account page
- [x] 1F LT issue/reset logins
- [x] All smoke tests recorded in SESSION-LOG
- [ ] **OWNER MANUAL-TEST SIGN-OFF CHECKPOINT** — owner confirms auth/RBAC before Phase 2 (record sign-off in SESSION-LOG + CURRENT-STATUS) — *awaiting owner; see CURRENT-STATUS for the test script*
