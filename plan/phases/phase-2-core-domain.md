# Phase 2 — Core Domain

Goal: the domain objects entries will reference — teams, members, meetings, and the configurable scoring engine. Begins only after Phase 1 owner sign-off.

---

## 2A — Teams
**Goal:** LT manages teams.
**Deliverables:** `teams` migration/model; LT all-teams list, create/edit, activate/deactivate; auto short-code; captain team-profile (limited edit).
**Implements:** `FR-TEAM-001`–`FR-TEAM-003`, `FR-TEAM-006`–`FR-TEAM-008`, `BR-TEAM-*`.
**Acceptance criteria:**
- LT creates a team (unique name, crest color) → appears in list.
- Deactivate hides from live but keeps record.
- Captain can edit only crest color + contact (403 on others).
- **Smoke test:** create/edit/deactivate + captain-scope block — SESSION-LOG.

## 2B — Members / roster
**Goal:** Captains manage rosters.
**Deliverables:** `members` migration/model; roster list, add/edit, activate/deactivate; team-scoped queries; member detail placeholder.
**Implements:** `FR-MBR-001`–`FR-MBR-005`, `FR-MBR-007`, `BR-MBR-*`.
**Acceptance criteria:**
- Captain adds/edits members of own team only.
- Inactive members excluded from active lists but kept.
- Cross-team access → 403.
- **Smoke test:** roster CRUD + scope — SESSION-LOG.

## 2C — Seasons & meetings
**Goal:** LT defines meetings and their lifecycle.
**Deliverables:** `seasons`, `meetings` migrations/models; meetings list, create/edit, open/close control; season concept (one active).
**Implements:** `FR-MTG-001`–`FR-MTG-004`, `FR-MTG-007`, `FR-MTG-008`, `FR-SSN-001`, `BR-MTG-001`, `BR-MTG-003`.
**Acceptance criteria:**
- LT creates a meeting; toggles open/closed.
- Teams cannot submit to a non-open meeting (enforced later, contract set here).
- **Smoke test:** create + open/close — SESSION-LOG.

## 2D — Categories & applicable-per-meeting
**Goal:** Category catalog + per-meeting applicability.
**Deliverables:** `categories` migration/model with `input_shape`; `meeting_categories` pivot; LT categories-manage screen; per-meeting category selection.
**Implements:** `FR-SCO-001`, `FR-MTG-005`, `BR-MTG-002`.
**Acceptance criteria:**
- LT enables/disables + orders categories.
- LT sets which categories apply to a meeting; default = full set.
- **Smoke test:** category manage + per-meeting selection — SESSION-LOG.

## 2E — Scoring rules engine + seed
**Goal:** The flexibility engine.
**Deliverables:** `scoring_rules` migration/model (points, `extra_params` json); LT rules list + add/edit; seeder loading the confirmed default rule set; `ScoringService` skeleton with the five input-shape formulas (unit-tested, no HTTP yet).
**Implements:** `FR-SCO-002`–`FR-SCO-010`, `BR-SCO-001`, `BR-SCO-002`, `BR-SCO-004`.
**Key decisions:** flat/penalty/multiplier params in `extra_params`; stray `×76` NOT reproduced.
**Acceptance criteria:**
- Seeder loads default categories + rules matching `requirements/05` table.
- Unit tests: count_subtype sum, attendance flat vs −200/absent, punctuality −20/late, training doubling, binary, amount_subtype.
- Editing a rule changes computed points for a fresh (unapproved) calc.
- **Smoke test:** rules screen loads live subtypes; ScoringService unit tests pass — SESSION-LOG.

---

## Phase 2 Exit Checklist
- [x] 2A teams CRUD + scope
- [x] 2B roster CRUD + scope
- [x] 2C seasons/meetings + open/close
- [x] 2D categories + per-meeting applicability
- [x] 2E scoring rules + seed + ScoringService unit tests green
- [x] All smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
