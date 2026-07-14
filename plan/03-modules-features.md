# Plan — 03 Modules & Features

Maps each module to its requirement file, primary screens, and the phase that delivers it.

| Module | Requirements | Primary screens | Phase |
|---|---|---|---|
| Authentication & accounts | `01` | Login, reset, set-password, account | 1 |
| User/role management | `14`, `01` | LT logins, team logins mgmt | 1 |
| Team management | `02` | All teams, create/edit team, team profile | 2 |
| Member roster | `03` | Roster, add/edit member, member history | 2 |
| Meetings | `04` | Meetings list, create/edit, open/close | 2 |
| Scoring engine | `05` | Categories manage, scoring rules list/edit | 2 |
| Score entry | `06` | Submit list, entry form, attendance, review | 3 |
| Approval workflow | `07` | Approval queue, review, send-back, unlock | 4 |
| League table | `08` | League table (team/LT/embedded) | 5 |
| Season summary | `09` | Season summary (team/LT) | 5 |
| Reports & leaderboards | `10` | Reports hub, individual leaderboards, exports | 6 (P2) |
| Notifications & announcements | `11` | Team notifications, LT broadcast | 6 (P2) |
| Public display | `12` | Public table, season, live display | 6 (P2) |

## Cross-module services
- **ScoringService** (`05`) — turns entry data + active rules into points; owns the five input-shape formulas.
- **ApprovalService** (`07`) — the Draft→Submitted→Approved state machine, snapshotting, and history.
- **StandingsService** (`08`,`09`) — aggregates approved entries into the table and season grid, computes movement.

## Delivery order rationale
Auth/user-management first (foundation + gate). Then the core domain teams/members/meetings/rules must exist before entries can reference them. Score entry then approval then standings follow the natural data flow. Reports/notifications/public are additive Phase 2.
