<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    season: { type: Object, default: null },
    kpis: { type: Object, required: true },
    needsAttention: { type: Array, default: () => [] },
    recentlyApproved: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');

function ago(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' · ' +
        d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}

const QUICK_LINKS = [
    { href: '/lt/teams', label: 'All teams ›', caption: 'Teams', accent: 'border-l-gold' },
    { href: '/lt/meetings', label: 'Meetings ›', caption: 'Season', accent: 'border-l-turf' },
    { href: '/lt/categories', label: 'Categories ›', caption: 'Scoring', accent: 'border-l-ink' },
    { href: '/lt/scoring', label: 'Scoring rules ›', caption: 'Scoring', accent: 'border-l-bronze' },
    { href: '/lt/logins', label: 'Logins ›', caption: 'Access', accent: 'border-l-gold' },
    { href: '/lt/reports', label: 'Reports ›', caption: 'Insights', accent: 'border-l-turf' },
    { href: '/lt/announcements', label: 'Announce ›', caption: 'Broadcast', accent: 'border-l-ink' },
    { href: '/season', label: 'Season recap ›', caption: 'Standings', accent: 'border-l-bronze' },
];
</script>

<template>
    <Head title="LT overview" />

    <header class="mb-6">
        <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Overview</h1>
    </header>

    <!-- KPIs -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Link
            href="/lt/queue"
            class="rounded-card border border-line border-l-[3px] border-l-gold bg-white p-4 transition hover:bg-paper-2"
        >
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Awaiting review</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-ink">{{ kpis.pending_approvals }}</p>
        </Link>
        <Link
            href="/lt/teams"
            class="rounded-card border border-line border-l-[3px] border-l-turf bg-white p-4 transition hover:bg-paper-2"
        >
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Active teams</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-ink">{{ kpis.active_teams }}</p>
        </Link>
        <Link
            href="/lt/meetings"
            class="rounded-card border border-line border-l-[3px] border-l-ink bg-white p-4 transition hover:bg-paper-2"
        >
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Meetings open</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-ink">{{ kpis.meetings_open }} <span class="text-base font-semibold text-slate">/ {{ kpis.meetings_total }}</span></p>
        </Link>
        <Link
            href="/league"
            class="rounded-card border border-line border-l-[3px] border-l-bronze bg-white p-4 transition hover:bg-paper-2"
        >
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">League leader</p>
            <p v-if="kpis.leader" class="mt-1.5 truncate font-display text-2xl font-bold text-ink">{{ kpis.leader.name }}</p>
            <p v-if="kpis.leader" class="font-mono text-xs text-slate">{{ inr(kpis.leader.total) }} pts</p>
            <p v-else class="mt-1.5 font-display text-2xl font-bold text-slate">—</p>
        </Link>
    </div>

    <!-- Needs your attention -->
    <div class="mb-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="font-display text-base font-semibold text-ink">Needs your attention</h2>
            <Link v-if="needsAttention.length" href="/lt/queue" class="text-[13px] font-semibold text-bronze hover:text-ink">View all ›</Link>
        </div>
        <EmptyState
            v-if="!needsAttention.length"
            icon="✓"
            title="Nothing waiting"
            message="Every submitted scorecard has been reviewed. New submissions will show up here."
        />
        <section v-else class="overflow-hidden rounded-lg border border-line bg-white">
            <Link
                v-for="e in needsAttention"
                :key="e.id"
                :href="`/lt/queue/${e.id}`"
                class="flex items-center gap-3 border-b border-paper-2 px-5 py-3.5 transition last:border-b-0 hover:bg-paper-2"
            >
                <TeamCrest :name="e.team.name" :initials="e.team.short_code" :color="e.team.crest_color" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-display text-[15px] font-semibold text-ink">{{ e.team.name }}</p>
                    <p class="text-xs text-slate">Meeting {{ e.meeting.sequence_no }} · submitted {{ ago(e.submitted_at) }}</p>
                </div>
                <span class="font-mono text-sm font-semibold text-ink">{{ inr(e.computed_total) }}</span>
                <StatusPill status="submitted" />
            </Link>
        </section>
    </div>

    <!-- Recently approved -->
    <section v-if="recentlyApproved.length" class="mb-6 rounded-lg border border-line bg-white">
        <div class="flex items-center justify-between border-b border-paper-2 px-5 py-3">
            <h2 class="font-display text-base font-semibold text-ink">Recently approved</h2>
            <Link href="/lt/recent" class="text-[13px] font-semibold text-bronze hover:text-ink">View all ›</Link>
        </div>
        <div
            v-for="e in recentlyApproved"
            :key="e.id"
            class="flex items-center gap-3 border-b border-paper-2 px-5 py-3.5 last:border-b-0"
        >
            <TeamCrest :name="e.team.name" :initials="e.team.short_code" :color="e.team.crest_color" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-[15px] font-semibold text-ink">{{ e.team.name }}</p>
                <p class="text-xs text-slate">
                    Meeting {{ e.meeting.sequence_no }} · approved {{ ago(e.approved_at) }}
                    <span v-if="e.approved_by"> · by {{ e.approved_by }}</span>
                </p>
            </div>
            <span class="font-mono text-sm font-semibold text-turf">{{ inr(e.computed_total) }}</span>
        </div>
    </section>

    <!-- Quick links -->
    <section>
        <p class="mb-2 font-mono text-[11px] uppercase tracking-wide text-slate">Manage</p>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <Link
                v-for="link in QUICK_LINKS"
                :key="link.href"
                :href="link.href"
                class="rounded-card border border-line border-l-[3px] bg-white p-4 transition hover:bg-paper-2"
                :class="link.accent"
            >
                <p class="font-mono text-[11px] uppercase tracking-wide text-slate">{{ link.caption }}</p>
                <p class="mt-1.5 font-display text-lg font-semibold text-ink">{{ link.label }}</p>
            </Link>
        </div>
    </section>
</template>
