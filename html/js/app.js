/* ============================================================
   Pluto Premier League — shared application script (multi-page)
   Every screen is its own .html file. They all load this script.
   The script reads <body data-page="..."> to know which screen it
   is on, injects the shared chrome (top bar + nav) for the current
   role, and renders that screen's dynamic content.
   Role is persisted in localStorage so it survives page loads.
   Responsive behaviour lives entirely in styles.css (media queries).
   ============================================================ */
(function () {
  "use strict";

  /* -------------------- helpers -------------------- */
  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));
  function el(tag, cls, html) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }
  const inr = (n) => Number(n).toLocaleString("en-IN");
  const ROLE_KEY = "pluto-role";
  const getRole = () => localStorage.getItem(ROLE_KEY) || "captain";
  const setRole = (r) => localStorage.setItem(ROLE_KEY, r);

  /* -------------------- data -------------------- */
  const TEAMS = [
    { pos: 1, name: "Digital Titans",  ini: "DT", color: "#1B2F52", captain: "R. Menon",     pts: 1860, move: "up",   moveVal: 2, ring: "gold",   dots: [1,1,1,1,1,0] },
    { pos: 2, name: "Growth Circle",   ini: "GC", color: "#3F6F8F", captain: "S. Kapoor",    pts: 1640, move: "flat", moveVal: 0, ring: "silver", dots: [1,1,1,1,0,0] },
    { pos: 3, name: "Momentum Makers", ini: "MM", color: "#7A5C3E", captain: "A. Iyer",      pts: 1420, move: "down", moveVal: 1, ring: "bronze", dots: [1,1,1,0,0,0] },
    { pos: 4, name: "Prime Movers",    ini: "PM", color: "#4B5A78", captain: "N. Shah",      pts: 1180, move: "flat", moveVal: 0, ring: "none",   dots: [1,1,1,1,0,0] },
    { pos: 5, name: "Apex Alliance",   ini: "AA", color: "#5E7B6B", captain: "You · P. Rao", pts: 1050, move: "up",   moveVal: 1, ring: "none",   dots: [1,1,1,0,0,0], current: true },
    { pos: 6, name: "Vertex Group",    ini: "VG", color: "#8A6D4B", captain: "D. Nair",      pts: 920,  move: "down", moveVal: 1, ring: "none",   dots: [1,1,0,0,0,0] },
    { pos: 7, name: "Summit Squad",    ini: "SS", color: "#556074", captain: "K. Reddy",     pts: 780,  move: "flat", moveVal: 0, ring: "none",   dots: [1,1,1,0,0,0] },
    { pos: 8, name: "Catalyst Crew",   ini: "CC", color: "#6B5340", captain: "M. Joshi",     pts: 610,  move: "up",   moveVal: 1, ring: "none",   dots: [1,1,0,0,0,0] }
  ];
  const MEMBERS = ["P. Rao", "A. Desai", "N. Verma", "S. Pillai", "R. Khanna", "M. Bose", "T. Ghosh", "V. Sinha"];
  const AV = ["#5E7B6B", "#3F6F8F", "#7A5C3E", "#4B5A78", "#8A6D4B", "#556074", "#6B5340", "#1B2F52"];
  const initials = (n) => n.replace(/[^A-Za-z ]/g, "").split(" ").filter(Boolean).map((w) => w[0]).slice(0, 2).join("").toUpperCase();

  const QUEUE = [
    { team: "Momentum Makers", ini: "MM", color: "#7A5C3E", mtg: "05", when: "2d ago", pts: "+240" },
    { team: "Prime Movers",    ini: "PM", color: "#4B5A78", mtg: "06", when: "1d ago", pts: "+290" },
    { team: "Growth Circle",   ini: "GC", color: "#3F6F8F", mtg: "06", when: "5h ago", pts: "+310" },
    { team: "Digital Titans",  ini: "DT", color: "#1B2F52", mtg: "06", when: "2h ago", pts: "+360" }
  ];

  /* captain's season of meetings */
  const CAPTAIN_MEETINGS = [
    { no: "01", date: "01 Jul", status: "approved",  pts: 290 },
    { no: "02", date: "15 Jul", status: "approved",  pts: 310 },
    { no: "03", date: "29 Jul", status: "approved",  pts: 300 },
    { no: "04", date: "12 Aug", status: "sent-back", pts: 180 },
    { no: "05", date: "26 Aug", status: "submitted", pts: 270 },
    { no: "06", date: "09 Sep", status: "draft",     pts: 0 }
  ];
  const MEETING_ACTION = { draft: "Continue", "sent-back": "Fix & resubmit", submitted: "View", approved: "View (locked)" };

  /* captain roster */
  const ROSTER = [
    { name: "P. Rao",    cat: "Financial Advisor",     active: true, captain: true },
    { name: "A. Desai",  cat: "Interior Design",       active: true },
    { name: "N. Verma",  cat: "Textiles Export",       active: true },
    { name: "S. Pillai", cat: "Legal Services",        active: true },
    { name: "R. Khanna", cat: "Digital Marketing",     active: true },
    { name: "M. Bose",   cat: "Real Estate",           active: true },
    { name: "T. Ghosh",  cat: "Chartered Accountant",  active: false },
    { name: "V. Sinha",  cat: "Event Management",      active: true }
  ];

  /* season bars (captain points per meeting) */
  const SEASON_BARS = [290, 310, 300, 180, 270, 0];

  /* LT — all teams (standing + latest submission) */
  const ALL_TEAMS = [
    { pos: 1, name: "Digital Titans",  ini: "DT", color: "#1B2F52", pts: 1860, latest: "approved" },
    { pos: 2, name: "Growth Circle",   ini: "GC", color: "#3F6F8F", pts: 1640, latest: "submitted" },
    { pos: 3, name: "Momentum Makers", ini: "MM", color: "#7A5C3E", pts: 1420, latest: "submitted" },
    { pos: 4, name: "Prime Movers",    ini: "PM", color: "#4B5A78", pts: 1180, latest: "submitted" },
    { pos: 5, name: "Apex Alliance",   ini: "AA", color: "#5E7B6B", pts: 1050, latest: "draft" },
    { pos: 6, name: "Vertex Group",    ini: "VG", color: "#8A6D4B", pts: 920,  latest: "draft" },
    { pos: 7, name: "Summit Squad",    ini: "SS", color: "#556074", pts: 780,  latest: "approved" },
    { pos: 8, name: "Catalyst Crew",   ini: "CC", color: "#6B5340", pts: 610,  latest: "sent-back" }
  ];

  /* LT — meetings list */
  const MEETINGS = [
    { no: "01", date: "01 Jul 26", window: "closed",    subs: "8 / 8" },
    { no: "02", date: "15 Jul 26", window: "closed",    subs: "8 / 8" },
    { no: "03", date: "29 Jul 26", window: "closed",    subs: "8 / 8" },
    { no: "04", date: "12 Aug 26", window: "closed",    subs: "8 / 8" },
    { no: "05", date: "26 Aug 26", window: "closed",    subs: "8 / 8" },
    { no: "06", date: "09 Sep 26", window: "open",      subs: "5 / 8" },
    { no: "07", date: "23 Sep 26", window: "scheduled", subs: "\u2014" }
  ];
  const WINDOW = {
    open:      { cls: "pill-open",      ico: "\u25CF", label: "Open" },
    closed:    { cls: "pill-closed",    ico: "\u25CB", label: "Closed" },
    scheduled: { cls: "pill-scheduled", ico: "\u25F4", label: "Scheduled" }
  };

  /* LT — scoring rules */
  const SCORING = [
    { code: "VIS", name: "Visitors", rules: [["Closed", "100"], ["Open", "50"], ["Hot", "25"], ["Repeat", "15"]] },
    { code: "REF", name: "Referrals", rules: [["Same team", "10"], ["Cross team / chapter", "20"], ["Cross region / commissioner", "30"]] },
    { code: "V2V", name: "V2V", rules: [["Same team", "20"], ["Cross team / chapter", "40"], ["Cross region", "60"], ["ED", "80"]] },
    { code: "ATT", name: "Attendance", rules: [["Present (per member)", "50"], ["Absent", "0"]] },
    { code: "PUN", name: "Punctuality", rules: [["On time (per member)", "20"], ["Late", "0"]] },
    { code: "TYF", name: "Thank You Notes (TYFCB)", rules: [["Per note filed", "250"], ["Amount recorded", "\u20B9 value"]] }
  ];

  /* LT — recently approved (unlockable) */
  const RECENT = [
    { team: "Digital Titans", ini: "DT", color: "#1B2F52", mtg: "05", when: "28 Aug", pts: "+330" },
    { team: "Summit Squad",   ini: "SS", color: "#556074", mtg: "06", when: "11 Sep", pts: "+180" },
    { team: "Apex Alliance",  ini: "AA", color: "#5E7B6B", mtg: "03", when: "30 Jul", pts: "+300" }
  ];

  /* pre-submit / read-back summary */
  const REVIEW_SUMMARY = [
    { name: "Visitors", detail: "1 visitor \u00b7 A. Desai", pts: "+300" },
    { name: "Referrals", detail: "4 members", pts: "+200" },
    { name: "V2V", detail: "4 members", pts: "+800" },
    { name: "Attendance", detail: "6 of 8 present", pts: "+300" },
    { name: "Punctuality", detail: "5 of 8 on time", pts: "+100" },
    { name: "Inductions", detail: "2 inducted", pts: "+150" },
    { name: "Thank You Notes", detail: "\u20B92,50,000", pts: "+250" },
    { name: "+ 6 more activities", detail: "Trainings, JP, Social, Testimonials\u2026", pts: "+780" }
  ];

  const PILL = {
    draft:       { cls: "pill-draft",     ico: "\u270E", label: "Draft" },
    submitted:   { cls: "pill-submitted", ico: "\u2191", label: "Submitted" },
    approved:    { cls: "pill-approved",  ico: "\u2713", label: "Approved" },
    "sent-back": { cls: "pill-sentback",  ico: "\u21B5", label: "Sent back" }
  };
  const pill = (status) => {
    const p = PILL[status] || PILL.draft;
    return '<span class="pill ' + p.cls + '"><span class="ico">' + p.ico + "</span>" + p.label + "</span>";
  };

  const VP = { hot: 25, open: 50, closed: 100, repeat: 15 };
  const ACTIVITIES = [
    { id: "visitors", code: "VIS", name: "Visitors", type: "visitors" },
    { id: "referrals", code: "REF", name: "Referrals", type: "fixed", fixed: 200 },
    { id: "v2v", code: "V2V", name: "V2V", type: "fixed", fixed: 800 },
    { id: "attendance", code: "ATT", name: "Attendance", type: "roster", mode: "att" },
    { id: "punctuality", code: "PUN", name: "Punctuality", type: "roster", mode: "pun" },
    { id: "inductions", code: "IND", name: "Inductions", type: "fixed", fixed: 150 },
    { id: "tyfcb", code: "TYF", name: "Thank You Notes (TYFCB)", type: "fixed", fixed: 250 }
  ];

  /* scorecard working state (per page load) */
  const sc = {
    open: { visitors: true, attendance: true },
    visitors: { hot: 0, open: 2, closed: 2, repeat: 0 },
    attendance: [true, true, true, true, true, true, false, false],
    punctuality: [true, true, true, true, true, false, false, false]
  };

  /* -------------------- navigation config -------------------- */
  /* sidebar = full destination list per role; bottom = the phone tab bar subset */
  const NAV = {
    captain: [
      { page: "dashboard", href: "dashboard.html", label: "Dashboard", icon: "\u2302" },
      { page: "submit",    href: "submit.html",    label: "Submit", icon: "\u270E" },
      { page: "roster",    href: "roster.html",    label: "Roster", icon: "\u2630" },
      { page: "league",    href: "league.html",    label: "Table", icon: "\u25A6" },
      { page: "season",    href: "season.html",    label: "Season", icon: "\u25C6" }
    ],
    lt: [
      { page: "overview",  href: "overview.html",  label: "Overview", icon: "\u25EB" },
      { page: "queue",     href: "queue.html",     label: "Approvals", icon: "\u2630", badge: "3" },
      { page: "allteams",  href: "allteams.html",  label: "All teams", icon: "\u2691" },
      { page: "meetings",  href: "meetings.html",  label: "Meetings", icon: "\u25A3" },
      { page: "scoring",   href: "scoring.html",   label: "Scoring rules", icon: "\u2261" },
      { page: "league",    href: "league.html",    label: "Table", icon: "\u25A6" }
    ]
  };
  const BOTTOM = {
    captain: null, /* null = same as sidebar (5 fits) */
    lt: ["overview", "queue", "allteams", "league"]
  };
  /* which nav item is highlighted for a given page (sub-pages map to their parent) */
  const ACTIVE_FOR = {
    scorecard: "submit", meetingread: "submit",
    review: "queue", recent: "queue",
    teamdetail: "allteams"
  };
  const HOME = { captain: "dashboard.html", lt: "overview.html" };


  /* -------------------- subtotals -------------------- */
  const visitorSub = () => sc.visitors.hot * VP.hot + sc.visitors.open * VP.open + sc.visitors.closed * VP.closed + sc.visitors.repeat * VP.repeat;
  const rosterSub = (mode) => (mode === "att" ? sc.attendance : sc.punctuality).filter(Boolean).length * (mode === "att" ? 50 : 20);
  const activitySub = (a) => a.type === "visitors" ? visitorSub() : a.type === "roster" ? rosterSub(a.mode) : a.fixed;
  const grandTotal = () => ACTIVITIES.reduce((t, a) => t + activitySub(a), 0);

  /* ============================================================
     Chrome (top bar + nav) — shared across every screen
     ============================================================ */
  function crestHtml(t, size) {
    const ring = t.ring && t.ring !== "none" ? " ring-" + t.ring : "";
    return '<span class="crest' + (size ? " " + size : "") + ring + '" style="background:' + t.color + '">' + t.ini + "</span>";
  }
  function dotsHtml(dots) { return dots.map((d) => '<span class="dot ' + (d ? "approved" : "pending") + '"></span>').join(""); }
  function deltaHtml(t) {
    if (t.move === "up") return '<span class="lt-delta delta-up">\u25B2' + t.moveVal + "</span>";
    if (t.move === "down") return '<span class="lt-delta delta-down">\u25BC' + t.moveVal + "</span>";
    return '<span class="lt-delta delta-flat">\u2013</span>';
  }

  function buildChrome(page, role) {
    /* top bar */
    const topbar = $("#topbar");
    if (topbar) {
      const idHtml = role === "captain"
        ? '<div class="who"><div class="n">Apex Alliance</div><div class="r">Captain</div></div>' +
          '<span class="crest crest--sm" style="background:#5E7B6B;box-shadow:0 0 0 2px #12213D,0 0 0 3.5px #5A6684">AA</span>'
        : '<div class="who"><div class="n">Leadership</div><div class="r">Admin</div></div><span class="lt-badge">LT</span>';
      topbar.innerHTML =
        '<a class="brand" href="' + HOME[role] + '"><div class="mark">P</div><div class="name">Pluto <span>PL</span></div></a>' +
        '<div class="topbar-right">' +
          '<button class="role-switch" id="role-switch">\u2646 <span>' + (role === "captain" ? "View as LT" : "View as Captain") + "</span></button>" +
          '<div class="identity">' + idHtml + "</div>" +
        "</div>";
      $("#role-switch").addEventListener("click", () => {
        const next = role === "captain" ? "lt" : "captain";
        setRole(next); location.href = HOME[next];
      });
    }

    /* nav item markup (used by both sidebar and bottom bar) */
    const items = NAV[role];
    const activeKey = ACTIVE_FOR[page] || page;
    const navLink = (it, tab) => {
      const active = it.page === activeKey ? " active" : "";
      const badge = it.badge ? '<span class="count">' + it.badge + "</span>" : "";
      if (tab) return '<a class="tab' + active + '" href="' + it.href + '"><span class="ico">' + it.icon + '</span><span class="lbl">' + it.label + "</span>" + badge + "</a>";
      return '<a class="nav-item' + active + '" href="' + it.href + '"><span class="ico">' + it.icon + '</span><span class="lbl">' + it.label + "</span>" + badge + "</a>";
    };

    const sidebar = $("#sidebar");
    if (sidebar) {
      sidebar.innerHTML =
        '<div class="eyebrow nav-label">' + (role === "captain" ? "Team Captain" : "Leadership") + "</div>" +
        items.map((it) => navLink(it, false)).join("") +
        '<div class="spacer"></div>' +
        '<a class="nav-item" href="login.html" id="sign-out"><span class="ico">\u23CF</span><span class="lbl">Sign out</span></a>';
      $("#sign-out").addEventListener("click", () => localStorage.removeItem(ROLE_KEY));
    }

    const bottom = $("#bottom-nav");
    const bottomItems = BOTTOM[role] ? items.filter((it) => BOTTOM[role].indexOf(it.page) !== -1) : items;
    if (bottom) bottom.innerHTML = bottomItems.map((it) => navLink(it, true)).join("");
  }

  /* ============================================================
     Screen renderers (each targets mounts on its own page)
     ============================================================ */
  function renderLeague(mount) {
    if (!mount) return;
    const head =
      '<div class="lt-head">' +
        '<div><div class="eyebrow">Season 4 · Meeting 6 of 12</div>' +
        '<h1 style-hook="lt-title">League Table</h1></div>' +
        '<div class="lt-legend"><span><span class="dot approved"></span>Approved</span>' +
        '<span><span class="dot pending"></span>Pending</span></div>' +
      "</div>";

    let table = '<div class="lt-table"><div class="lt-grid head"><div style-hook="c">#</div><div></div><div>Team</div>' +
      '<div style-hook="c">Meetings</div><div style-hook="r">Pts</div><div style-hook="c">\u0394</div></div><div class="lt-rows">';
    TEAMS.forEach((t) => {
      table += '<div class="lt-grid lt-row' + (t.pos <= 3 ? " top3" : "") + (t.current ? " current" : "") + '">' +
        '<div class="lt-pos">' + t.pos + "</div>" +
        '<div style-hook="center">' + crestHtml(t) + "</div>" +
        '<div><div class="lt-name">' + t.name + '</div><div class="lt-cap">' + t.captain + "</div></div>" +
        '<div class="lt-dots">' + dotsHtml(t.dots) + "</div>" +
        '<div class="lt-pts">' + inr(t.pts) + "</div>" + deltaHtml(t) + "</div>";
    });
    table += "</div></div>";

    let cards = '<div class="lt-cards">';
    TEAMS.forEach((t) => {
      cards += '<div class="lt-card' + (t.current ? " current" : "") + '">' +
        '<div class="lt-pos">' + t.pos + "</div>" + crestHtml(t, "crest--sm") +
        '<div><div class="lt-name">' + t.name + '</div><div class="lt-dots">' + dotsHtml(t.dots) + "</div></div>" +
        '<div><div class="lt-pts">' + inr(t.pts) + "</div>" + deltaHtml(t) + "</div></div>";
    });
    cards += "</div>";

    mount.innerHTML = head + table + cards;
  }

  function renderDashboard() {
    const hero = $("#dash-hero");
    if (hero) {
      hero.className = "dash-hero";
      hero.innerHTML =
        '<div class="dash-hero-inner">' +
          '<div class="dash-hero-left">' + crestHtml(TEAMS[4], "crest--lg") +
            '<div><div class="eyebrow">Current standing</div>' +
            '<div class="dash-rank"><span class="display">5th</span> <span class="mono delta-up">\u25B21</span></div>' +
            '<div class="mono dash-pts">1,050 <span class="muted">pts</span></div></div></div>' +
          '<div class="dash-hero-right"><div class="eyebrow">Up to date · 3 of 6</div>' +
          '<div class="lt-dots dash-dots">' + dotsHtml(TEAMS[4].dots) + "</div>" +
          '<div class="muted dash-behind">810 pts behind Digital Titans</div></div>' +
        "</div>";
    }
    const stats = [
      { label: "Position", val: "5th", sub: "\u25B21 this meeting", acc: "acc-turf" },
      { label: "Season points", val: "1,050", sub: "810 behind 1st", acc: "acc-ink" },
      { label: "This meeting", val: "Draft", sub: "Meeting 06 · due 23 Sep", acc: "acc-slate" },
      { label: "Up to date", val: "3 / 6", sub: "meetings approved", acc: "acc-gold" }
    ];
    const sg = $("#dash-stats");
    if (sg) sg.innerHTML = statCards(stats);
    renderLeague($("#dash-league"));
  }

  function statCards(stats) {
    return stats.map((s) =>
      '<div class="stat-card ' + s.acc + '"><div class="eyebrow">' + s.label + "</div>" +
      '<div class="stat-val">' + s.val + '</div><div class="muted" style-hook="sm">' + s.sub + "</div></div>"
    ).join("");
  }

  function renderOverview() {
    const stats = [
      { label: "Pending approvals", val: "3", sub: "across all teams", acc: "acc-gold" },
      { label: "Meetings open", val: "1", sub: "Meeting 06 · closes 25 Sep", acc: "acc-ink" },
      { label: "Teams behind", val: "2", sub: "missing latest scores", acc: "acc-bronze" },
      { label: "Approved this mtg", val: "5", sub: "of 8 teams", acc: "acc-turf" }
    ];
    const sg = $("#lt-stats");
    if (sg) sg.innerHTML = statCards(stats);

    const mount = $("#lt-attention");
    if (mount) {
      const list = el("div", "dtable");
      QUEUE.slice().reverse().forEach((q) => {
        const row = el("div", "attention-row");
        row.innerHTML =
          '<span class="crest crest--sm" style="background:' + q.color + '">' + q.ini + "</span>" +
          '<div class="attention-main"><div class="t">' + q.team + '</div><div class="s">Meeting ' + q.mtg + " · submitted " + q.when + "</div></div>" +
          pill("submitted") +
          '<a class="btn btn-primary btn-sm" href="review.html">Review</a>';
        list.appendChild(row);
      });
      mount.innerHTML = "";
      mount.appendChild(list);
    }
  }

  function renderQueue() {
    const mount = $("#queue-mount");
    if (!mount) return;
    let table = '<div class="dtable cols-queue"><div class="thead"><div>Team</div><div>Meeting</div><div>Submitted</div><div style-hook="r">Points</div><div></div></div>';
    QUEUE.forEach((q) => {
      table += '<div class="trow"><div class="cell-team"><span class="crest crest--sm" style="background:' + q.color + '">' + q.ini + '</span><span class="tname">' + q.team + "</span></div>" +
        '<div class="mono">M' + q.mtg + '</div><div class="muted">' + q.when + '</div><div class="mono" style-hook="r">' + q.pts + "</div>" +
        '<a class="btn btn-outline btn-sm" href="review.html">Review</a></div>';
    });
    table += "</div>";
    let cards = '<div class="dcards">';
    QUEUE.forEach((q) => {
      cards += '<div class="dcard"><div class="dcard-top"><span class="crest crest--sm" style="background:' + q.color + '">' + q.ini + "</span>" +
        '<div class="dcard-main"><div class="t">' + q.team + '</div><div class="s">Meeting ' + q.mtg + " · " + q.when + " · " + q.pts + "</div></div></div>" +
        '<a class="btn btn-primary btn-block" href="review.html" style-hook="mt-sm">Review</a></div>';
    });
    cards += "</div>";
    mount.innerHTML = table + cards;
  }

  function renderScorecard() {
    const mount = $("#scorecard-mount");
    if (!mount) return;
    mount.innerHTML = "";

    const header = el("div", "sc-headerbar");
    header.innerHTML = '<div class="meta"><div class="eyebrow">Meeting 06 · 09 Sep 26</div><div class="team">Apex Alliance · Scorecard</div></div>' + pill("draft");
    mount.appendChild(header);

    const accWrap = el("div", "accordions");
    ACTIVITIES.forEach((a) => {
      const open = !!sc.open[a.id];
      const sub = activitySub(a);
      const acc = el("div", "acc" + (open ? " open" : ""));
      const head = el("button", "acc-head");
      head.innerHTML =
        '<span class="acc-chev">\u25B8</span><span class="acc-code">' + a.code + '</span>' +
        '<span class="acc-name">' + a.name + '</span>' +
        '<span class="acc-sub' + (sub > 0 ? "" : " zero") + '">' + (sub > 0 ? "+" + inr(sub) + " pts" : "\u2014") + "</span>";
      head.addEventListener("click", () => { sc.open[a.id] = !sc.open[a.id]; renderScorecard(); });
      acc.appendChild(head);

      if (open) {
        const body = el("div", "acc-body");
        if (a.type === "visitors") {
          body.innerHTML = '<div class="vis-fields">' + ["Hot", "Open", "Closed", "Repeat"].map(stepperHtml).join("") + "</div>";
          $$(".stepper", body).forEach((st) => {
            const f = st.getAttribute("data-field");
            $$("button", st).forEach((b) => b.addEventListener("click", () => {
              sc.visitors[f] = Math.max(0, Math.min(20, sc.visitors[f] + Number(b.getAttribute("data-step"))));
              renderScorecard(); pulseTotal();
            }));
          });
        } else if (a.type === "roster") {
          const arr = a.mode === "att" ? sc.attendance : sc.punctuality;
          MEMBERS.forEach((name, i) => {
            const on = arr[i];
            const label = a.mode === "att" ? (on ? "Present" : "Absent") : (on ? "On time" : "Late");
            const row = el("button", "roster-row");
            row.innerHTML =
              '<span class="crest crest--sm" style="background:' + AV[i % AV.length] + '">' + initials(name) + "</span>" +
              '<span class="roster-name">' + name + "</span>" +
              '<span class="pill ' + (on ? "pill-approved" : "pill-sentback") + '"><span class="ico">' + (on ? "\u2713" : "\u2715") + "</span>" + label + "</span>";
            row.addEventListener("click", () => { arr[i] = !arr[i]; renderScorecard(); pulseTotal(); });
            body.appendChild(row);
          });
        } else {
          body.innerHTML = '<div class="muted">Row detail — date, member and auto-calculated points.</div>';
        }
        acc.appendChild(body);
      }
      accWrap.appendChild(acc);
    });
    mount.appendChild(accWrap);

    const footer = el("div", "sc-footer");
    footer.innerHTML = '<div class="sc-total-row"><span class="eyebrow">Running total</span><span class="sc-total mono" id="sc-total">' + inr(grandTotal()) + " pts</span></div>";
    const btns = el("div", "sc-btns");
    const draft = el("button", "btn btn-outline btn-block", "Save draft");
    const submit = el("button", "btn btn-primary btn-block", "Submit to LT");
    draft.addEventListener("click", () => toast("Draft saved"));
    submit.addEventListener("click", () => toast("Submitted to LT"));
    btns.appendChild(draft); btns.appendChild(submit);
    footer.appendChild(btns);
    mount.appendChild(footer);
  }
  function stepperHtml(field) {
    const f = field.toLowerCase();
    return '<div class="field"><div class="field-label">' + field + '</div><div class="stepper" data-field="' + f + '">' +
      '<button data-step="-1">\u2212</button><span class="val">' + sc.visitors[f] + '</span><button data-step="1">+</button></div></div>';
  }
  function pulseTotal() {
    const t = $("#sc-total");
    if (!t) return;
    t.classList.remove("pulse"); void t.offsetWidth; t.classList.add("pulse");
  }

  /* ---- Submit scores · meeting list (B2) ---- */
  function renderSubmit() {
    const mount = $("#submit-mount");
    if (!mount) return;
    let html = '<div class="meeting-list">';
    CAPTAIN_MEETINGS.forEach((m) => {
      const primary = m.status === "draft";
      const href = m.status === "draft" ? "scorecard.html" : (m.status === "sent-back" ? "scorecard.html" : "scorecard.html");
      html += '<div class="meeting-row"><div class="meeting-no"><div class="eyebrow">MTG</div><div class="display num">' + m.no + "</div></div>" +
        '<div class="meeting-mid"><div class="mono meeting-date">' + m.date + "</div>" + pill(m.status) + "</div>" +
        '<div class="meeting-end"><div class="mono meeting-pts' + (m.pts > 0 ? "" : " zero") + '">' + (m.pts > 0 ? "+" + m.pts : "\u2014") + "</div>" +
        '<a class="btn btn-sm ' + (primary ? "btn-primary" : "btn-ghost") + '" href="' + href + '">' + (MEETING_ACTION[m.status] || "Open") + "</a></div></div>";
    });
    html += "</div>";
    mount.innerHTML = html;
  }

  /* ---- My roster (B10) ---- */
  function renderRoster() {
    const mount = $("#roster-mount");
    if (!mount) return;
    let html = '<div class="roster-list">';
    ROSTER.forEach((m, i) => {
      const stat = m.active ? "active" : "inactive";
      html += '<div class="roster-card"><span class="crest crest--sm" style="background:' + AV[i % AV.length] + '">' + initials(m.name) + "</span>" +
        '<div class="roster-info"><div class="rn">' + m.name + (m.captain ? ' <span class="cap-badge">CAPTAIN</span>' : "") + '</div><div class="rc">' + m.cat + "</div></div>" +
        '<span class="pill ' + (m.active ? "pill-open" : "pill-closed") + '"><span class="ico">' + (m.active ? "\u25CF" : "\u25CB") + "</span>" + (m.active ? "Active" : "Inactive") + "</span></div>";
    });
    html += "</div>";
    mount.innerHTML = html;
  }

  /* ---- Season summary (B14) ---- */
  function renderSeason() {
    const mount = $("#season-mount");
    if (!mount) return;
    const max = Math.max.apply(null, SEASON_BARS);
    let bars = '<div class="card card--pad"><div class="season-bars">';
    SEASON_BARS.forEach((p, i) => {
      const h = p > 0 ? Math.max(12, Math.round((p / max) * 150)) : 6;
      const fill = p > 0 ? (p === max ? "var(--gold)" : "var(--turf)") : "var(--line)";
      bars += '<div class="season-bar"><span class="mono bar-val' + (p > 0 ? "" : " zero") + '">' + (p > 0 ? p : "\u2014") + "</span>" +
        '<div class="bar" style="height:' + h + "px;background:" + fill + '"></div><span class="mono bar-lbl">M' + (i + 1) + "</span></div>";
    });
    bars += "</div></div>";
    const stats = '<div class="stat-grid" style-hook="mt-sm">' + statCards([
      { label: "Season points", val: "1,050", sub: "across 6 meetings", acc: "acc-ink" },
      { label: "Best meeting", val: "M2 · 310", sub: "your season high", acc: "acc-turf" },
      { label: "Current rank", val: "5th", sub: "\u25B21 this meeting", acc: "acc-gold" }
    ]) + "</div>";
    mount.innerHTML = bars + stats;
  }

  /* ---- All teams (C7) ---- */
  function renderAllTeams() {
    const mount = $("#allteams-mount");
    if (!mount) return;
    let table = '<div class="dtable cols-allteams"><div class="thead"><div style-hook="c">#</div><div>Team</div><div style-hook="r">Points</div><div>Meeting 06</div><div></div></div>';
    ALL_TEAMS.forEach((t) => {
      table += '<div class="trow"><div class="mono" style-hook="c">' + t.pos + '</div><div class="cell-team"><span class="crest crest--sm" style="background:' + t.color + '">' + t.ini + '</span><span class="tname">' + t.name + "</span></div>" +
        '<div class="mono" style-hook="r">' + inr(t.pts) + "</div><div>" + pill(t.latest) + "</div>" +
        '<a class="btn btn-ghost btn-sm" href="allteams.html">View</a></div>';
    });
    table += "</div>";
    let cards = '<div class="dcards">';
    ALL_TEAMS.forEach((t) => {
      cards += '<div class="dcard"><div class="dcard-top"><span class="mono team-pos">' + t.pos + '</span><span class="crest crest--sm" style="background:' + t.color + '">' + t.ini + "</span>" +
        '<div class="dcard-main"><div class="t">' + t.name + '</div><div class="s mono">' + inr(t.pts) + ' pts</div></div>' + pill(t.latest) + "</div></div>";
    });
    cards += "</div>";
    mount.innerHTML = table + cards;
  }

  /* ---- Meetings (C9) ---- */
  function renderMeetings() {
    const mount = $("#meetings-mount");
    if (!mount) return;
    const wpill = (w) => { const x = WINDOW[w]; return '<span class="pill ' + x.cls + '"><span class="ico">' + x.ico + "</span>" + x.label + "</span>"; };
    let table = '<div class="dtable cols-meetings"><div class="thead"><div>Mtg</div><div>Date</div><div>Window</div><div style-hook="r">Subs</div><div></div></div>';
    MEETINGS.forEach((m) => {
      table += '<div class="trow"><div class="display num-sm">' + m.no + '</div><div class="mono">' + m.date + "</div><div>" + wpill(m.window) + "</div>" +
        '<div class="mono" style-hook="r">' + m.subs + '</div><a class="btn btn-ghost btn-sm" href="meetings.html">Edit</a></div>';
    });
    table += "</div>";
    let cards = '<div class="dcards">';
    MEETINGS.forEach((m) => {
      cards += '<div class="dcard meeting-dcard"><div class="meeting-no"><div class="eyebrow">MTG</div><div class="display num">' + m.no + '</div></div>' +
        '<div class="dcard-main"><div class="mono meeting-date">' + m.date + "</div><div style-hook='mt-xs'>" + wpill(m.window) + " <span class='mono muted'>" + m.subs + "</span></div></div>" +
        '<a class="btn btn-ghost btn-sm" href="meetings.html">Edit</a></div>';
    });
    cards += "</div>";
    mount.innerHTML = table + cards;
  }

  /* ---- Scoring rules (C12) ---- */
  function renderScoring() {
    const mount = $("#scoring-mount");
    if (!mount) return;
    let html = '<div class="rule-groups">';
    SCORING.forEach((a) => {
      html += '<div class="card rule-group"><div class="rule-head"><span class="acc-code">' + a.code + '</span><span class="rule-name">' + a.name + "</span></div>";
      a.rules.forEach((r) => {
        html += '<div class="rule-row"><span>' + r[0] + '</span><span class="mono rule-pts">' + r[1] + "</span></div>";
      });
      html += "</div>";
    });
    html += "</div>";
    mount.innerHTML = html;
  }

  /* ---- Recently approved (C5) + unlock (C6) ---- */
  function renderRecent() {
    const mount = $("#recent-mount");
    if (!mount) return;
    const list = el("div", "dtable");
    RECENT.forEach((r) => {
      const row = el("div", "attention-row");
      row.innerHTML =
        '<span class="crest crest--sm" style="background:' + r.color + '">' + r.ini + "</span>" +
        '<div class="attention-main"><div class="t">' + r.team + ' <span class="lock">\uD83D\uDD12</span></div><div class="s">Meeting ' + r.mtg + " \u00b7 approved " + r.when + " \u00b7 " + r.pts + "</div></div>";
      const btn = el("button", "btn btn-bronze btn-sm", "Unlock");
      btn.addEventListener("click", () => openUnlock(r));
      row.appendChild(btn);
      list.appendChild(row);
    });
    mount.innerHTML = "";
    mount.appendChild(list);
  }
  function openUnlock(r) {
    let root = $("#modal-root");
    if (!root) { root = el("div"); root.id = "modal-root"; document.body.appendChild(root); }
    root.innerHTML =
      '<div class="modal-backdrop"><div class="modal">' +
      '<div class="modal-icon">\uD83D\uDD13</div>' +
      "<h3>Unlock " + r.team + " \u00b7 Meeting " + r.mtg + "?</h3>" +
      "<p>This reopens an approved entry. Its points come off the league table until the team resubmits and you approve it again.</p>" +
      '<div class="modal-actions"><button class="btn btn-ghost" data-close>Cancel</button>' +
      '<button class="btn btn-danger" data-confirm>\uD83D\uDD13 Unlock entry</button></div></div></div>';
    $("[data-close]", root).addEventListener("click", () => { root.innerHTML = ""; });
    $("[data-confirm]", root).addEventListener("click", () => { root.innerHTML = ""; toast("Entry unlocked — back with the team"); });
    $(".modal-backdrop", root).addEventListener("click", (e) => { if (e.target === $(".modal-backdrop", root)) root.innerHTML = ""; });
  }

  /* ---- Review submission (C3) ---- */
  function renderReview() {
    const mount = $("#review-mount");
    if (!mount) return;
    let head =
      '<a class="back-link" href="queue.html">\u2190 Back to queue</a>' +
      '<div class="card review-head"><div class="review-team"><span class="crest crest--sm" style="background:#1B2F52">DT</span>' +
      '<div><div class="review-name">Digital Titans</div><div class="s muted">Meeting 06 · submitted 2h ago · <span class="mono">+360 pts</span></div></div></div>' +
      '<div class="review-actions">' + pill("submitted") +
      '<button class="btn btn-bronze" id="review-sendback">\u21B5 Send back</button>' +
      '<button class="btn btn-turf" id="review-approve">\u2713 Approve</button></div></div>' +
      '<div class="mono muted review-note">Read-only review · figures locked as submitted</div>';
    let summary = '<div class="dtable review-summary">';
    REVIEW_SUMMARY.forEach((r) => {
      summary += '<div class="attention-row"><div class="attention-main"><div class="t">' + r.name + '</div><div class="s">' + r.detail + "</div></div>" +
        '<div class="mono review-pts">' + r.pts + "</div></div>";
    });
    summary += '<div class="review-total"><span class="eyebrow">Grand total</span><span class="mono">2,880 pts</span></div></div>';
    mount.innerHTML = head + summary;
    $("#review-approve").addEventListener("click", () => { toast("Approved · Digital Titans M06"); setTimeout(() => location.href = "queue.html", 900); });
    $("#review-sendback").addEventListener("click", () => { toast("Sent back with a note"); setTimeout(() => location.href = "queue.html", 900); });
  }

  /* ---- toast ---- */
  let toastTimer;
  function toast(msg) {
    let root = $("#toast-root");
    if (!root) { root = el("div"); root.id = "toast-root"; document.body.appendChild(root); }
    root.innerHTML = '<div class="toast"><span class="ok">\u2713</span>' + msg + "</div>";
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { root.innerHTML = ""; }, 2200);
  }

  /* ============================================================
     Login page
     ============================================================ */
  function initLogin() {
    let tab = "team";
    $$(".tab-btn").forEach((b) => b.addEventListener("click", () => {
      tab = b.getAttribute("data-login-tab");
      $$(".tab-btn").forEach((x) => x.classList.toggle("active", x === b));
      $("#login-id-label").textContent = tab === "team" ? "Team code" : "Email";
      $("#login-id").value = tab === "team" ? "apex-alliance" : "lt@lvbpluto.org";
    }));
    $("#sign-in").addEventListener("click", () => {
      const role = tab === "team" ? "captain" : "lt";
      setRole(role);
      location.href = HOME[role];
    });
  }

  /* ============================================================
     Boot
     ============================================================ */
  function boot() {
    const page = document.body.getAttribute("data-page");
    if (page === "login") { initLogin(); return; }

    /* Reconcile role with the page's audience so chrome always matches content.
       Captain-only pages force 'captain', LT-only pages force 'lt';
       shared pages (no data-role) follow the stored role. */
    const audience = document.body.getAttribute("data-role");
    let role = getRole();
    if (audience && audience !== role) { setRole(audience); role = audience; }

    buildChrome(page, role);

    if (page === "dashboard") renderDashboard();
    else if (page === "league") renderLeague($("#league-mount"));
    else if (page === "scorecard") renderScorecard();
    else if (page === "overview") renderOverview();
    else if (page === "queue") renderQueue();
    else if (page === "submit") renderSubmit();
    else if (page === "roster") renderRoster();
    else if (page === "season") renderSeason();
    else if (page === "allteams") renderAllTeams();
    else if (page === "meetings") renderMeetings();
    else if (page === "scoring") renderScoring();
    else if (page === "recent") renderRecent();
    else if (page === "review") renderReview();
  }

  document.addEventListener("DOMContentLoaded", boot);
})();
