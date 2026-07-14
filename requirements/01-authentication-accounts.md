# 01 — Authentication & Accounts

## Purpose
Provide secure, role-separated access for two user types — team captains and Leadership Team (LT) — with robust account lifecycle, password, and session handling. This is a **priority foundation** (built deep in Phase 1) and a manual sign-off checkpoint.

## User stories
- As a **team captain**, I log in and land directly on my team's dashboard.
- As an **LT member**, I log in and land on the LT overview.
- As a user, if I forget my password I can request a reset link and set a new password.
- As a newly-issued account holder, I am forced to set my own password on first login.
- As LT, I can issue and reset team-captain credentials (see `02-team-management.md`).

## Functional requirements

- **FR-AUTH-001** The system supports two Laravel auth guards: `team` and `lt`, backed by separate user tables/models (`team_users`, `lt_users`) or a single users table with a role discriminator — decided in `plan/04-auth-user-management.md`.
- **FR-AUTH-002** A single login screen presents two tabs (Team / LT); the active tab selects the guard used for authentication.
- **FR-AUTH-003** On successful login, the user is redirected to their role-appropriate dashboard: team → team dashboard; LT → LT overview.
- **FR-AUTH-004** Failed login shows a non-enumerating error ("These credentials do not match our records") without revealing whether the username exists.
- **FR-AUTH-005** Passwords are stored using Laravel's default bcrypt/argon hashing; plaintext is never stored or logged.
- **FR-AUTH-006** A user can request a password reset by email/username; a signed, expiring token is emailed.
- **FR-AUTH-007** A reset-token page lets the user set a new password; tokens are single-use and expire (default 60 min).
- **FR-AUTH-008** A newly-created account is flagged `must_set_password`; first login forces a set-password step before any other action.
- **FR-AUTH-009** A logged-in user can change their own password (requires current password).
- **FR-AUTH-010** Sessions expire after a configurable idle timeout; an expired session shows a re-authenticate screen without losing the attempted destination.
- **FR-AUTH-011** Login is rate-limited (Laravel throttling) to mitigate brute force.
- **FR-AUTH-012** Logout invalidates the session and regenerates the CSRF token.
- **FR-AUTH-013** Every user has an account/profile page (name, contact, notification preference).

## Business rules
- **BR-AUTH-001** A team guard user can never access LT routes and vice versa; enforced server-side by guard + middleware, not by hiding UI.
- **BR-AUTH-002** Password minimum policy: ≥8 chars, at least one letter and one number (configurable in `plan/06-security.md`).
- **BR-AUTH-003** Password reset for team captains may also be initiated by LT (admin-mediated reset), matching the small-scale operational model.

## Key screens / flows
Login (tabbed) · Forgot password · Reset password · First-login set password · Change password · My account · Session expired.
(See `CLAUDE_DESIGN.md` section 4A and page inventory A1–A8.)

## Data touched
`team_users`, `lt_users` (or unified `users` + role), `password_reset_tokens`, `sessions`.

## Edge cases
- Reset requested for a non-existent account → generic success message (no enumeration).
- Expired/used reset token → clear "link expired, request a new one" message.
- User with `must_set_password` who navigates away → still forced back to set-password on next request.
- Concurrent sessions → allowed; password change does not force other sessions out in v1 (flag for revisit).

## Open questions / to clarify
- ~~Is login by **email**, **username**, or **team name + password**?~~ **RESOLVED 2026-07-11 (owner): login by EMAIL.** Every captain and LT member has a unique email on file, so self-serve email password reset (FR-AUTH-006) is enabled for all users (LT-mediated reset remains available per BR-AUTH-003). Unique `email` column on both `team_users` and `lt_users`.
- Should a password change invalidate other active sessions? (Deferred; revisit in security hardening.)
- Do LT members need 2FA given they control scoring rules? (Not discussed; listed as a possible phase-2 hardening.)
