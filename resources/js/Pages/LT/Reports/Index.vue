<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    season: { type: Object, default: null },
    teams: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});
</script>

<template>
    <Head title="Reports" />

    <header class="mb-6">
        <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Reports</h1>
        <p class="mt-1 text-sm text-slate">Insights from approved scorecards only.</p>
    </header>

    <EmptyState
        v-if="!teams.length"
        icon="▤"
        title="No approved data yet"
        message="Reports appear once meetings are approved. Approve a submission to get started."
    />

    <template v-else>
        <!-- League-wide -->
        <h2 class="mb-3 font-display text-lg font-semibold">League</h2>
        <div class="mb-6 grid gap-2 sm:grid-cols-2">
            <Link
                href="/lt/reports/categories"
                class="flex items-center gap-3 rounded-lg border border-line bg-white px-5 py-4 transition hover:bg-paper-2"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gold/15 text-lg text-gold-ink">◆</span>
                <div class="flex-1">
                    <p class="font-display text-[15px] font-semibold text-ink">Category leaders</p>
                    <p class="text-sm text-slate">Top team in each category.</p>
                </div>
                <span class="text-slate">›</span>
            </Link>
            <Link
                href="/lt/reports/mvp"
                class="flex items-center gap-3 rounded-lg border border-line bg-white px-5 py-4 transition hover:bg-paper-2"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-turf/15 text-lg text-turf">★</span>
                <div class="flex-1">
                    <p class="font-display text-[15px] font-semibold text-ink">MVP leaderboard</p>
                    <p class="text-sm text-slate">Top individual contributors.</p>
                </div>
                <span class="text-slate">›</span>
            </Link>
        </div>

        <!-- Per team -->
        <h2 class="mb-3 font-display text-lg font-semibold">Team performance</h2>
        <ul class="grid gap-2 sm:grid-cols-2">
            <li v-for="t in teams" :key="t.id">
                <Link
                    :href="`/lt/reports/teams/${t.id}`"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3 transition hover:bg-paper-2"
                >
                    <TeamCrest :name="t.name" :initials="t.short_code" :color="t.crest_color" size="sm" />
                    <span class="flex-1 truncate font-display text-[15px] font-semibold text-ink">{{ t.name }}</span>
                    <span class="text-slate">›</span>
                </Link>
            </li>
        </ul>
    </template>
</template>
