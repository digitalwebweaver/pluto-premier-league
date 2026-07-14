<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    meeting: { type: Object, required: true },
    entry: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const allZero = props.categories.every((c) => c.points === 0);

const form = useForm({});
function submit() {
    form.post(route('team.submit.submit', props.meeting.id));
}
function fmt(d) {
    const [y, mo, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${day} ${months[Number(mo) - 1]} ${y}`;
}
</script>

<template>
    <Head :title="`Review — Meeting ${meeting.sequence_no}`" />

    <header class="mb-6">
        <Link :href="`/team/submit/${meeting.id}`" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← Back to edit</Link
        >
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">Review &amp; submit</h1>
        <p class="mt-1 text-sm text-slate">
            Meeting {{ meeting.sequence_no }} · {{ fmt(meeting.meeting_date) }} — check everything before it goes to the LT.
        </p>
    </header>

    <div class="mb-4 overflow-hidden rounded-lg border border-line bg-white">
        <div
            v-for="c in categories"
            :key="c.id"
            class="flex items-center justify-between border-b border-paper-2 px-5 py-3 last:border-b-0"
        >
            <div class="flex items-center gap-3">
                <span class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold text-slate">{{ c.code }}</span>
                <span class="text-sm font-medium text-ink">{{ c.name }}</span>
            </div>
            <span class="font-mono text-sm font-semibold" :class="c.points ? 'text-ink' : 'text-slate'">
                {{ c.points }}
            </span>
        </div>
    </div>

    <!-- Grand total -->
    <div class="mb-4 flex items-center justify-between rounded-lg bg-ink px-5 py-4 text-paper">
        <span class="font-mono text-[11px] uppercase tracking-wide text-silver">Grand total</span>
        <span class="font-mono text-3xl font-semibold">{{ total }}</span>
    </div>

    <p v-if="allZero" class="mb-4 rounded-input border border-gold bg-gold/10 px-3 py-2 text-sm text-gold-ink">
        Heads up — this will submit 0 points for every category. Continue only if that’s intended.
    </p>

    <div v-if="form.errors.attendance" class="mb-4 rounded-input border border-bronze/50 bg-bronze/5 px-3 py-2 text-sm text-bronze">
        {{ form.errors.attendance }}
    </div>

    <div v-if="entry.editable" class="flex gap-3">
        <Link
            :href="`/team/submit/${meeting.id}`"
            class="min-h-tap inline-flex items-center rounded-input border border-ink bg-white px-5 text-sm font-semibold text-ink transition hover:bg-paper-2"
        >
            Keep editing
        </Link>
        <button
            type="button"
            :disabled="form.processing"
            class="min-h-tap rounded-input bg-turf px-6 text-sm font-semibold text-white transition hover:brightness-95 disabled:opacity-60"
            @click="submit"
        >
            {{ form.processing ? 'Submitting…' : 'Submit to LT' }}
        </button>
    </div>
    <p v-else class="rounded-input border border-line bg-paper-2 px-3 py-2 text-sm text-slate">
        This entry is {{ entry.status }} and can no longer be edited here.
    </p>
</template>
