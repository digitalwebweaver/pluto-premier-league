# 13 — Non-Functional Requirements

## Performance
- **NFR-PERF-001** Score-entry form interactions (expand category, add row, running-total update) respond in <100ms on a mid-range phone (client-side).
- **NFR-PERF-002** League table and season summary render in <1s for a realistic league size (tens of teams, ~10 meetings).
- **NFR-PERF-003** Standings aggregation queries are indexed on `(team_id, meeting_id, status)`; consider a `standings_snapshots` table if aggregation becomes heavy.
- **NFR-PERF-004** Avoid N+1 queries in Inertia responses (eager-load relations for rosters, entries, rules).

## Security
- **NFR-SEC-001** All input validated server-side via Form Requests; never trust client values (especially point totals — recomputed server-side per `FR-SCO-011`).
- **NFR-SEC-002** All DB access via Eloquent/query builder with bindings; no string-interpolated SQL (injection prevention).
- **NFR-SEC-003** Output escaped by default (Blade `{{ }}` / Vue text interpolation); no unescaped rendering of user data.
- **NFR-SEC-004** Authorization enforced server-side on every route via guards + Policies/middleware; UI hiding is never the security boundary (`BR-AUTH-001`).
- **NFR-SEC-005** CSRF protection enabled (Laravel default) on all state-changing requests.
- **NFR-SEC-006** Passwords hashed (bcrypt/argon2); secrets only in `.env`, never committed.
- **NFR-SEC-007** Login and password-reset endpoints rate-limited/throttled.
- **NFR-SEC-008** File uploads (member photos, meeting evidence) validated for type/size and stored outside the web root or via Laravel's storage with restricted access.
- **NFR-SEC-009** A team can never read or write another team's data; enforced by scoping every query to the authenticated team.
- **NFR-SEC-010** Audit trail records auth events and all approval-state transitions (who/when).

## Scalability & availability
- **NFR-SCAL-001** Architecture is a standard Laravel monolith on a single host initially; stateless controllers allow horizontal scaling later if ever needed.
- **NFR-AVAIL-001** Runs on commodity/shared-style hosting with MariaDB; no exotic infra dependency.

## Accessibility
- **NFR-A11Y-001** Status meaning conveyed by color **+ label + icon**, never color alone.
- **NFR-A11Y-002** Minimum 44px tap targets on mobile entry rows.
- **NFR-A11Y-003** Full keyboard operability for the LT approval queue (desktop-primary flow).
- **NFR-A11Y-004** Visible focus states on all interactive elements; `prefers-reduced-motion` respected.

## Internationalization
- **NFR-I18N-001** English-only for v1. Copy is centralized (Laravel lang files) so localization is possible later. Currency (TYFCB amounts) shown in ₹ with Indian formatting.

## Compliance & data
- **NFR-DATA-001** Personal data limited to member names, business categories, optional photos, and captain contacts. No sensitive categories. Retain per LT policy; deactivation preserves history rather than deleting.

## Browser / device support
- **NFR-DEV-001** Latest 2 versions of Chrome, Safari, Firefox, Edge. Mobile Safari and Chrome Android are first-class (captains on phones).
- **NFR-DEV-002** Light theme only, mobile-first, responsive across mobile/tablet/desktop on every page.

## Open questions / to clarify
- Expected concurrent load at meeting time (all teams submitting at once)? (Assumed low tens; not a scaling concern.)
- Backup cadence for the local MariaDB? (Ops decision — flag to owner.)
