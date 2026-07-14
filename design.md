# Pluto Premier League — Design Documentation

A competitive inter-team scoring platform for **LVB Pluto**, a business-networking
chapter. Teams compete over a season of fortnightly meetings; captains log scores on a
phone, and a Leadership Team (LT) reviews and approves each submission before it counts
on the league table.

> **Emotional job of the design:** make a spreadsheet's worth of data entry feel fast and
> even a little satisfying, and make the standings feel like a real sports league people
> want to climb. **Light theme, mobile-first, one bold thing per screen.**

---

## 1. Deliverables

| Build | Path | What it is |
|---|---|---|
| **Design system + signature screens** | `Pluto Premier League.dc.html` | Theme reference: palette, type, tokens, component previews, League Table + Scorecard at 375 / 768 / 1280. |
| **Interactive prototype** | `Pluto App.dc.html` | Full responsive app (component-format): login, both roles, all key flows. |
| **Separated web build** | `pluto-web/` | Production-style codebase — one HTML file per screen + shared external CSS/JS. |

The `pluto-web/` build is the developer-facing codebase and the subject of the rest of
this document.

---

## 2. Visual system (theme)

### 2.1 Colour palette
Warm, premium, meaning-bearing. Gold / Turf / Bronze carry fixed meaning (rank + status)
and are never used decoratively. The background is warm paper, never stark white.

| Token | Hex | Role |
|---|---|---|
| Ink | `#12213D` | Primary dark — top bar, nav, primary buttons, headings |
| Ink-2 | `#1B2F52` | Secondary dark — gradients, dark cards |
| Paper | `#F6F4EF` | Primary background (warm off-white) |
| Paper-2 | `#EDE9DF` | Secondary surface — inputs, subtle panels |
| Gold | `#D9A441` | 1st place, CTAs, "approved" highlights, brand spark |
| Gold-ink | `#9A6F1E` | Accessible gold text on light surfaces |
| Silver | `#B8C0CC` | 2nd place, neutral secondary badges |
| Bronze | `#B5473A` | 3rd place, error/destructive, "absent/rejected" |
| Turf | `#3F8F6B` | Success / approved / present |
| Slate | `#5A6684` | Secondary text, captions, muted borders |
| Line | `#DAD5C6` | Hairline borders, dividers |

All defined as CSS custom properties in `pluto-web/css/styles.css` (`--ink`, `--gold`, …).

### 2.2 Typography
| Role | Typeface | Weights | Use |
|---|---|---|---|
| Display | Space Grotesk | 600 / 700 | Team names, big point numbers, page titles |
| Body | Inter | 400 / 500 / 600 | All UI text, labels, forms |
| Data / Mono | IBM Plex Mono | 500 / 600 | Points, dates, row numbers, category codes |

**Scale:** 12 caption · 14 body · 16 section label · 22 card title · 32–44 display numbers.
Line-height 1.5 on body, tight on display. Currency in ₹ with Indian digit grouping
(`toLocaleString('en-IN')`).

### 2.3 Spacing, shape, elevation
- **Spacing** — 4px grid: 4 / 8 / 12 / 16 / 24 / 32 (`--s-1` … `--s-8`).
- **Radius** — 8px inputs & buttons, 14–16px cards, full-round avatars/crests/pills.
- **Elevation** — flat by default. Soft shadow **only** on (a) the current team's row in
  the league table (`--shadow-row`) and (b) modals/overlays (`--shadow-modal`). Nothing
  else casts a shadow.

### 2.4 Motion
- Accordion expand/collapse — 180ms ease.
- Running-total number — scale pulse `1 → 1.04 → 1`, 200ms, on change.
- League movement — ▲ up (turf), ▼ down (bronze), – steady (slate).
- Honours `prefers-reduced-motion` (all animation/transition disabled).

### 2.5 Status = colour + icon + label (never colour alone)
| State | Style | Icon |
|---|---|---|
| Draft | slate outline | ✎ |
| Submitted | gold outline | ↑ |
| Approved | turf filled | ✓ |
| Sent back | bronze outline | ↵ |
Meeting windows reuse the same pattern: Open (turf ●), Closed (slate ○), Scheduled (gold ◴).

---

## 3. Core components

Defined once as classes in `styles.css`, rendered by helpers in `app.js`.

