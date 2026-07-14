<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';

const props = defineProps({
    season: { type: Object, default: null },
    standing: { type: Object, default: null },
    teamCount: { type: Number, default: 0 },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');
const ordinal = (n) => {
    const s = ['th', 'st', 'nd', 'rd'], v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
};
const approvedCount = computed(() => props.standing?.dots?.filter((d) => d.approved).length ?? 0);
</script>

<template>
    <Head title="Team dashboard" />

    <header class="mb-6">
        <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Team dashboard</h1>
    </header>

    <!-- Hero standing -->
    <div
        v-if="standing"
        class="mb-4 flex flex-wrap items-center justify-between gap-6 rounded-lg p-6 text-paper"
        style="background-image: linear-gradient(135deg, var(--ink-2), var(--ink))"
    >
        <div class="flex items-center gap-5">
            <TeamCrest
                :name="standing.team.name"
                :initials="standing.team.short_code"
                :color="standing.team.crest_color"
                size="lg"
                :ring="standing.ring"
            />
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-silver">{{ standing.team.name }}</p>
                <div class="mt-1 flex items-baseline gap-3">
                    <span class="font-display text-4xl font-bold leading-none">{{ ordinal(standing.rank) }}</span>
                    <span class="text-sm font-semibold">{{ inr(standing.total) }} pts</span>
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="flex justify-end gap-1.5">
                <span
                    v-for="d in standing.dots"
                    :key="d.seq"
                    class="h-2.5 w-2.5 rounded-full"
                    :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-white/30'"
                ></span>
            </div>
            <p class="mt-2 text-[13px] text-silver">{{ approvedCount }} of {{ standing.dots.length }} meetings approved</p>
        </div>
    </div>
    <div v-else class="mb-4 rounded-lg border border-line bg-white px-5 py-6 text-sm text-slate">
        Your team isn’t on the table yet — it appears once the Leadership Team links your account and approves a meeting.
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Link href="/team/submit" class="rounded-card border border-line border-l-[3px] border-l-gold bg-white p-4 transition hover:bg-paper-2">
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Scores</p>
            <p class="mt-1.5 font-display text-lg font-semibold text-ink">Submit ›</p>
        </Link>
        <Link href="/team/roster" class="rounded-card border border-line border-l-[3px] border-l-turf bg-white p-4 transition hover:bg-paper-2">
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Members</p>
            <p class="mt-1.5 font-display text-lg font-semibold text-ink">Roster ›</p>
        </Link>
        <Link href="/league" class="rounded-card border border-line border-l-[3px] border-l-ink bg-white p-4 transition hover:bg-paper-2">
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Standings</p>
            <p class="mt-1.5 font-display text-lg font-semibold text-ink">Table ›</p>
        </Link>
        <Link href="/season" class="rounded-card border border-line border-l-[3px] border-l-bronze bg-white p-4 transition hover:bg-paper-2">
            <p class="font-mono text-[11px] uppercase tracking-wide text-slate">Recap</p>
            <p class="mt-1.5 font-display text-lg font-semibold text-ink">Season ›</p>
        </Link>
    </div>
</template>
