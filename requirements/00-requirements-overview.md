# 00 — Requirements Overview

## Product summary
**Pluto Premier League** is a multi-team meeting scoring platform for LVB Pluto. Each team (e.g. Digital Titans) records its fortnightly meeting performance across ~17 scoring categories. A Leadership Team (LT) reviews and approves each submission before it counts toward the league standings. The platform replaces a manual per-meeting Excel workbook with a live, self-serve, approval-gated system and an automatically-computed league table.

## Goals
- Replace error-prone manual spreadsheets (which drift — e.g. a stray `×76` multiplier found in the source workbook) with a single consistent scoring engine.
- Let each team self-report scores quickly, on a phone, right after their meeting.
- Give LT a clean review-and-approve workflow so only verified scores count.
- Keep point values and category subtypes **configurable in the database**, not in code, so the rules can evolve without a developer.
- Surface individual recognition (top referrer, top visitor-host, etc.) that the spreadsheet never exposed.

## Target users
- **Team captains** (one login per team) — enter and submit their team's meeting scores; manage their roster.
- **Leadership Team / LT** (you + a couple of leadership members) — manage scoring rules, meetings and teams; review, approve, or send back submissions; publish the league table.
- **(Phase 2) Public viewers** — read-only league table / standings, e.g. shown on the meeting projector.

## Scope

### In scope
- Two-guard authentication (`team`, `lt`) with role-appropriate dashboards.
- Team roster management (members with business category).
- Meeting lifecycle (LT creates meeting dates, opens/closes submission windows).
- Configurable scoring engine: categories, subtypes, point values in the DB.
- Matchday score-entry form with Draft → Submitted workflow.
- LT approval workflow: Approve (lock) / Send back with note / Unlock.
- Auto-computed league table with movement + per-meeting approval status.
- Season summary.
- (Phase 2) Reports, individual leaderboards, notifications, announcements, public display.

### Out of scope (this build)
- Mobile native apps (the stack leaves room to add a Sanctum API later, but no app is built now).
- Payment/billing (this is an internal league tool, not a paid SaaS).
- Integration with external BNI/LVB national systems or data feeds.
- Automated ingestion from the old Excel workbook (data is entered fresh).
- Real-time multi-user live sync of a single form (approval workflow makes this unnecessary).

## Derived scoring rules (from the source workbook, confirmed)
These are the **default seed values** for the `scoring_rules` table. LT can change them in-app afterward.

| Category | Subtype → points |
|---|---|
| Visitors | Hot 300 · Open 200 · Closed 50 · Repeat 0 (per visitor) |
| Inductions | 500 per member inducted |
| Referrals | Same team 50 · Cross team/chapter 100 · Cross region/commissioner 150 |
| V2V | Same team 50 · Cross team/chapter/commissioner 150 · Cross region 200 · ED 300 |
| Specific Ask Completed | 200 per completed ask |
| Trainings | 50 per member present; **doubled to 100** per member if whole team present |
| Attendance | 300 flat if zero absent; else **−200 per absent member** |
| Punctuality | 100 flat if zero late; else **−20 per late member** |
| Wearing Badge/Achiever's Pin | 100 per member wearing |
| Getting Achiever's Pin | 100 per member earning |
| Thank You Notes (TYFCB) | points tied to amount received |
| Joint Presentations | 100 per JP |
| Social/Member's Place Visibility | 100 per member present |
| Golden Mic | flat points if awarded |
| Team/Joint Meeting | 100 per meeting held (has photo evidence field) |
| Abiding Theme | flat points if whole team followed theme |
| Testimonials | 50 per testimonial given |
| Attire | 50 per member in attire |

> Note: categories available per meeting can differ (the source workbook's Meeting 1 had a reduced set vs later meetings). Which categories apply is set per meeting by LT — see `04-meetings.md`.

## Requirements index

| File | Area |
|---|---|
| `01-authentication-accounts.md` | Login, guards, account lifecycle, sessions |
| `02-team-management.md` | Teams, crests, team logins |
| `03-member-roster.md` | Members per team |
| `04-meetings.md` | Meeting lifecycle, category applicability, open/close |
| `05-scoring-engine.md` | Categories, subtypes, scoring rules, all point math |
| `06-score-entry.md` | Matchday scorecard, draft workflow |
| `07-approval-workflow.md` | Submit → approve / send back → lock / unlock |
| `08-league-table-standings.md` | Standings, movement, computation |
| `09-season-summary.md` | Season recap, champion |
| `10-reports-analytics.md` | Reports + individual leaderboards |
| `11-notifications-announcements.md` | In-app notifications, LT broadcasts |
| `12-public-display.md` | Public/projector standings |
| `13-non-functional.md` | Performance, security, scalability, a11y, support |
| `14-user-roles-permissions.md` | Roles and permission matrix |

## Requirement ID convention
`FR-<AREA>-NNN` for functional requirements, `BR-<AREA>-NNN` for business rules, `NFR-<AREA>-NNN` for non-functional. Area codes: AUTH, TEAM, MBR, MTG, SCO, ENT, APR, LGT, SSN, RPT, NOT, PUB, ROLE.