- **AppShell** — top bar (`.topbar`) + role nav + content. Sidebar (`.sidebar`) ≥680px,
  bottom tab bar (`.bottom-nav`) <680px. Injected per page from the role's nav config.
- **StatusPill** (`.pill` + modifier) — the four workflow states + window states.
- **TeamCrest** (`.crest`, `.crest--sm/--lg`, `.ring-gold/-silver/-bronze`) — initials on
  the team colour; gold/silver/bronze ring for ranks 1–3, neutral ink otherwise.
- **LeagueTable** — the signature. Desktop grid (`.lt-table`) + mobile cards (`.lt-cards`);
  CSS shows the right one per breakpoint. Movement chevron, per-meeting dots (gold =
  approved, hollow = pending), current row highlighted (gold tint + the one soft shadow).
- **StatCard** (`.stat-card`, `.acc-*`) — one KPI with a coloured left accent.
- **DataTable** (`.dtable`, `.cols-*`) — quiet sortable-style list; each collapses to
  `.dcards` on mobile so no column is ever clipped.
- **Accordion / Scorecard** (`.acc`, `.acc-body`) — collapsible activity with a live
  subtotal shown even when closed; sticky `.sc-footer` running total that pulses.
- **RosterChecklist** (`.roster-row`) — tap to toggle present/absent, on-time/late.
- **NumberStepper** (`.stepper`) — ± counter for small counts, 44px targets.
- **Modal / Confirm** (`.modal-backdrop`, `.modal`) — the unlock guard; the second place a
  shadow is allowed.
- **Toast** (`.toast`) — transient success feedback.
- **EmptyState / Manage links** (`.manage-link`) — never a dead end; always a next step.

---

## 4. Screens (pluto-web build)

**Team Captain** (`data-role="captain"`)
| File | Screen | Notes |
|---|---|---|
| `login.html` | Sign in | Team / LT tabs, gold active underline |
| `dashboard.html` | Team dashboard | Hero standing card, StatCards, embedded league peek |
| `submit.html` | Submit scores | One MeetingTicket-style card per meeting, status-driven action |
| `scorecard.html` | Meeting scorecard | ~18 activity accordions, live subtotals, sticky running total |
| `roster.html` | My roster | Members, business category, active/inactive, captain badge |
| `league.html` | League table | Full standings, own row highlighted (shared page) |
| `season.html` | Season summary | Points-per-meeting bar chart + KPIs |

**Leadership** (`data-role="lt"`)
| File | Screen | Notes |
|---|---|---|
| `overview.html` | LT overview | KPI StatCards, Manage quick-links, "needs your attention" |
| `queue.html` | Approval queue | Worklist of submissions across all teams |
| `review.html` | Review submission | Read-back + Approve (turf) / Send back (bronze) |
| `allteams.html` | All teams | Standing + latest-submission status at a glance |
| `meetings.html` | Meetings | Season meetings + Open/Closed/Scheduled window |
| `scoring.html` | Scoring rules | Activity → subtype → points (the flexibility engine) |
| `recent.html` | Recently approved | Locked entries + guarded Unlock modal |

### Per-screen states designed
Empty (roster/first-run), populated (default), read-only/locked (approved meetings, lock
icon), success feedback (toast on submit/approve/unlock), and validation copy on the
pre-submit review.

---

## 5. Signature screen — League Table (spend the budget here)

Each row shows: **position** (mono), **movement** chevron vs last meeting, **crest** with
rank ring, **team name** (display face), **per-meeting dots** (gold = approved, hollow =
pending — doubles as an "are we up to date" signal), and **points** total (mono, prominent).
The viewer's own team row gets a subtle gold tint and the only soft shadow on the screen.
Works at three sizes: full-page, embedded compact (dashboard peek), and mobile cards.

## 6. Signature screen — Meeting Scorecard (the heart)

~18 activities as collapsed accordions, each showing its subtotal even when closed, so a
captain never opens all of them to see where they stand. Expanding reveals that activity's
own row layout — distinct field controls compose them: date picker, member dropdown, text
field, count stepper, ₹ amount, 1/0 toggle, photo upload, roster checklist. Points are
always auto-calculated. A sticky running total pulses on change. Before submit, a clean
read-back of every activity + grand total confirms before it leaves the captain's hands.

---

## 7. Architecture (pluto-web)

