# Phase 0 — Foundation

Goal: a solid, running architecture and design system before any feature work.

---

## 0A — Project scaffold & stack wiring
**Goal:** Stand up Laravel 11 + Inertia + Vue 3 + Vite + Tailwind, connected to MariaDB.
**Deliverables:** fresh Laravel 11 app; Inertia server+client adapters; Vue 3 configured in Vite; Tailwind installed; `.env` pointed at local `pluto_league` MariaDB DB; `AppServiceProvider` sets `defaultStringLength(191)`; welcome Inertia page renders.
**Implements:** architecture groundwork for all FRs.
**Key decisions:** Inertia (not API) per `plan/01`; split-guard direction noted for Phase 1.
**Acceptance criteria:**
- `php artisan serve` + `npm run dev` start with no errors.
- Visiting `/` renders an Inertia Vue page (200).
- `php artisan migrate` runs against MariaDB successfully (default Laravel tables).
- **Smoke test:** app boots, home route 200, DB connection verified — recorded in SESSION-LOG.

## 0B — Design tokens & core components
**Goal:** Encode `design.md` tokens and build the reusable component shells.
**Deliverables:** `tailwind.config.js` colors/fonts/spacing tokens; fonts loaded (Space Grotesk, Inter, IBM Plex Mono); component stubs: `AppShell`, `StatusPill`, `TeamCrest`, `EmptyState`, `NumberStepper` (others stubbed as needed); a component preview/sandbox route.
**Implements:** `design.md` §2–§6.
**Key decisions:** tokens defined once, consumed via classes; no raw hex in components.
**Acceptance criteria:**
- Preview route shows StatusPill in all four states (color+label+icon) and TeamCrest with/without ring.
- Tokens resolve (e.g. `bg-ink`, `text-gold`, `font-display` render correctly).
- Renders correctly at 375 / 768 / 1280px.
- **Smoke test:** preview route 200, components render — recorded in SESSION-LOG.

## 0C — App shell, layout & base routing
**Goal:** Persistent responsive layout with role-aware nav placeholder.
**Deliverables:** `AppShell` with top bar + sidebar (≥680px) / bottom-tab (<680px); persistent Inertia layout; placeholder dashboards for team and LT; 404/403/500 error pages on-brand.
**Implements:** global layout (`design.md` §3, §9); groundwork for `FR-AUTH-003`.
**Key decisions:** persistent layout so nav/scroll survive Inertia visits.
**Acceptance criteria:**
- Nav collapses to bottom tabs under 680px, sidebar above.
- Placeholder `/team` and `/lt` pages render inside AppShell.
- 404/403 pages render on-brand.
- **Smoke test:** both placeholder routes 200, error pages verified — recorded in SESSION-LOG.

## 0D — Seed skeleton & test harness
**Goal:** Base seeders + PHPUnit harness ready for later phases.
**Deliverables:** seeder skeletons (season, teams, categories+rules placeholder); PHPUnit configured; one trivial passing test; optional GitHub Actions CI (`composer install`, `artisan test`, `npm build`).
**Implements:** testing strategy (`plan/08`).
**Acceptance criteria:**
- `php artisan migrate --seed` runs clean.
- `php artisan test` passes the sample test.
- `npm run build` produces assets with no errors.
- **Smoke test:** migrate+seed+test+build all green — recorded in SESSION-LOG.

---

## Phase 0 Exit Checklist
- [x] 0A app boots (serve + vite), home 200, MariaDB migrate OK
- [x] 0B tokens + core components render, responsive
- [x] 0C AppShell responsive nav + placeholder dashboards + error pages
- [x] 0D seed skeleton + test harness + build all green
- [x] All 0A–0D smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
