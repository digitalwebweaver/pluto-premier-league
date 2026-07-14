<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    filter: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    leaders: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');
function setFilter(code) {
    router.get('/lt/reports/mvp', code ? { category: code } : {}, { preserveScroll: true, preserveState: true });
}
const rankStyle = (i) =>
    ['bg-gold text-ink', 'bg-silver text-ink', 'bg-bronze text-white'][i] ?? 'bg-paper-2 text-slate';
</script>

<template>
    <Head title="MVP leaderboard" />

    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <Link href="/lt/reports" class="text-[13px] font-semibold text-slate hover:text-ink">← Reports</Link>
            <h1 class="mt-2 font-display text-2xl font-bold text-ink">MVP leaderboard</h1>
            <p class="mt-1 text-sm text-slate">Top individual contributors from approved scorecards, across all teams.</p>
        </div>
        <a
            :href="filter ? `/lt/exports/mvp.csv?category=${filter}` : '/lt/exports/mvp.csv'"
            class="min-h-9 shrink-0 rounded-input border border-ink bg-white px-4 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
        >
            ↓ Export CSV
        </a>
    </header>

    <!-- Category filter -->
    <div class="mb-4 flex flex-wrap gap-2">
        <button
            type="button"
            class="rounded-full border px-3 py-1.5 text-[13px] font-semibold transition"
            :class="!filter ? 'border-ink bg-ink text-paper' : 'border-line bg-white text-slate hover:bg-paper-2'"
            @click="setFilter(null)"
        >
            All categories
        </button>
        <button
            v-for="c in categories"
            :key="c.code"
            type="button"
            class="rounded-full border px-3 py-1.5 text-[13px] font-semibold transition"
            :class="filter === c.code ? 'border-ink bg-ink text-paper' : 'border-line bg-white text-slate hover:bg-paper-2'"
            @click="setFilter(c.code)"
        >
            {{ c.name }}
        </button>
    </div>

    <EmptyState
        v-if="!leaders.length"
        icon="★"
        title="No individual data yet"
        message="Member contributions appear once scorecards with member rows are approved."
    />

    <ul v-else class="space-y-2">
        <li
            v-for="(m, i) in leaders"
            :key="m.name + i"
            class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
        >
            <span class="flex h-8 w-8 items-center justify-center rounded-full font-mono text-sm font-semibold" :class="rankStyle(i)">
                {{ i + 1 }}
            </span>
            <TeamCrest :name="m.name" :color="m.avatar_color" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-[15px] font-semibold text-ink">{{ m.name }}</p>
                <p class="truncate text-xs text-slate">{{ m.team }}</p>
            </div>
            <span class="font-mono text-lg font-semibold text-ink">{{ inr(m.points) }}</span>
        </li>
    </ul>
</template>