```
pluto-web/
├── login.html              entry — role tabs, writes role to localStorage
├── dashboard.html          ┐
├── submit.html             │ captain screens  (data-role="captain")
├── scorecard.html          │
├── roster.html             │
├── season.html             ┘
├── league.html             shared (role-driven)
├── overview.html           ┐
├── queue.html              │ LT screens       (data-role="lt")
├── review.html             │
├── allteams.html           │
├── meetings.html           │
├── scoring.html            │
├── recent.html             ┘
├── css/styles.css          design tokens + all component classes + media queries
└── js/app.js               shared: data, chrome injection, per-screen renderers
```

- **One stylesheet, one script, no inline styles.** Every page links the same
  `css/styles.css` and `js/app.js`.
- **Page identity** — each `<body>` carries `data-page` (which screen) and, for
  audience-specific screens, `data-role` (`captain` / `lt`). `league.html` is shared and
  omits `data-role`.
- **Chrome injection** — `app.js` reads the page + role and builds the top bar, sidebar,
  and bottom nav into empty `#topbar` / `#sidebar` / `#bottom-nav` containers. Nav items
  are real `<a href>` links; the active item is derived (sub-pages like `scorecard` map to
  their parent nav item `submit` via `ACTIVE_FOR`).
- **Role persistence + reconciliation** — the chosen role is stored in
  `localStorage['pluto-role']`. On load, a page whose `data-role` differs from the stored
  role reconciles the stored role to the page's audience, so chrome always matches content
  no matter how a page is reached (nav, bookmark, or direct URL).
- **Responsiveness is pure CSS.** Base rules are the phone layout; a single
  `@media (min-width: 680px)` swaps bottom-tab → sidebar and card lists → grid tables. No
  JS measures the viewport.

---

## 8. Responsive & accessibility

- Breakpoints designed at **375 / 768 / 1280**; mobile-first. Nav switches at 680px.
- Touch targets ≥ **44px** on interactive rows.
- Status is always **colour + icon + label** — never colour alone (WCAG-safe).
- Visible focus rings on interactive elements; the LT approval flow is keyboard-operable.
- Motion honours `prefers-reduced-motion`.

## 9. Copy voice

Plain, confident, sports-scorecard tone. Buttons say exactly what happens — **Save draft ·
Submit to LT · Approve · Send back · Unlock**. Errors are helpful, not scolding; empty
states point the way forward. Numbers and dates in mono; currency in ₹ with Indian
formatting.

---

## 10. Conventions & patterns

Repeatable rules the codebase follows — apply these when extending it.

**Styling**
- Tokens first: never hard-code a hex/size that a `--*` token exists for.
- Component = one class + modifiers (e.g. `.pill` + `.pill-approved`, `.crest` +
  `.crest--sm` + `.ring-gold`). Keep styling on classes, not inline.
- Mobile-first cascade: write the phone layout as the base rule; add desktop treatment
  inside the single `@media (min-width: 680px)` block. Don't introduce new breakpoints
  without reason.
- Two-layout tables: any multi-column table ships a grid layout **and** a card layout;
  CSS shows one per breakpoint so a column is never clipped on mobile.

**Structure**
- Every screen is its own file with the shell skeleton (`#topbar` / `#sidebar` /
  `#bottom-nav` + a `<main>` with named mount points like `#queue-mount`).
- `<body data-page="…">` names the screen; add `data-role="captain|lt"` on
  audience-specific screens; omit it on shared screens.
- Content mounts into named `#…-mount` containers — `app.js` never assumes DOM order.

**Behaviour**
- Data lives in one place near the top of `app.js` (`TEAMS`, `QUEUE`, `SCORING`, …); a
  `render<Screen>()` function turns data → HTML into its mount. Add a screen by adding its
  data, a renderer, a `boot()` dispatch line, and a nav entry.
- Navigation is real `<a href>`; role state persists in `localStorage` and reconciles to
  the page audience on load.
- Sub-pages map to a parent nav item via `ACTIVE_FOR` for the active highlight.
- Feedback is a toast; destructive/irreversible actions (unlock) go through a guarded
  modal.

**Content**
- Status is always colour + icon + label. Numbers/dates/currency in mono, ₹ with Indian
  grouping. Buttons name the exact action. Empty states point forward.

## 11. Not yet built

Phase-2 and lower-priority screens remaining for a future pass: team detail drill-in,
member add/edit, auth extras (forgot / reset / first-login / account), public + projector
views, error pages (404 / 403 / 500 / maintenance), and full loading-skeleton states.
