# Phase 5 — Standings & Season

Goal: the signature league table and season summary, computed from approved entries, recomputing on approve/unlock.

---

## 5A — StandingsService & league table
**Goal:** Ranked standings from approved data.
**Deliverables:** `StandingsService` aggregating approved `computed_total` per team; ranking + tiebreak; `LeagueTable` component (position, movement chevron, `TeamCrest` with 1–3 rings, mono total, meeting dots); full-page + embedded mini-table; own-team highlight.
**Implements:** `FR-LGT-001`–`FR-LGT-008`, `BR-LGT-001`–`BR-LGT-003`.
**Key decisions:** movement vs previous approved standings; tiebreak per `08` (confirm with LT).
**Acceptance criteria:**
- Only approved entries counted; unapproved excluded (test).
- Positions 1–3 ringed; own row highlighted for team viewer.
- Meeting dots: gold=approved, hollow=pending.
- Approving an entry updates the table; unlocking recomputes it.
- **Smoke test:** table renders + recomputes on approve/unlock — SESSION-LOG.

## 5B — Season summary grid
**Goal:** season recap (mirrors old "Summery").
**Deliverables:** season grid (teams × meetings, approved cells, season total column); pending vs 0 distinction; champion emphasis; team view (own row + context) and LT full grid.
**Implements:** `FR-SSN-002`–`FR-SSN-006`, `BR-SSN-001`, `BR-SSN-002`.
**Acceptance criteria:**
- Grid cells show approved points; pending shown as "—", not 0.
- Season total = Σ approved; champion emphasized.
- Recomputes on approve/unlock.
- **Smoke test:** grid + champion + recompute — SESSION-LOG.

---

## Phase 5 Exit Checklist
- [x] 5A StandingsService + LeagueTable (movement, rings, dots), recompute on approve/unlock
- [x] 5B season summary grid + champion + pending-vs-zero
- [x] Only approved data reflected (tested)
- [x] All smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
- [x] **MVP (Phases 0–5) runnable for a first season**
