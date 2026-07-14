<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});
</script>

<template>
    <Head title="LT overview" />

    <header class="mb-6">
        <p class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">
            Leadership
        </p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Overview</h1>
        <p class="mt-1 text-sm text-slate">
            Placeholder shell — KPIs, manage quick-links and "needs your attention" land in Phase 2+.
        </p>
    </header>

    <!-- KPI placeholders -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div
            v-for="n in 4"
            :key="n"
            class="rounded-card border border-line bg-white p-4"
            :class="['border-l-[3px]', ['border-l-gold','border-l-turf','border-l-ink','border-l-bronze'][n-1]]"
        >
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">KPI {{ n }}</p>
            <p class="mt-1.5 font-display text-2xl font-bold text-ink">—</p>
        </div>
    </div>

    <!-- Needs attention (placeholder) -->
    <section class="rounded-lg border border-line bg-white">
        <h2 class="border-b border-paper-2 px-5 py-3 font-display text-base font-semibold">
            Needs your attention
        </h2>
        <div
            v-for="n in 3"
            :key="n"
            class="flex items-center gap-3 border-b border-paper-2 px-5 py-3.5 last:border-b-0"
        >
            <span
                class="flex h-11 w-11 items-center justify-center rounded-full bg-slate/20 font-display text-sm font-semibold text-slate"
                >T{{ n }}</span
            >
            <div class="flex-1">
                <p class="font-display text-[15px] font-semibold text-ink">Team {{ n }}</p>
                <p class="text-xs text-slate">Meeting 0{{ n }} · awaiting review</p>
            </div>
            <StatusPill status="submitted" />
        </div>
    </section>
</template>
