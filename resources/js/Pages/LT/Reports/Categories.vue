<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');
const maxTotal = computed(() => Math.max(1, ...props.categories.map((c) => c.total)));
</script>

<template>
    <Head title="Category leaders" />

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <Link href="/lt/reports" class="text-[13px] font-semibold text-slate hover:text-ink">← Reports</Link>
            <h1 class="mt-2 font-display text-2xl font-bold text-ink">Category leaders</h1>
            <p class="mt-1 text-sm text-slate">League-wide approved points per category, and the leading team.</p>
        </div>
        <a
            href="/lt/exports/category-leaders.csv"
            class="min-h-9 shrink-0 rounded-input border border-ink bg-white px-4 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
        >
            ↓ Export CSV
        </a>
    </header>

    <ul class="space-y-2">
        <li
            v-for="c in categories"
            :key="c.code"
            class="rounded-card border border-line bg-white px-5 py-4"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold text-slate">{{ c.code }}</span>
                    <span class="font-display text-[15px] font-semibold text-ink">{{ c.name }}</span>
                </div>
                <span class="font-mono text-lg font-semibold text-ink">{{ inr(c.total) }}</span>
            </div>
            <!-- share bar -->
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper-2">
                <div class="h-full rounded-full bg-gold" :style="{ width: (c.total / maxTotal * 100) + '%' }"></div>
            </div>
            <div v-if="c.leader" class="mt-2 flex items-center gap-2 text-sm text-slate">
                <TeamCrest :name="c.leader.name" :initials="c.leader.short_code" :color="c.leader.crest_color" size="sm" />
                <span class="font-semibold text-ink">{{ c.leader.name }}</span>
                <span>leads with</span>
                <span class="font-mono font-semibold text-gold-ink">{{ inr(c.leader.points) }}</span>
            </div>
        </li>
        <li v-if="!categories.length" class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate">
            No approved category data yet.
        </li>
    </ul>
</template>
