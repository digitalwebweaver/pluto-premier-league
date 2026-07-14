<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeamCrest from '@/Components/TeamCrest.vue';

defineProps({
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const inr = (n) => Number(n).toLocaleString('en-IN');
</script>

<template>
    <Head title="Season summary" />

    <div class="min-h-screen bg-paper">
        <header class="flex h-[60px] items-center justify-between bg-ink px-5 text-paper">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold font-display text-base font-bold text-ink">P</span>
                <span class="font-display text-base font-semibold">Pluto <span class="font-medium text-silver">PL</span></span>
            </div>
            <nav class="flex items-center gap-4 font-mono text-[12px] font-semibold">
                <Link href="/public/league" class="text-silver hover:text-paper">Table</Link>
                <span class="text-gold">Season</span>
                <Link href="/public/live" class="text-silver hover:text-paper">Live ↗</Link>
            </nav>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 md:px-8">
            <header class="mb-6">
                <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
                <h1 class="mt-1 font-display text-3xl font-bold text-ink">Season Summary</h1>
            </header>

            <div class="overflow-x-auto rounded-lg border border-line bg-white">
                <table class="w-full min-w-[560px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="sticky left-0 z-10 bg-white px-4 py-3 text-left font-mono text-[11px] uppercase tracking-wide text-slate">Team</th>
                            <th v-for="m in meetings" :key="m.sequence_no" class="px-3 py-3 text-center font-mono text-[11px] font-semibold text-slate">M{{ m.sequence_no }}</th>
                            <th class="px-4 py-3 text-right font-mono text-[11px] uppercase tracking-wide text-slate">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.team.id" class="border-b border-paper-2 last:border-b-0" :class="row.is_champion ? 'border-t-2 border-t-gold' : ''">
                            <td class="sticky left-0 z-10 bg-white px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <TeamCrest :name="row.team.name" :initials="row.team.short_code" :color="row.team.crest_color" size="sm" :ring="row.is_champion ? 'gold' : null" />
                                    <div>
                                        <p class="truncate font-display text-[14px] font-semibold text-ink">{{ row.team.name }}</p>
                                        <p v-if="row.is_champion" class="font-mono text-[10px] font-semibold uppercase text-gold-ink">{{ season?.is_complete ? 'Champion' : 'Leader' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td v-for="cell in row.cells" :key="cell.seq" class="px-3 py-3 text-center font-mono" :class="cell.points === null ? 'text-slate/50' : (cell.points ? 'text-ink' : 'text-slate')">
                                {{ cell.points === null ? '—' : cell.points }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-[16px] font-semibold text-ink">{{ inr(row.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-6 text-center font-mono text-[11px] text-slate">Approved results only · LVB Pluto Premier League</p>
        </main>
    </div>
</template>
