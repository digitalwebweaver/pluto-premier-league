# 14 — User Roles & Permissions

## Roles
1. **Team Captain** (`team` guard) — one login per team; manages their own team only.
2. **Leadership Team / LT** (`lt` guard) — league administrators; oversee all teams and configuration.
3. **Public viewer** (Phase 2, no auth) — read-only standings.

## What each role can and cannot do

### Team Captain
**Can:**
- Log in → own team dashboard.
- Manage own team's roster (add/edit/deactivate members).
- Edit own team profile (crest color, captain contact).
- Create/edit/save-draft/submit score entries for **open** meetings, own team only.
- Edit a submitted entry until it is approved; fix and resubmit a sent-back entry.
- View the league table (own row highlighted), season summary (own row), own member histories.
- (Phase 2) Receive notifications.

**Cannot:**
- See or edit any other team's roster, drafts, or entries.
- Create teams, meetings, or scoring rules.
- Approve/send-back/unlock anything.
- Edit an approved (locked) entry.
- Access any LT route (blocked server-side).

### Leadership Team (LT)
**Can:**
- Log in → LT overview.
- Create/edit/activate/deactivate teams; issue and reset team-captain logins.
- Manage LT logins.
- Create/edit meetings; open/close them; set applicable categories per meeting.
- Manage categories and scoring rules (the flexibility engine).
- View every team's submissions; approve, send back (with note), and unlock.
- View/publish the league table and season summary.
- (Phase 2) Run and export reports, individual leaderboards, post announcements, view audit log, edit league settings.

**Cannot:**
- Enter a team's scores *as* the team in the normal flow (LT reviews/corrects via approval + unlock, not by impersonating submission) — unless an explicit LT-on-behalf entry is later approved as scope.

### Public viewer (Phase 2)
**Can:** view public league table, public season summary, live standings.
**Cannot:** see any draft/submitted/sent-back data, member detail beyond standings, or any control.

## Permission matrix

| Capability | Team Captain | LT | Public |
|---|---|---|---|
| Log in to own dashboard | ✅ | ✅ | — |
| Manage own roster | ✅ | (view) | ❌ |
| Manage own team profile | ✅ (limited) | ✅ | ❌ |
| Create/edit teams | ❌ | ✅ | ❌ |
| Issue/reset team logins | ❌ | ✅ | ❌ |
| Create/edit meetings, open/close | ❌ | ✅ | ❌ |
| Set applicable categories per meeting | ❌ | ✅ | ❌ |
| Manage categories & scoring rules | ❌ | ✅ | ❌ |
| Enter/draft/submit scores (own, open mtg) | ✅ | ❌* | ❌ |
| Edit submitted (pre-approval) | ✅ | ✅ | ❌ |
| Edit approved (locked) | ❌ | via unlock | ❌ |
| Approve / send back | ❌ | ✅ | ❌ |
| Unlock approved | ❌ | ✅ | ❌ |
| View league table | ✅ (own highlighted) | ✅ | ✅ (P2) |
| View season summary | ✅ (own row) | ✅ (full grid) | ✅ (P2) |
| Reports / individual leaderboards | ❌ | ✅ (P2) | ❌ |
| Announcements | ❌ | ✅ (P2) | ❌ |
| Audit log | ❌ | ✅ (P2) | ❌ |

\* LT does not submit as a team in normal flow; corrections happen through approval/unlock.

## Enforcement requirements
- **FR-ROLE-001** Every route is protected by the correct guard and a Policy/middleware check; authorization is server-side only.
- **FR-ROLE-002** Team-scoped queries always filter by the authenticated `team_id`; no capability relies on hidden UI.
- **FR-ROLE-003** Attempting a forbidden action returns 403 (not a silent no-op).
- **FR-ROLE-004** All approval-state transitions and auth events are audit-logged with actor and timestamp.

## Open questions / to clarify
- Should LT be able to enter scores on behalf of a team that can't access the system? (Not in scope now; possible future "LT-on-behalf" entry.)
- Multiple LT permission tiers (e.g. super-LT vs reviewer)? (Assumed single LT tier v1.)
