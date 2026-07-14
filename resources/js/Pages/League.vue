<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LeagueTable from '@/Components/LeagueTable.vue';

defineProps({
    role: { type: String, default: 'captain' },
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: page.props.role ?? 'captain' }, () => page),
});
</script>

<template>
    <Head title="League table" />

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
            <h1 class="mt-1 font-display text-3xl font-bold text-ink">League Table</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate">
                <span class="h-2.5 w-2.5 rounded-full bg-gold"></span>Approved
            </span>
            <span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate">
                <span class="h-2.5 w-2.5 rounded-full border-[1.5px] border-line"></span>Pending
            </span>
            <a
                v-if="role === 'lt'"
                href="/lt/exports/standings.csv"
                class="min-h-9 rounded-input border border-ink bg-white px-3 py-1.5 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
            >
                ↓ CSV
            </a>
        </div>
    </header>

    <LeagueTable :meetings="meetings" :rows="rows" />
</template>
