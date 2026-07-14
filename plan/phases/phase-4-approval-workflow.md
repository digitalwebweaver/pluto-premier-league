# Phase 4 — Approval Workflow

Goal: LT reviews submissions; only approved scores count. Implements the state machine, snapshotting, and audit.

---

## 4A — ApprovalService state machine + history
**Goal:** Encode transitions.
**Deliverables:** `ApprovalService` (draft→submitted→approved; submitted→sent_back; approved→unlock); `entry_status_history` writes (actor, timestamp, note); unit tests per transition; illegal transitions rejected.
**Implements:** `FR-APR-008`, `FR-APR-010`, `BR-APR-004`.
**Acceptance criteria:**
- Unit tests cover every legal transition + rejection of illegal ones.
- History row written per transition.
- **Smoke test:** service transitions + history — SESSION-LOG.

## 4B — Approval queue & review
**Goal:** LT worklist + read-only review.
**Deliverables:** LT approval queue (all `submitted` across teams: team, meeting, submitted-at, total); review screen reusing the scorecard read-only with server-computed detail.
**Implements:** `FR-APR-001`, `FR-APR-002`.
**Acceptance criteria:**
- Queue lists all submitted entries; team users get 403 on these routes.
- Review shows full category detail + authoritative total.
- **Smoke test:** queue + review load, scoped to LT — SESSION-LOG.

## 4C — Approve (lock + snapshot) & send-back
**Goal:** Terminal + return paths.
**Deliverables:** Approve → status `approved`, `points_snapshot` + `computed_total` written, locked to team; Send-back → required note, status `sent_back`, team can edit/resubmit; resubmit → `submitted` keeping note history.
**Implements:** `FR-APR-003`–`FR-APR-005`, `FR-APR-007`, `BR-APR-001`, `BR-APR-002`, `FR-SCO-012`.
**Acceptance criteria:**
- Approve locks entry (team edit → 403) and snapshots points.
- Editing a scoring rule after approval does NOT change the approved total (snapshot honored) — test.
- Send-back requires note; team sees it and can resubmit.
- **Smoke test:** approve+lock+snapshot, send-back+resubmit — SESSION-LOG.

## 4D — Unlock & recently-approved
**Goal:** correction path.
**Deliverables:** recently-approved list; guarded Unlock → back to `submitted` (per config); optimistic-lock guard against approving a stale/edited version.
**Implements:** `FR-APR-006`, `FR-APR-009`, edge cases in `requirements/07`.
**Acceptance criteria:**
- Unlock returns entry to editable/re-reviewable state and flags standings for recompute (Phase 5 consumes).
- Stale-version approval prevented.
- **Smoke test:** unlock flow + stale guard — SESSION-LOG.

---

## Phase 4 Exit Checklist
- [x] 4A ApprovalService + history, transitions tested
- [x] 4B queue + review, LT-scoped
- [x] 4C approve/lock/snapshot + send-back/resubmit; snapshot immutability tested
- [x] 4D unlock + recently-approved + stale guard
- [x] All smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
