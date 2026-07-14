<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    hasTeam: { type: Boolean, default: true },
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

// Map an entry status → the workflow StatusPill state.
function entryPill(status) {
    return { draft: 'draft', submitted: 'submitted', approved: 'approved', sent_back: 'sentback' }[status] ?? null;
}
function action(m) {
    if (m.entry_status === 'draft') return 'Continue';
    if (m.entry_status === 'sent_back') return 'Fix & resubmit';
    if (m.entry_status === 'submitted') return 'View';
    if (m.entry_status === 'approved') return 'View (locked)';
    if (m.window === 'open') return 'Start';
    return null;
}
function fmt(d) {
    const [y, mo, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${day} ${months[Number(mo) - 1]} ${y}`;
}
</script>

<template>
    <Head title="Submit scores" />

    <header class="mb-6">
        <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">
            {{ season.name }}
        </p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Submit scores</h1>
        <p class="mt-1 text-sm text-slate">Log your team’s scores for each meeting window.</p>
    </header>

    <EmptyState
        v-if="!hasTeam"
        icon="⚑"
        title="No team linked yet"
        message="Ask the Leadership Team to link your account to a team before entering scores."
    />

    <ul v-else class="space-y-2.5">
        <li
            v-for="m in meetings"
            :key="m.id"
            class="flex items-center gap-4 rounded-card border border-line bg-white px-4 py-4"
        >
            <div class="shrink-0 border-r border-dashed border-line pr-4 text-center">
                <div class="font-mono text-2xl font-semibold leading-none text-ink">
                    {{ String(m.sequence_no).padStart(2, '0') }}
                </div>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-display text-[15px] font-semibold text-ink">Meeting {{ m.sequence_no }}</p>
                <p class="font-mono text-xs text-slate">{{ fmt(m.meeting_date) }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <StatusPill :status="m.window" />
                    <StatusPill v-if="entryPill(m.entry_status)" :status="entryPill(m.entry_status)" />
                </div>
            </div>
            <div class="shrink-0">
                <Link
                    v-if="action(m)"
                    :href="`/team/submit/${m.id}`"
                    class="inline-flex min-h-tap items-center rounded-input px-4 text-sm font-semibold transition"
                    :class="m.entry_status === 'sent_back'
                        ? 'bg-gold text-ink hover:brightness-95'
                        : m.entry_status === 'approved'
                            ? 'border border-line text-slate hover:bg-paper-2'
                            : 'bg-ink text-paper hover:bg-ink-2'"
                >
                    {{ action(m) }}
                </Link>
                <span v-else class="font-mono text-xs text-slate">—</span>
            </div>
        </li>
        <li
            v-if="!meetings.length"
            class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate"
        >
            No meetings in the active season yet.
        </li>
    </ul>
</template>
