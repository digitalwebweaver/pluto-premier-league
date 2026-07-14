<script setup>
import TeamCrest from '@/Components/TeamCrest.vue';

defineProps({
    meetings: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const inr = (n) => Number(n).toLocaleString('en-IN');
</script>

<template>
    <!-- Column labels (desktop only — cards are self-explanatory on mobile) -->
    <div
        v-if="rows.length"
        class="mb-2 hidden grid-cols-[56px_96px_1fr_auto_140px] items-center gap-4 px-5 nav:grid"
    >
        <div class="text-center font-mono text-[11px] uppercase tracking-wide text-slate">#</div>
        <div></div>
        <div class="font-mono text-[11px] uppercase tracking-wide text-slate">Team</div>
        <div class="text-center font-mono text-[11px] uppercase tracking-wide text-slate">Meetings</div>
        <div class="text-right font-mono text-[11px] uppercase tracking-wide text-slate">Points</div>
    </div>

    <!-- One big card per team, every breakpoint -->
    <div class="flex flex-col gap-3">
        <div
            v-for="row in rows"
            :key="row.team.id"
            class="grid grid-cols-[40px_68px_1fr_auto] items-center gap-3 rounded-card border bg-white px-4 py-4 nav:grid-cols-[56px_96px_1fr_auto_140px] nav:gap-4 nav:px-5 nav:py-5"
            :class="row.is_current ? 'border-gold/55 bg-gold/12 shadow-row' : 'border-line'"
        >
            <div class="text-center font-mono text-xl font-semibold nav:text-2xl" :class="row.rank <= 3 ? 'text-ink' : 'text-slate'">
                {{ row.rank }}
            </div>

            <TeamCrest
                :name="row.team.name"
                :initials="row.team.short_code"
                :color="row.team.crest_color"
                :logo-path="row.team.logo_path"
                size="lg"
                class="nav:hidden"
                :ring="row.ring"
            />
            <TeamCrest
                :name="row.team.name"
                :initials="row.team.short_code"
                :color="row.team.crest_color"
                :logo-path="row.team.logo_path"
                size="xl"
                class="hidden nav:flex"
                :ring="row.ring"
            />

            <div class="min-w-0">
                <p class="truncate font-display text-lg font-bold text-ink nav:text-xl">{{ row.team.name }}</p>
                <!-- Meeting dots: inline under the name on mobile (no spare column there) -->
                <div class="mt-2 flex gap-1 nav:hidden">
                    <span
                        v-for="d in row.dots"
                        :key="d.seq"
                        class="h-2 w-2 rounded-full"
                        :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-line'"
                    ></span>
                </div>
            </div>

            <!-- Meeting dots: own column on desktop, matching the "Meetings" header -->
            <div class="hidden justify-center gap-1.5 nav:flex">
                <span
                    v-for="d in row.dots"
                    :key="d.seq"
                    class="h-2.5 w-2.5 rounded-full"
                    :class="d.approved ? 'bg-gold' : 'border-[1.5px] border-line'"
                    :title="`Meeting ${d.seq}: ${d.approved ? 'approved' : 'pending'}`"
                ></span>
            </div>

            <div class="text-right">
                <div class="font-mono text-[13px] font-semibold">
                    <span v-if="row.movement === 'up'" class="text-turf">▲{{ row.movement_by }}</span>
                    <span v-else-if="row.movement === 'down'" class="text-bronze">▼{{ row.movement_by }}</span>
                    <span v-else class="text-slate">–</span>
                </div>
                <div class="font-mono text-2xl font-bold text-ink nav:text-[28px]">{{ inr(row.total) }}</div>
            </div>
        </div>
    </div>

    <div v-if="!rows.length" class="rounded-lg border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-slate">
        No active teams to rank yet.
    </div>
</template>
