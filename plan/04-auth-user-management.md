# Plan — 04 Auth & User Management (Priority Foundation)

This is the deep, early foundation and a **manual sign-off gate** (Phase 1). Later phases do not begin until the owner has manually tested and approved this.

## Guard strategy
Two guards: `team` and `lt`.

**Chosen approach (default):** split tables `lt_users` and `team_users`, each with its own guard + provider in `config/auth.php`. Rationale: clean separation, team users always carry `team_id`, no role-confusion in queries.

**Alternative considered:** single `users` table + `role` enum + nullable `team_id`. Simpler migrations but every query must remember to filter role. Documented here so a future session doesn't "discover" it as new scope. If switched, update `02-data-model-schema.md`.

## RBAC
- Guard determines area (team vs LT). Within each, **Policies** gate per-model actions.
- Team queries are always scoped by authenticated `team_id` via a global-ish scope or explicit `->forTeam(auth)` — enforced server-side (`FR-ROLE-002`).
- Forbidden actions return 403 (`FR-ROLE-003`).

## Account lifecycle
1. LT creates a team → issues a `team_user` with `must_set_password = true` and a set-password link/temp credential.
2. First login forces set-password before anything else (`FR-AUTH-008`).
3. Password reset: self-serve (email token) or LT-mediated (`FR-AUTH-006`, `BR-AUTH-003`).
4. Change password requires current password (`FR-AUTH-009`).
5. Deactivation: disabling a team/member preserves history; login can be disabled without deletion.

## Session handling
- Laravel session guard, database or file driver.
- Idle timeout configurable; expired session → re-auth screen preserving intended URL (`FR-AUTH-010`).
- Logout invalidates session + regenerates CSRF (`FR-AUTH-012`).
- Login throttling (`FR-AUTH-011`).

## Post-login redirect
A single resolver maps guard → dashboard route: `team` → `team.dashboard`, `lt` → `lt.overview` (`FR-AUTH-003`). Covered by an integration test asserting each role lands correctly.

## Security basics (see `06-security.md`)
Hashing (bcrypt/argon2), no enumeration on login/reset, CSRF on, rate limiting.

## Phase-1 sign-off checkpoint
Phase 1 ends with an explicit **owner manual-test gate**:
- Team login → team dashboard; LT login → LT overview.
- Wrong-guard access blocked (team → LT route = 403).
- Reset + set-password + change-password flows work.
- Session expiry + re-auth works.
Only after owner sign-off does Phase 2 begin.

## Open decisions / revisit later
- Unified vs split user tables (default: split).
- 2FA for LT (given they control scoring rules) — possible P2 hardening.
- Session invalidation on password change — deferred.
