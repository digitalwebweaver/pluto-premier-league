<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';

const props = defineProps({
    team: { type: Object, required: true },
    byCategory: { type: Array, default: () => [] },
    byMeeting: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const inr = (n) => Number(n).toLocaleString('en-IN');
const maxCat = computed(() => Math.max(1, ...props.byCategory.map((c) => c.points)));
const maxMtg = computed(() => Math.max(1, ...props.byMeeting.map((m) => m.total)));
</script>

<template>
    <Head :title="`Report — ${team.name}`" />

    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <Link href="/lt/reports" class="text-[13px] font-semibold text-slate hover:text-ink">← Reports</Link>
            <div class="mt-2 flex items-center gap-3">
                <TeamCrest :name="team.name" :initials="team.short_code" :color="team.crest_color" />
                <div>
                    <h1 class="font-display text-2xl font-bold text-ink">{{ team.name }}</h1>
                    <p class="font-mono text-sm text-slate">{{ inr(total) }} approved points this season</p>
                </div>
            </div>
        </div>
        <a
            :href="`/lt/exports/teams/${team.id}.csv`"
            class="min-h-9 shrink-0 rounded-input border border-ink bg-white px-4 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
        >
            ↓ Export CSV
        </a>
    </header>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Points by category -->
        <section class="rounded-lg border border-line bg-white p-5">
            <h2 class="mb-4 font-display text-base font-semibold">Points by category</h2>
            <ul class="space-y-3">
                <li v-for="c in byCategory" :key="c.code">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-ink">{{ c.name }}</span>
                        <span class="font-mono font-semibold text-ink">{{ inr(c.points) }}</span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-paper-2">
                        <div class="h-full rounded-full bg-turf" :style="{ width: (c.points / maxCat * 100) + '%' }"></div>
                    </div>
                </li>
                <li v-if="!byCategory.length" class="text-sm text-slate">No approved data yet.</li>
            </ul>
        </section>

        <!-- Points by meeting -->
        <section class="rounded-lg border border-line bg-white p-5">
            <h2 class="mb-4 font-display text-base font-semibold">Points by meeting</h2>
            <div class="flex items-end justify-between gap-2" style="height: 180px">
                <div v-for="m in byMeeting" :key="m.sequence_no" class="flex flex-1 flex-col items-center justify-end gap-2">
                    <span class="font-mono text-[11px] font-semibold text-ink">{{ m.total }}</span>
                    <div class="w-full max-w-[40px] rounded-t bg-ink" :style="{ height: (m.total / maxMtg * 130) + 'px' }"></div>
                    <span class="font-mono text-[11px] text-slate">M{{ m.sequence_no }}</span>
                </div>
                <p v-if="!byMeeting.length" class="text-sm text-slate">No approved meetings yet.</p>
            </div>
        </section>
    </div>
</template>
