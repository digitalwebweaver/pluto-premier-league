<script setup>
import { Head, Link } from '@inertiajs/vue3';
import LeagueTable from '@/Components/LeagueTable.vue';

defineProps({
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="League Table" />

    <div class="min-h-screen bg-paper">
        <!-- Public header (no auth chrome) -->
        <header class="flex h-[60px] items-center justify-between bg-ink px-5 text-paper">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold font-display text-base font-bold text-ink">P</span>
                <span class="font-display text-base font-semibold">Pluto <span class="font-medium text-silver">PL</span></span>
            </div>
            <nav class="flex items-center gap-4 font-mono text-[12px] font-semibold">
                <span class="text-gold">Table</span>
                <Link href="/public/season" class="text-silver hover:text-paper">Season</Link>
                <Link href="/public/live" class="text-silver hover:text-paper">Live ↗</Link>
            </nav>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 md:px-8">
            <header class="mb-6">
                <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
                <h1 class="mt-1 font-display text-3xl font-bold text-ink">League Table</h1>
                <p v-if="!rows.length || rows.every(r => !r.total)" class="mt-1 text-sm text-slate">
                    Season starting soon — standings appear once meetings are approved.
                </p>
            </header>

            <LeagueTable :meetings="meetings" :rows="rows" />

            <p class="mt-6 text-center font-mono text-[11px] text-slate">Approved results only · LVB Pluto Premier League</p>
        </main>
    </div>
</template>
