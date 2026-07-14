<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TeamCrest from '@/Components/TeamCrest.vue';

defineProps({
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const inr = (n) => Number(n).toLocaleString('en-IN');

// Auto-refresh the standings for projector use (FR-PUB-003).
let timer = null;
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['rows', 'meetings', 'season'], preserveScroll: true });
    }, 30000);
});
onUnmounted(() => timer && clearInterval(timer));

const ringHex = { gold: '#D9A441', silver: '#B8C0CC', bronze: '#B5473A' };
</script>

<template>
    <Head title="Live standings" />

    <div class="min-h-screen text-paper" style="background-image: radial-gradient(circle at 20% 0%, #1B2F52 0%, #12213D 60%)">
        <div class="mx-auto max-w-4xl px-6 py-8">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <p v-if="season" class="font-mono text-sm uppercase tracking-[0.2em] text-silver">{{ season.name }}</p>
                    <h1 class="mt-1 font-display text-5xl font-bold">League Table</h1>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-turf/50 bg-turf/10 px-3 py-1.5 font-mono text-xs font-semibold text-turf">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-turf"></span> LIVE
                </span>
            </header>

            <div class="space-y-2">
                <div
                    v-for="row in rows"
                    :key="row.team.id"
                    class="grid grid-cols-[56px_64px_1fr_auto] items-center gap-5 rounded-xl px-6 py-4"
                    :class="row.rank <= 3 ? 'bg-white/10' : 'bg-white/[0.04]'"
                >
                    <div class="text-center font-mono text-3xl font-semibold" :style="row.ring ? { color: ringHex[row.ring] } : {}">
                        {{ row.rank }}
                    </div>
                    <TeamCrest :name="row.team.name" :initials="row.team.short_code" :color="row.team.crest_color" size="lg" :ring="row.ring" />
                    <div class="min-w-0">
                        <p class="truncate font-display text-2xl font-semibold">{{ row.team.name }}</p>
                        <div class="mt-1.5 flex gap-1.5">
                            <span v-for="d in row.dots" :key="d.seq" class="h-2.5 w-2.5 rounded-full" :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-white/25'"></span>
                        </div>
                    </div>
                    <div class="text-right font-mono text-4xl font-bold">{{ inr(row.total) }}</div>
                </div>
                <p v-if="!rows.length" class="py-20 text-center font-display text-2xl text-silver">Season starting soon…</p>
            </div>

            <p class="mt-8 text-center font-mono text-xs text-silver/70">LVB Pluto Premier League · approved results · updates automatically</p>
        </div>
    </div>
</template>
