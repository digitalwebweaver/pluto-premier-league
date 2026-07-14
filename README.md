# Pluto Premier League

A multi-team meeting scoring platform built for **LVB Pluto**, a business-networking chapter. Every team logs its fortnightly meeting performance across 18 scoring categories; a Leadership Team reviews and approves each submission before it counts on a live, auto-computed league table.

It replaces a manual, error-prone Excel workbook (one sheet per meeting, one column per activity) with a self-serve web app: captains submit from their phone right after the meeting, the Leadership Team reviews with one click, and the standings update automatically — no spreadsheet, no manual point tallying, no drift between what was scored and what was recorded.

**Live:** [pplpluto.digitalwebweaver.com](https://pplpluto.digitalwebweaver.com)

---

## What it does

### The problem it replaces
Each team used to fill in a shared Excel workbook by hand — one tab per meeting, ~18 sections per tab (Visitors, Referrals, Attendance, Golden Mic, TYFCB, etc.), each with its own point formula typed directly into the cell. Point values were hardcoded in formulas, easy to fat-finger, hard to audit, and impossible to change without editing every sheet. There was no review step — whatever a team typed in counted immediately.

### How this app solves it
- **Point values live in the database**, not in code or a spreadsheet formula. The Leadership Team can change what a "Hot visitor" is worth, or what Attendance's penalty is, from a settings screen — no developer needed.
- **Every submission is reviewed before it counts.** A team's meeting score is a *draft* until they submit it; it only reaches the league table after the Leadership Team approves it. Submissions can be sent back with a note for correction.
- **The server is the only source of truth for points.** The running total a captain sees while filling out the form is just a preview — every save, submit, and approval recomputes the score from scratch on the server, so nothing can be tampered with client-side.
- **Approved scores are snapshotted.** Once a meeting is approved, its point breakdown is locked in permanently — if the Leadership Team later tweaks a point value, it never silently rewrites history.

---

## Who uses it

| Role | What they do |
|---|---|
| **Team Captain** | One login per team. Submits their team's scorecard each meeting, manages their team's member roster, edits their crest color, views the league table and their own team's standing. |
| **Leadership Team (LT)** | Manages teams, meetings, scoring categories, and point values. Reviews every submitted scorecard, approves or sends it back with a note, and can unlock an approved meeting for correction. Runs reports, broadcasts announcements, and issues/resets team logins. |
| **Public / projector viewers** | No login required. A read-only league table, season summary grid, and a full-screen auto-refreshing "live" display, meant to be shown on a projector at the meeting venue. Shows only approved results — nothing in draft or pending review ever leaks out. |

---

## The scoring engine

18 categories are scored, each using one of five underlying math shapes — all driven by data, not hardcoded logic:

| Shape | Formula | Used by |
|---|---|---|
| **Count × subtype** | `Σ (count × subtype's points)` | Visitors, Inductions, Referrals, V2V, Golden Mic, Wearing Pin, Getting Pin, Joint Presentations, Social Visibility, Team/Joint Meeting, Testimonials, Attire |
| **Amount-based** | `Σ(₹ amount) ÷ rate × points` | Thank You Notes / TYFCB — scales with the rupee value of business given, not a flat count |
| **Roster flat-or-penalty** | Flat award if nobody offends; otherwise `offenders × penalty` | Attendance (absent), Punctuality (late) |
| **Binary flat** | Fixed points if a team-wide condition is met | Abiding Theme |
| **Conditional multiplier** | Per-member points, doubled if the whole team participates | Trainings |

Every meeting can have a different set of applicable categories (mirroring how a chapter's first meeting of a season scores fewer categories than later ones), configured per-meeting by the Leadership Team.

---

## The flow, end to end

1. **Leadership Team sets up the season** — creates the season and its meetings, decides which of the 18 categories apply to each meeting, and confirms point values in the scoring rules screen.
2. **Captain opens a meeting's scorecard** — an accordion of every applicable category. Count-based categories (Visitors, Referrals, Golden Mic, …) are repeatable rows: pick the member, pick the subtype, enter a count. Attendance/Punctuality is a roster checklist. A running total updates live and pulses on change.
3. **Captain saves as draft, or submits** — a draft can be edited and re-saved any number of times. Submitting locks it for editing and puts it into the Leadership Team's approval queue.
4. **Leadership Team reviews** — sees the full category-by-category breakdown (server-recomputed, not the client's number) and either **Approves** (locks it in, snapshots the point breakdown, counts on the table) or **Sends it back** with a required note explaining what needs fixing, which the captain sees and can then resubmit.
5. **An approved meeting can be unlocked** by the Leadership Team if a correction is needed later — it goes back to "submitted" for the team to fix and resubmit.
6. **The league table, season summary, and reports** all read only approved data, recomputing live — there's no separate "publish" step and no way for a draft or pending submission to influence the standings.
7. **Notifications** fire automatically — a team is notified when their submission is approved or sent back (with the note); the Leadership Team can also broadcast announcements to every active team.
8. **The public/projector views** (`/public/league`, `/public/season`, `/public/live`) need no login and show only approved results — ideal for displaying live standings at the meeting venue.

---

## Feature list

- **Two-guard authentication** — separate login flows for Team Captains and the Leadership Team, with role-appropriate dashboards, forced password-set on first login, self-serve password reset by email, and LT-managed login issuing/resetting.
- **Team management** — Leadership Team creates/edits/deactivates teams (never hard-deleted, so history is preserved); each team gets an auto-derived short code and a brand color, plus an optional real logo.
- **Roster management** — captains add, edit, and deactivate their own team's members; each member has a name, business category, and avatar color.
- **Configurable scoring rules** — every category, subtype, and point value lives in the database and is editable in-app by the Leadership Team.
- **Meeting lifecycle** — Leadership Team creates meetings, sets which categories apply to each, and opens/closes the submission window.
- **Full scorecard entry** — all five scoring shapes, live running total, server-authoritative recompute on every save.
- **Draft → Submit → Approve/Send-back → Unlock workflow** — fully audited (every status change is logged with who and when), with optimistic-lock protection so an approval can't silently overwrite a team's last-second edit.
- **League table** — auto-computed standings with rank, movement (▲/▼ vs. the previous meeting), per-meeting approval dots, and tiebreak rules (total → meetings approved → name).
- **Season summary** — a teams × meetings grid showing every approved score, with the season leader/champion highlighted.
- **Reports & individual recognition** — team performance reports, category leaders, an individual MVP/contribution leaderboard, visitor/referral/TYFCB/attendance breakdowns — all things the original spreadsheet never surfaced.
- **CSV export** — every report can be downloaded.
- **In-app notifications & announcements** — teams get notified on approval/send-back; the Leadership Team can broadcast to all active teams.
- **Public projector display** — no-login, approved-only league table, season grid, and an auto-refreshing full-screen "live" view for showing standings during a meeting.
- **Fully responsive, mobile-first** — the scorecard is designed to be filled out on a phone right after a meeting; sidebar nav on desktop, bottom tab bar on mobile.

---

## Tech stack

- **Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** Vue 3 (Composition API) + Inertia.js (server-driven SPA, no separate REST/GraphQL API layer)
- **Styling:** Tailwind CSS, fully custom design system (no UI component library) — see `design.md`
- **Database:** MariaDB
- **Build tool:** Vite
- **Testing:** PHPUnit — 198 tests, 764 assertions, covering the scoring engine, the full approval workflow, RBAC boundaries, and every major screen
- **Deployment:** Docker (multi-stage build) + GitHub Actions CI/CD, auto-deploying to production on every push to `main`

---

## Local development

```bash
git clone https://github.com/digitalwebweaver/pluto-premier-league.git
cd pluto-premier-league

composer install
npm install

cp .env.example .env
php artisan key:generate
# edit .env: set DB_DATABASE / DB_USERNAME / DB_PASSWORD for your local MariaDB

php artisan migrate --seed
npm run build      # or `npm run dev` for hot-reload during development

php artisan serve  # → http://127.0.0.1:8000
```

Seeded accounts (all use the same password set in `AuthSeeder`) let you sign in as either the Leadership Team or any team captain to explore both roles immediately after seeding.

### Running the tests

```bash
php artisan test
```

---

## Deployment

The app ships as a multi-stage Docker image (Node build stage for frontend assets, PHP-FPM + Nginx + Supervisor for the runtime). `.github/workflows/deploy.yml` builds the image, pushes it to GitHub Container Registry, and deploys it to the production server over SSH on every push to `main`.

---

## Project structure

```
app/Services/          # ScoringService, EntryScoringService, ApprovalService, StandingsService, ReportService, NotificationService
app/Models/             # Team, Member, Season, Meeting, Category, ScoringRule, MeetingEntry, EntryLine, ...
database/migrations/    # Schema
database/seeders/       # Real chapter data (teams, captains, categories, scoring rules)
resources/js/Pages/     # Inertia pages, split by role: Team/, LT/, Public/, Auth/
resources/js/Components/# Shared design-system components (LeagueTable, TeamCrest, StatusPill, ...)
requirements/           # Functional requirements, organized by area
tests/                  # PHPUnit — Unit (scoring engine) + Feature (every workflow)
```

---

## License & credits

Built for LVB Pluto's Pluto Premier League.

**Digital Web Weaver** · Software Development Company
