# Plan — 05 Scoring Engine Design

The single most important piece of logic. Implements `requirements/05`.

## Principle
No point value in code. `categories.input_shape` + `scoring_rules` (with `extra_params` json) fully describe how each category scores. `ScoringService` reads them and computes.

## Input shapes → formulas

### 1. count_subtype
`points = Σ (line.count × rule.points)` over the category's `entry_lines`.
Categories: Visitors, Inductions, Referrals, V2V, Specific Ask, Wearing Pin, Getting Pin, Joint Presentations, Social Visibility, Team/Joint Meeting, Testimonials, Attire.

### 2. amount_subtype (TYFCB)
Like count_subtype but each line also stores `amount` (₹). Points per LT's confirmed rule; `amount` always stored for reporting even if points formula is simple.

### 3. roster_flat_penalty (Attendance, Punctuality)
From `entry_attendance`:
- Attendance: `absent = count(is_present=false)`; `points = absent==0 ? flat(300) : absent × penalty(-200)`.
- Punctuality: `late = count(is_on_time=false)`; `points = late==0 ? flat(100) : late × penalty(-20)`.
- `flat` and `penalty` come from the rule's `extra_params`, not literals.

### 4. binary_flat (Golden Mic, Abiding Theme)
`points = toggle ? rule.points : 0`.

### 5. conditional_multiplier (Trainings)
`per_member = whole_team_present ? base×2 : base` (base 50 → 100); `points = members_present × per_member`. Base and multiplier in `extra_params`.

## Meeting total
`total = Σ category_points` over the meeting's **applicable** categories only (`meeting_categories`).

## Authority & snapshotting
- Compute on **save** (draft convenience), **submit** (authoritative), **approve** (authoritative + snapshot).
- On approve, write `meeting_entries.points_snapshot` (per-category + per-line breakdown) and `computed_total`. Later `scoring_rules` edits never touch approved rows.
- The Vue running total mirrors formulas client-side for UX but is discarded server-side.

## Seeding
A seeder loads the default categories, input_shapes, and `scoring_rules` from the table in `requirements/00`/`05`. LT edits afterward in-app.

## Explicitly NOT reproduced
The source spreadsheet's stray `×76` on Specific Ask is a spreadsheet error; the rule is clean `count × 200` unless LT says otherwise (`BR-SCO-002`).

## Testability
`ScoringService` is pure w.r.t. inputs → unit tests cover each shape with edge cases (zero, negative attendance points, whole-team training doubling, multi-row same-member visitors). No HTTP needed.

## Open decisions / revisit later
- Exact TYFCB points formula, Golden Mic / Abiding Theme flat values — confirm with LT, then adjust seed.
- Whether `extra_params` should be normalized into columns instead of json (json chosen for flexibility).
