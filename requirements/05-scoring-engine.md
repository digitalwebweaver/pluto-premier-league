# 05 — Scoring Engine

## Purpose
The configurable heart of the system. Point values and category subtypes live in the **database**, never in code, so LT can evolve the rules without a developer. This file specifies the categories, their subtypes, input shapes, and the special-case math — derived exactly from the source workbook.

## User stories
- As **LT**, I change what a "Hot visitor" is worth without touching code.
- As **LT**, I add a new subtype to a category (e.g. a new referral tier) and it immediately appears in every team's entry form.
- As a **captain**, the entry form always reflects the current rules — I never see stale point values.

## Category model

Three input shapes cover every category:

1. **Count × subtype** — a repeatable list of `(member, subtype, count)`; points = Σ count × subtype_points. (Visitors, Referrals, V2V, Inductions, Testimonials, Attire, Wearing Pin, Getting Pin, Joint Presentations, Specific Ask, Social Visibility, Team/Joint Meeting.)
2. **Roster checklist with flat/penalty** — special math, not a simple product. (Attendance, Punctuality.)
3. **Binary / flat** — a single toggle awards flat points. (Golden Mic, Abiding Theme.)
4. **Amount-bearing** — like count×subtype but carries a money `amount` alongside. (Thank You Notes / TYFCB.)
5. **Conditional-multiplier** — base per-unit points, doubled on a condition. (Trainings: doubles if whole team present.)

## Functional requirements

- **FR-SCO-001** Categories are stored in a `categories` table with: name, code, input_shape (enum: `count_subtype` | `roster_flat_penalty` | `binary_flat` | `amount_subtype` | `conditional_multiplier`), display order, active flag.
- **FR-SCO-002** Subtypes and their point values are stored in `scoring_rules`: category_id, subtype label, points, active flag, effective ordering.
- **FR-SCO-003** LT can create/edit/deactivate scoring rules; the entry form reads active rules live.
- **FR-SCO-004** Points for a `count_subtype` category = Σ (count × subtype.points) across its rows.
- **FR-SCO-005** **Attendance** (`roster_flat_penalty`): if absent_count = 0 → +300 (flat, configurable); else points = absent_count × (−200) (configurable penalty).
- **FR-SCO-006** **Punctuality** (`roster_flat_penalty`): if late_count = 0 → +100 (flat); else points = late_count × (−20).
- **FR-SCO-007** **Trainings** (`conditional_multiplier`): base 50 per member present; if whole_team_present flag = 1 → 100 per member present.
- **FR-SCO-008** **TYFCB / Thank You Notes** (`amount_subtype`): captures member, from-member, amount, and awards points per the rule (points value confirmed with LT; amount stored for reporting regardless).
- **FR-SCO-009** **Binary** categories award flat configured points when toggled on, 0 when off.
- **FR-SCO-010** A meeting's total = Σ of all applicable categories' computed points for that team.
- **FR-SCO-011** All point math is computed **server-side** on save/submit and re-verified on approval; the Vue form's live total is a convenience mirror, never the source of truth.
- **FR-SCO-012** Changing a scoring rule does **not** retroactively alter already-approved (locked) meeting totals; approved entries store their computed points as a snapshot.

## Seed values (default `scoring_rules`)

| Category | input_shape | Subtypes → points |
|---|---|---|
| Visitors | count_subtype | Hot 300 · Open 200 · Closed 50 · Repeat 0 |
| Inductions | count_subtype | Inducted 500 |
| Referrals | count_subtype | Same team 50 · Cross team/chapter 100 · Cross region/commissioner 150 |
| V2V | count_subtype | Same team 50 · Cross team/chapter/commissioner 150 · Cross region 200 · ED 300 |
| Specific Ask Completed | count_subtype | Completed 200 |
| Trainings | conditional_multiplier | Per member 50 (→100 if whole team) |
| Attendance | roster_flat_penalty | Flat 300 · penalty −200/absent |
| Punctuality | roster_flat_penalty | Flat 100 · penalty −20/late |
| Wearing Badge/Pin | count_subtype | Wearing 100 |
| Getting Achiever's Pin | count_subtype | Earned 100 |
| Thank You Notes (TYFCB) | amount_subtype | (points per LT decision) + amount |
| Joint Presentations | count_subtype | Per JP 100 |
| Social/Member's Place Visibility | count_subtype | Per member present 100 |
| Golden Mic | binary_flat | Awarded (flat, value per LT) |
| Team/Joint Meeting | count_subtype | Per meeting 100 (+ photo evidence field) |
| Abiding Theme | binary_flat | Whole team abiding (flat) |
| Testimonials | count_subtype | Per testimonial 50 |
| Attire | count_subtype | Per member in attire 50 |

## Business rules
- **BR-SCO-001** No point value is ever hardcoded in application code; all come from `categories` / `scoring_rules`.
- **BR-SCO-002** The stray `×76` multiplier found in the source spreadsheet is treated as a **spreadsheet error and NOT reproduced**; the Specific Ask rule is a clean `count × 200` unless LT specifies otherwise.
- **BR-SCO-003** Approved entries snapshot their computed points so later rule edits don't rewrite history.
- **BR-SCO-004** Flat/penalty and multiplier parameters (300/−200, 100/−20, 50/100) are stored as configurable rule fields, not literals.

## Key screens / flows
Scoring rules list (LT) · Add/edit scoring rule (LT) · Categories manage (LT).
(Page inventory C12, C13, C14.)

## Data touched
`categories`, `scoring_rules`, and (read) `meeting_entries` / `entry_lines`.

## Edge cases
- A subtype deactivated mid-season → existing entries that used it keep their snapshot; new rows can't select it.
- Negative totals (heavy absence/lateness) are allowed — attendance/punctuality can subtract.
- Whole-team-present training flag with a member count that exceeds active roster → validation warns.

## Open questions / to clarify
- Exact **TYFCB points** formula (source cell tied points to amount ambiguously) — confirm with LT.
- Exact **Golden Mic** and **Abiding Theme** flat point values — confirm with LT.
- Whether any category has a per-meeting cap (source had fixed row counts, likely just layout, not a cap) — assume no cap.
