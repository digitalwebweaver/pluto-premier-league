# Phase 3 — Score Entry (Matchday Scorecard)

Goal: captains record and submit their team's meeting scores; server computes authoritatively.

---

## 3A — Entry data model & draft persistence
**Goal:** Store entries and lines.
**Deliverables:** `meeting_entries`, `entry_lines`, `entry_attendance` migrations/models; unique (team_id, meeting_id); create-or-load draft for an open meeting; team + meeting scoping.
**Implements:** `FR-ENT-008`, `FR-ENT-013`, `BR-ENT-002`.
**Acceptance criteria:**
- Opening a meeting creates/loads a single draft (no duplicates).
- Only own team + open meeting editable (403/blocked otherwise).
- **Smoke test:** draft create/load + scope — SESSION-LOG.

## 3B — Category accordion + subtype rows
**Goal:** count/amount categories entry UI.
**Deliverables:** `CategoryAccordion`, `SubtypeRow` wired; subtype dropdowns from active `scoring_rules`; member dropdowns from active own-team members; add/remove rows; per-category subtotal.
**Implements:** `FR-ENT-001`–`FR-ENT-004`, `FR-ENT-002` (TYFCB amount field).
**Acceptance criteria:**
- Only meeting-applicable categories shown.
- Subtypes/members load live and correctly scoped.
- Adding Visitors Hot ×1 shows subtotal 300 (client) and persists lines.
- **Smoke test:** add/remove rows + subtotal — SESSION-LOG.

## 3C — Attendance & punctuality checklist
**Goal:** roster_flat_penalty UI + math.
**Deliverables:** `RosterChecklist`; present/absent + on-time/late marks persisted to `entry_attendance`; live flat-vs-penalty display.
**Implements:** `FR-ENT-005`, `FR-SCO-005`, `FR-SCO-006`.
**Acceptance criteria:**
- 0 absent → +300; 2 absent → −400 (client + server agree).
- 0 late → +100; 3 late → −60.
- **Smoke test:** attendance/punctuality math — SESSION-LOG.

## 3D — Running total, binary toggles, review & submit
**Goal:** complete the scorecard and submit.
**Deliverables:** `RunningTotal` (sticky, pulse); binary category toggles; pre-submit review screen; submit action → server recompute → status `submitted`; validation (non-negative, member∈team, subtype∈category, attendance present before submit).
**Implements:** `FR-ENT-006`, `FR-ENT-007`, `FR-ENT-009`–`FR-ENT-012`, `FR-SCO-010`, `FR-SCO-011`, `BR-ENT-001`.
**Acceptance criteria:**
- Running total reflects all categories live; server total is authoritative on submit (client value ignored server-side).
- Review screen lists all entered data + total.
- Submit sets `submitted`, `submitted_at`; invalid submit blocked with clear message.
- **Smoke test:** full entry → review → submit; server total verified independent of client — SESSION-LOG.

---

## Phase 3 Exit Checklist
- [x] 3A entry/lines/attendance model + single-draft + scope
- [x] 3B accordion + subtype/member rows live-scoped
- [x] 3C attendance/punctuality math correct
- [x] 3D running total + review + authoritative submit + validation
- [x] Server-computed totals verified (client not trusted)
- [x] All smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
