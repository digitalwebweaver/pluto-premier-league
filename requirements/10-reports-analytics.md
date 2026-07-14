# 10 — Reports & Analytics (Phase 2)

## Purpose
Because the data model captures *who* did *what* (which member invited each visitor, who gave each referral, TYFCB amounts), the platform can surface insight the spreadsheet never did — including individual recognition. This whole area is **Phase 2**.

## User stories
- As **LT**, I run a per-team performance report by category and meeting.
- As **LT**, I see which team/member leads each category.
- As **LT**, I recognize individuals: top referrer, top visitor-host, most punctual, MVP.
- As **LT**, I export any report to CSV/PDF.

## Functional requirements
- **FR-RPT-001** Team performance report: points by category, per team, per meeting.
- **FR-RPT-002** Category leaders report: top team/member per category.
- **FR-RPT-003** Individual MVP/contribution report: ranks members across all teams by points generated.
- **FR-RPT-004** Visitor report: visitors brought by subtype (hot/open/closed/repeat).
- **FR-RPT-005** Referral & V2V report: same-team vs cross-team vs cross-region vs ED/commissioner splits.
- **FR-RPT-006** TYFCB value report: business-value totals with amounts.
- **FR-RPT-007** Attendance & punctuality trend report per team across the season.
- **FR-RPT-008** Export center: CSV and PDF export of any report.
- **FR-RPT-009** All reports read only `approved` data.

## Business rules
- **BR-RPT-001** Reports never include unapproved data (consistency with standings).
- **BR-RPT-002** Individual leaderboards aggregate across teams from `entry_lines` member references.

## Key screens / flows
Reports hub · Individual leaderboards · each report sub-page · export center.
(Page inventory C20, C21, D1–D8.)

## Data touched
Read-only aggregation across `entry_lines`, `entry_attendance`, `meeting_entries` (approved), `members`, `teams`.

## Edge cases
- Member who moved teams mid-season (not supported in v1, but if added) → attribute points to the team at time of entry.
- Empty reports before any approvals → clear empty states.

## Open questions / to clarify
- Which reports are must-have vs nice-to-have for the first season? (All marked Phase 2; prioritize with LT.)
- PDF export styling — reuse league branding? (Assumed yes.)
