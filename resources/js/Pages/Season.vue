<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';

defineProps({
    role: { type: String, default: 'captain' },
    season: { type: Object, default: null },
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: page.props.role ?? 'captain' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');
</script>

<template>
    <Head title="Season summary" />

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">{{ season.name }}</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-ink">Season summary</h1>
            <p class="mt-1 text-sm text-slate">
                Approved points per team, per meeting. Pending cells show <span class="font-mono">—</span>.
            </p>
        </div>
        <a
            v-if="role === 'lt'"
            href="/lt/exports/season.csv"
            class="min-h-9 shrink-0 rounded-input border border-ink bg-white px-4 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
        >
            ↓ Export CSV
        </a>
    </header>

    <!-- Grid (horizontal scroll on small screens) -->
    <div class="overflow-x-auto rounded-lg border border-line bg-white">
        <table class="w-full min-w-[560px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-line">
                    <th class="sticky left-0 z-10 bg-white px-4 py-3 text-left font-mono text-[11px] uppercase tracking-wide text-slate">Team</th>
                    <th
                        v-for="m in meetings"
                        :key="m.sequence_no"
                        class="px-3 py-3 text-center font-mono text-[11px] font-semibold text-slate"
                    >
                        M{{ m.sequence_no }}
                    </th>
                    <th class="px-4 py-3 text-right font-mono text-[11px] uppercase tracking-wide text-slate">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in rows"
                    :key="row.team.id"
                    class="border-b border-paper-2 last:border-b-0"
                    :class="[
                        row.is_current ? 'bg-gold/10' : '',
                        row.is_champion ? 'border-t-2 border-t-gold' : '',
                    ]"
                >
                    <td class="sticky left-0 z-10 px-4 py-3" :class="row.is_current ? 'bg-[#FBF3DE]' : 'bg-white'">
                        <div class="flex items-center gap-2.5">
                            <TeamCrest
                                :name="row.team.name"
                                :initials="row.team.short_code"
                                :color="row.team.crest_color"
                                size="sm"
                                :ring="row.is_champion ? 'gold' : null"
                            />
                            <div class="min-w-0">
                                <p class="truncate font-display text-[14px] font-semibold text-ink">{{ row.team.name }}</p>
                                <p v-if="row.is_champion" class="font-mono text-[10px] font-semibold uppercase text-gold-ink">
                                    {{ season?.is_complete ? 'Champion' : 'Leader' }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td
                        v-for="cell in row.cells"
                        :key="cell.seq"
                        class="px-3 py-3 text-center font-mono"
                        :class="cell.points === null ? 'text-slate/50' : (cell.points ? 'text-ink' : 'text-slate')"
                    >
                        {{ cell.points === null ? '—' : cell.points }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[16px] font-semibold text-ink">{{ inr(row.total) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p v-if="!rows.length" class="mt-4 text-center text-sm text-slate">No active teams yet.</p>
</template>
