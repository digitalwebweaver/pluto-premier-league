<script setup>
import TeamCrest from '@/Components/TeamCrest.vue';

defineProps({
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const inr = (n) => Number(n).toLocaleString('en-IN');
</script>

<template>
    <!-- Desktop grid (≥680px) -->
    <div class="hidden overflow-hidden rounded-lg border border-line bg-white nav:block">
        <div class="grid grid-cols-[44px_48px_minmax(140px,1fr)_auto_120px] items-center gap-4 border-b border-line px-5 py-2.5">
            <div class="text-center font-mono text-[11px] uppercase tracking-wide text-slate">#</div>
            <div></div>
            <div class="font-mono text-[11px] uppercase tracking-wide text-slate">Team</div>
            <div class="text-center font-mono text-[11px] uppercase tracking-wide text-slate">Meetings</div>
            <div class="text-right font-mono text-[11px] uppercase tracking-wide text-slate">Points</div>
        </div>
        <div
            v-for="row in rows"
            :key="row.team.id"
            class="relative grid grid-cols-[44px_48px_minmax(140px,1fr)_auto_120px] items-center gap-4 border-b border-paper-2 px-5 py-3.5 last:border-b-0"
            :class="row.is_current ? 'z-10 bg-gold/12 shadow-row' : ''"
        >
            <div class="text-center font-mono text-lg font-semibold" :class="row.rank <= 3 ? 'text-ink' : 'text-slate'">
                {{ row.rank }}
            </div>
            <div class="text-center font-mono text-[13px] font-semibold">
                <span v-if="row.movement === 'up'" class="text-turf">▲{{ row.movement_by }}</span>
                <span v-else-if="row.movement === 'down'" class="text-bronze">▼{{ row.movement_by }}</span>
                <span v-else class="text-slate">–</span>
            </div>
            <div class="flex min-w-0 items-center gap-3">
                <TeamCrest :name="row.team.name" :initials="row.team.short_code" :color="row.team.crest_color" size="sm" :ring="row.ring" />
                <span class="truncate font-display text-[17px] font-semibold text-ink">{{ row.team.name }}</span>
            </div>
            <div class="flex justify-center gap-1.5">
                <span
                    v-for="d in row.dots"
                    :key="d.seq"
                    class="h-2.5 w-2.5 rounded-full"
                    :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-line'"
                    :title="`Meeting ${d.seq}: ${d.approved ? 'approved' : 'pending'}`"
                ></span>
            </div>
            <div class="text-right font-mono text-[22px] font-semibold text-ink">{{ inr(row.total) }}</div>
        </div>
    </div>

    <!-- Mobile cards (<680px) -->
    <div class="flex flex-col gap-2 nav:hidden">
        <div
            v-for="row in rows"
            :key="row.team.id"
            class="grid grid-cols-[26px_40px_1fr_auto] items-center gap-3 rounded-card border bg-white px-3.5 py-3"
            :class="row.is_current ? 'border-gold/55 bg-gold/12 shadow-row' : 'border-line'"
        >
            <div class="text-center font-mono text-base font-semibold" :class="row.rank <= 3 ? 'text-ink' : 'text-slate'">
                {{ row.rank }}
            </div>
            <TeamCrest :name="row.team.name" :initials="row.team.short_code" :color="row.team.crest_color" size="sm" :ring="row.ring" />
            <div class="min-w-0">
                <p class="truncate font-display text-base font-semibold text-ink">{{ row.team.name }}</p>
                <div class="mt-1 flex gap-1">
                    <span
                        v-for="d in row.dots"
                        :key="d.seq"
                        class="h-2 w-2 rounded-full"
                        :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-line'"
                    ></span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-right">
                <span class="font-mono text-[11px] font-semibold">
                    <span v-if="row.movement === 'up'" class="text-turf">▲</span>
                    <span v-else-if="row.movement === 'down'" class="text-bronze">▼</span>
                    <span v-else class="text-slate">–</span>
                </span>
                <span class="font-mono text-lg font-semibold text-ink">{{ inr(row.total) }}</span>
            </div>
        </div>
    </div>

    <div v-if="!rows.length" class="rounded-lg border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-slate">
        No active teams to rank yet.
    </div>
</template>
