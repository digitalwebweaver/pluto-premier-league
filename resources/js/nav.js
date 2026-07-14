// Role nav config. `href` is a real path (design.md: navigation is real <a href>).
// `bottom: true` = also shown in the phone bottom-tab bar (<680px).
// Placeholder destinations exist as routes now; real screens land in later phases.

export const NAV = {
    captain: [
        { key: 'dashboard', href: '/team', label: 'Dashboard', icon: '⌂', bottom: true },
        { key: 'submit', href: '/team/submit', label: 'Submit', icon: '✎', bottom: true },
        { key: 'roster', href: '/team/roster', label: 'Roster', icon: '☰', bottom: true },
        { key: 'league', href: '/league', label: 'Table', icon: '▦', bottom: true },
        { key: 'season', href: '/season', label: 'Season', icon: '◆', bottom: true },
        { key: 'profile', href: '/team/profile', label: 'My team', icon: '⚑' },
    ],
    lt: [
        { key: 'overview', href: '/lt', label: 'Overview', icon: '◫', bottom: true },
        { key: 'queue', href: '/lt/queue', label: 'Approvals', icon: '☰', badgeKey: 'pendingApprovals', bottom: true },
        { key: 'teams', href: '/lt/teams', label: 'All teams', icon: '⚑', bottom: true },
        { key: 'meetings', href: '/lt/meetings', label: 'Meetings', icon: '▣' },
        { key: 'categories', href: '/lt/categories', label: 'Categories', icon: '❏' },
        { key: 'scoring', href: '/lt/scoring', label: 'Scoring rules', icon: '≡' },
        { key: 'logins', href: '/lt/logins', label: 'Logins', icon: '⚿' },
        { key: 'league', href: '/league', label: 'Table', icon: '▦', bottom: true },
        { key: 'season', href: '/season', label: 'Season', icon: '◆' },
        { key: 'reports', href: '/lt/reports', label: 'Reports', icon: '▤' },
        { key: 'announce', href: '/lt/announcements', label: 'Announce', icon: '📣' },
    ],
};

// Sub-pages map to their parent nav item for the active highlight (design.md §7).
export const ACTIVE_FOR = {
    '/team/scorecard': '/team/submit',
    '/lt/review': '/lt/queue',
    '/lt/recent': '/lt/queue',
};

export const HOME = { captain: '/team', lt: '/lt' };
