# Plan — 06 Security

Maps controls to `requirements/13` NFR-SEC. Enforced on every sub-phase (CLAUDE.md rule 3).

## Authentication & sessions
- Bcrypt/argon2 hashing (Laravel default); never log plaintext (`NFR-SEC-006`).
- Non-enumerating login/reset errors (`FR-AUTH-004`).
- Login + reset rate-limited via Laravel throttling (`NFR-SEC-007`).
- CSRF on all state-changing requests (`NFR-SEC-005`); logout regenerates token.

## Authorization
- Two guards + Policies/middleware on **every** route; server-side only, UI hiding is never the boundary (`NFR-SEC-004`, `BR-AUTH-001`).
- Team queries always scoped to authenticated `team_id`; cross-team access impossible (`NFR-SEC-009`).
- Forbidden = 403, not silent no-op (`FR-ROLE-003`).

## Input & output
- All input via Form Requests with explicit rules (`NFR-SEC-001`); counts non-negative, member∈team, subtype∈category.
- **Point totals recomputed server-side** — client total never trusted (`FR-SCO-011`, `BR-ENT-001`).
- Eloquent/query-builder bindings only; no interpolated SQL (`NFR-SEC-002`).
- Output escaped by default in Blade/Vue (`NFR-SEC-003`).

## Files
- Member photos / meeting evidence: validate type + size; store via Laravel storage with restricted access (`NFR-SEC-008`).

## Secrets & config
- All secrets in `.env`, never committed. `.env.example` documents keys without values.

## Audit
- Auth events + all approval-state transitions recorded (actor, timestamp, IP) in `entry_status_history` / `audit_logs` (`NFR-SEC-010`, `FR-ROLE-004`).

## Dependency awareness
- Keep Laravel/Vue deps current; run `composer audit` / `npm audit` periodically.

## Open decisions / revisit later
- 2FA for LT accounts.
- Session invalidation on password change.
- Signed URLs vs public routes for Phase-2 public display.
