<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    notifications: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const META = {
    approved: { icon: '✓', accent: 'text-turf', bg: 'bg-turf/10' },
    sent_back: { icon: '↵', accent: 'text-bronze', bg: 'bg-bronze/10' },
    corrected: { icon: '✎', accent: 'text-gold-ink', bg: 'bg-gold/10' },
    new_meeting: { icon: '◴', accent: 'text-gold-ink', bg: 'bg-gold/10' },
    announcement: { icon: '📣', accent: 'text-ink', bg: 'bg-paper-2' },
};
function meta(t) {
    return META[t] ?? { icon: '•', accent: 'text-slate', bg: 'bg-paper-2' };
}
function title(n) {
    const p = n.payload ?? {};
    return {
        approved: `Meeting ${p.meeting} approved`,
        sent_back: `Meeting ${p.meeting} sent back`,
        corrected: `Meeting ${p.meeting} corrected by the LT`,
        new_meeting: `New meeting ${p.meeting} scheduled`,
        announcement: 'Announcement from the Leadership Team',
    }[n.type] ?? 'Notification';
}
function body(n) {
    const p = n.payload ?? {};
    return {
        approved: `${p.total} points now count on the league table.`,
        sent_back: p.note,
        corrected: `${p.reason} (new total: ${p.total})`,
        new_meeting: p.date ? `Meeting date: ${p.date}.` : '',
        announcement: p.body,
    }[n.type] ?? '';
}
function ago(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' · ' +
        d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Notifications" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Notifications</h1>
        <p class="mt-1 text-sm text-slate">Approvals, send-backs, new meetings, and announcements.</p>
    </header>

    <EmptyState
        v-if="!notifications.length"
        icon="🔔"
        title="Nothing yet"
        message="You’ll hear here when the LT approves or sends back your scorecards, or posts an announcement."
    />

    <ul v-else class="space-y-2">
        <li
            v-for="n in notifications"
            :key="n.id"
            class="flex items-start gap-3 rounded-card border bg-white px-4 py-3"
            :class="n.is_new ? 'border-gold/50' : 'border-line'"
        >
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-base" :class="[meta(n.type).bg, meta(n.type).accent]">
                {{ meta(n.type).icon }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-display text-[15px] font-semibold text-ink">{{ title(n) }}</p>
                    <span v-if="n.is_new" class="rounded-full bg-gold px-1.5 py-px font-mono text-[9px] font-bold text-ink">NEW</span>
                </div>
                <p v-if="body(n)" class="mt-0.5 text-sm text-slate">{{ body(n) }}</p>
                <p class="mt-1 font-mono text-[11px] text-slate/70">{{ ago(n.created_at) }}</p>
            </div>
        </li>
    </ul>
</template>
