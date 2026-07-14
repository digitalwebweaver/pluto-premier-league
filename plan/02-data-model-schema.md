# Plan — 02 Data Model & Schema

> Column names here are the intended contract. **Always verify against the real migrations/DB before writing models or queries** (CLAUDE.md cross-cutting rule 2).

## Entities & key columns

### Auth
- **lt_users** — id, name, email/username (unique), password, must_set_password, notification_pref, timestamps.
- **team_users** — id, team_id (FK), name, email/username (unique), password, must_set_password, notification_pref, timestamps.
  - (Alternative: single `users` table + `role` + nullable `team_id`. Decide in `04-auth-user-management.md`; whichever is chosen, guards map accordingly.)
- **password_reset_tokens**, **sessions** — Laravel defaults.

### League core
- **seasons** — id, name, starts_on, ends_on, is_active, is_complete.
- **teams** — id, name (unique), short_code, crest_color, is_active, timestamps.
- **members** — id, team_id (FK), name, business_category, photo_path, avatar_color, is_active, timestamps.
- **meetings** — id, season_id (FK), sequence_no, meeting_date, status (scheduled|open|closed), timestamps.
- **meeting_categories** — meeting_id (FK), category_id (FK) — which categories apply to a meeting (pivot).

### Scoring engine
- **categories** — id, name, code (unique), input_shape (enum: count_subtype|roster_flat_penalty|binary_flat|amount_subtype|conditional_multiplier), display_order, is_active.
- **scoring_rules** — id, category_id (FK), subtype_label, points (int, may be negative), extra_params (json — e.g. flat/penalty/multiplier values), is_active, display_order.

### Entries
- **meeting_entries** — id, team_id (FK), meeting_id (FK), status (draft|submitted|approved|sent_back), computed_total, points_snapshot (json, set on approval), submitted_at, approved_by (FK lt_users), approved_at, sent_back_note, timestamps. **Unique (team_id, meeting_id).**
- **entry_lines** — id, meeting_entry_id (FK), category_id (FK), scoring_rule_id (FK, nullable for special shapes), member_id (FK, nullable), count, amount (nullable, for TYFCB), from_member_id (nullable, TYFCB), computed_points, evidence_path (nullable, Team/Joint Meeting).
- **entry_attendance** — id, meeting_entry_id (FK), member_id (FK), is_present (bool), is_on_time (bool). Backs Attendance & Punctuality math.
- **entry_status_history** — id, meeting_entry_id (FK), from_status, to_status, actor_type, actor_id, note, created_at (audit for approvals).

### Phase 2
- **notifications** — id, team_id (FK), type, payload (json), read_at, created_at.
- **announcements** — id, lt_user_id (FK), body, created_at.
- **audit_logs** — id, actor_type, actor_id, action, subject_type, subject_id, ip, created_at.
- (optional) **standings_snapshots** — season_id, meeting_id, team_id, rank, total (for movement history).

## Relationships (summary)
- season 1—* meetings ; season 1—* (teams via league membership; teams are global to the active season in v1).
- team 1—* members ; team 1—1 team_user (captain, v1) ; team 1—* meeting_entries.
- meeting 1—* meeting_entries ; meeting *—* categories (via meeting_categories).
- category 1—* scoring_rules ; category 1—* entry_lines.
- meeting_entry 1—* entry_lines ; 1—* entry_attendance ; 1—* entry_status_history.

## Indexes
- `meeting_entries (team_id, meeting_id)` unique; `(status)`; `(meeting_id, status)`.
- `entry_lines (meeting_entry_id)`, `(category_id)`, `(member_id)`.
- `entry_attendance (meeting_entry_id, member_id)`.
- `members (team_id, is_active)`.
- `scoring_rules (category_id, is_active)`.

## MariaDB note
Set `Schema::defaultStringLength(191)` in `AppServiceProvider::boot()` as a safeguard for older MariaDB index-length limits (harmless on newer versions). Use `utf8mb4`.

## Snapshotting
On approval, `points_snapshot` stores the fully computed per-category/per-line points so subsequent `scoring_rules` edits never change an approved entry (`FR-SCO-012`, `BR-SCO-003`).

## Open decisions / revisit later
- Unified `users` vs split `lt_users`/`team_users` (see auth doc).
- Whether `standings_snapshots` is built now or deferred.
- Do teams persist across seasons or re-enroll per season? (v1: single active season, teams global.)
