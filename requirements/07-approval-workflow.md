# 07 — Approval Workflow

## Purpose
LT reviews each submitted meeting entry before it counts on the league table. Governs the state machine: Draft → Submitted → Approved (locked), with Send-back and Unlock paths.

## State machine
```
draft ──submit──▶ submitted ──approve──▶ approved (locked)
  ▲                   │                        │
  └──(team edits)◀────┘                        │
        ▲          ──send back──▶ sent_back ───┘ (team fixes → resubmit)
        │                                        │
        └────────────────── unlock ◀────────────┘ (LT correction)
```

## User stories
- As **LT**, I see a queue of all submitted entries across teams.
- As **LT**, I open a submission, review the same scorecard the team saw, and Approve or Send back with a note.
- As **LT**, I can unlock an approved entry to correct a genuine error.
- As a **captain**, when my entry is sent back I see the note and can fix and resubmit.

## Functional requirements
- **FR-APR-001** LT has an approval queue listing every `submitted` entry with team, meeting, submitted-at, and computed points.
- **FR-APR-002** LT can open a read-only review of any submission showing all category detail and the server-computed total.
- **FR-APR-003** LT can **Approve**: status → `approved`, locked from team edits, points snapshotted, counts on the league table.
- **FR-APR-004** LT can **Send back** with a required note: status → `sent_back`; the team is notified and can edit/resubmit.
- **FR-APR-005** A `sent_back` entry returns to `submitted` on resubmit (keeping the note history).
- **FR-APR-006** LT can **Unlock** an `approved` entry (guarded confirmation); status → `submitted` (or `draft` per config) for correction; the league table recomputes.
- **FR-APR-007** Only `approved` entries contribute to standings; `draft`/`submitted`/`sent_back` do not.
- **FR-APR-008** Every state transition is recorded (who, when) for the audit log (`10`/`14`).
- **FR-APR-009** A "recently approved" list gives LT quick access to unlock.
- **FR-APR-010** The server re-verifies point computation at approval time (never trusts stored client totals).

## Business rules
- **BR-APR-001** A team can edit a `submitted` entry until it is approved; once `approved` it is locked to the team (only LT can unlock).
- **BR-APR-002** Send-back requires a non-empty note so the team knows what to fix.
- **BR-APR-003** Unlock is an intentionally low-prominence action (rare correction path), but always available to LT.
- **BR-APR-004** Approval snapshots points so later scoring-rule edits don't rewrite approved history (ties to `BR-SCO-003`).

## Key screens / flows
Approval queue · Review submission (+ send-back composer) · Recently approved · Unlock confirmation.
(Page inventory C2, C3, C4, C5, C6; team side B7, B8, B9.)

## Data touched
`meeting_entries.status`, `approved_by`, `approved_at`, `sent_back_note`, points snapshot fields · `entry_status_history` (audit).

## Edge cases
- Team resubmits while LT is mid-review → optimistic-lock/refresh guard prevents approving a stale version.
- Unlocking an approved entry that changes standings → table + season summary recompute; movement indicators update.
- Meeting closed but entry still `submitted` → LT can still approve/send-back (close gates team submission, not LT action) — per `BR-MTG-003`.

## Open questions / to clarify
- On unlock, should the entry return to `submitted` or `draft`? (Assumed `submitted`; confirm.)
- Should teams get a hard deadline after send-back to resubmit? (Not discussed.)
