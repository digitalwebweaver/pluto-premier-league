<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    entries: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

function ago(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) +
        ' · ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Approval queue" />

    <header class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">Approval queue</h1>
            <p class="mt-1 text-sm text-slate">Submitted scorecards awaiting your review.</p>
        </div>
        <Link href="/lt/recent" class="mt-1 shrink-0 text-[13px] font-semibold text-bronze hover:text-ink">
            Recently approved →
        </Link>
    </header>

    <EmptyState
        v-if="!entries.length"
        icon="✓"
        title="You're all caught up"
        message="No submissions are waiting. When a captain submits a scorecard, it lands here."
    />

    <ul v-else class="space-y-2">
        <li
            v-for="e in entries"
            :key="e.id"
            class="flex items-center gap-4 rounded-card border border-line bg-white px-4 py-3"
        >
            <TeamCrest :name="e.team.name" :initials="e.team.short_code" :color="e.team.crest_color" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-[15px] font-semibold text-ink">{{ e.team.name }}</p>
                <p class="font-mono text-xs text-slate">
                    Meeting {{ e.meeting.sequence_no }} · {{ ago(e.submitted_at) }}
                </p>
            </div>
            <span class="font-mono text-lg font-semibold text-ink">{{ e.computed_total }}</span>
            <Link
                :href="`/lt/queue/${e.id}`"
                class="min-h-9 shrink-0 rounded-input bg-ink px-4 py-2 text-[13px] font-semibold text-paper transition hover:bg-ink-2"
            >
                Review
            </Link>
        </li>
    </ul>
</template>
