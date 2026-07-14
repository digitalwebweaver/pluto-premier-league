# Phase 6 — Reports, Notifications & Public Display (Phase 2 scope)

Goal: additive value on top of the runnable MVP. Build only after Phases 0–5 are complete and signed off.

---

## 6A — Reports hub & team/category reports
**Goal:** LT reporting foundation.
**Deliverables:** reports hub; team performance report (points by category/meeting); category leaders report; approved-data-only queries.
**Implements:** `FR-RPT-001`, `FR-RPT-002`, `FR-RPT-009`, `BR-RPT-001`.
**Acceptance criteria:** reports render from approved data; empty states before approvals. **Smoke test** recorded.

## 6B — Individual leaderboards & MVP
**Goal:** individual recognition from member-level data.
**Deliverables:** individual MVP/contribution report; visitor, referral/V2V, TYFCB, attendance/punctuality reports.
**Implements:** `FR-RPT-003`–`FR-RPT-007`, `BR-RPT-002`.
**Acceptance criteria:** leaderboards aggregate correctly across teams from `entry_lines`. **Smoke test** recorded.

## 6C — Export center
**Goal:** CSV/PDF export.
**Deliverables:** CSV + PDF export of any report; league-branded PDF.
**Implements:** `FR-RPT-008`.
**Acceptance criteria:** exports produce valid files matching on-screen data. **Smoke test** recorded.

## 6D — Notifications & announcements
**Goal:** workflow awareness.
**Deliverables:** `notifications` + `announcements`; team notifications (approved/sent-back/new-meeting); LT broadcast; notification prefs; optional email.
**Implements:** `FR-NOT-001`–`FR-NOT-005`, `BR-NOT-001`, `BR-NOT-002`.
**Acceptance criteria:** events generate scoped notifications; send-back includes note; broadcast reaches active teams. **Smoke test** recorded.

## 6E — Public / projector display
**Goal:** read-only public standings.
**Deliverables:** public league table, public season summary, full-screen auto-refresh live display; publish/token access; approved-only, no private detail.
**Implements:** `FR-PUB-001`–`FR-PUB-005`, `BR-PUB-001`, `BR-PUB-002`.
**Acceptance criteria:** public pages show only approved/published data, no controls, no draft leakage; live display auto-refreshes. **Smoke test** recorded.

---

## Phase 6 Exit Checklist
- [x] 6A reports hub + team/category reports
- [x] 6B individual leaderboards + MVP + category reports
- [x] 6C CSV export (PDF deferred — needs a package)
- [x] 6D notifications + announcements
- [x] 6E public/projector display, approved-only
- [x] All smoke tests recorded in SESSION-LOG
- [x] CURRENT-STATUS + PROGRESS updated
