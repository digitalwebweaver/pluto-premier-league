<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import ScorecardEditor from '@/Components/ScorecardEditor.vue';

const props = defineProps({
    meeting: { type: Object, required: true },
    entry: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    lines: { type: Array, default: () => [] },
    attendance: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const editable = props.entry.editable;
const editor = ref(null);

const form = useForm({ lines: [], attendance: [] });
function buildPayload() {
    const payload = editor.value.collectPayload();
    form.lines = payload.lines;
    form.attendance = payload.attendance;
}
function save() {
    buildPayload();
    form.put(route('team.submit.save', props.meeting.id), { preserveScroll: true });
}
// Save the current state, then go to the read-back / submit screen.
function reviewAndSubmit() {
    buildPayload();
    form.put(route('team.submit.save', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => router.visit(route('team.submit.review', props.meeting.id)),
    });
}

function fmt(d) {
    const [y, mo, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${day} ${months[Number(mo) - 1]} ${y}`;
}
const entryPill = { draft: 'draft', submitted: 'submitted', approved: 'approved', sent_back: 'sentback' };
</script>

<template>
    <Head :title="`Meeting ${meeting.sequence_no} scorecard`" />

    <header class="mb-4">
        <Link href="/team/submit" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← Submit scores</Link
        >
    </header>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-ink px-5 py-4 text-paper">
        <div>
            <p class="font-mono text-[11px] uppercase tracking-wide text-silver">{{ fmt(meeting.meeting_date) }}</p>
            <p class="mt-1 font-display text-xl font-semibold">Meeting {{ meeting.sequence_no }}</p>
        </div>
        <div class="flex items-center gap-2">
            <StatusPill :status="meeting.window" />
            <StatusPill :status="entryPill[entry.status]" />
        </div>
    </div>

    <!-- Sent-back note from the LT (FR-APR-004). -->
    <div
        v-if="entry.sent_back_note"
        class="mb-4 rounded-lg border border-bronze/40 bg-bronze/5 px-4 py-3"
    >
        <p class="font-display text-[13px] font-semibold text-bronze">↵ Sent back by the Leadership Team</p>
        <p class="mt-1 text-sm text-ink">{{ entry.sent_back_note }}</p>
    </div>

    <p v-if="!editable" class="mb-4 rounded-input border border-line bg-paper-2 px-3 py-2 text-sm text-slate">
        This scorecard is read-only.
    </p>

    <div class="pb-44 nav:pb-28">
        <ScorecardEditor
            ref="editor"
            :categories="categories"
            :members="members"
            :lines="lines"
            :attendance="attendance"
            :editable="editable"
        />
    </div>

    <!-- Sticky running total + save -->
    <div
        class="fixed inset-x-0 bottom-[57px] border-t border-line bg-white px-4 py-3 nav:bottom-0 nav:left-[236px]"
        style="box-shadow: 0 -8px 24px rgba(18,33,61,0.10)"
    >
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
            <div>
                <span class="font-mono text-[11px] uppercase tracking-wide text-slate">Running total</span>
                <div class="font-mono text-3xl font-semibold text-ink" :class="editor?.pulse ? 'animate-total-pulse' : ''">
                    {{ editor?.runningTotal ?? 0 }}
                </div>
            </div>
            <div v-if="editable" class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="min-h-tap rounded-input border border-ink bg-white px-4 text-sm font-semibold text-ink transition hover:bg-paper-2 disabled:opacity-60"
                    @click="save"
                >
                    {{ form.processing ? 'Saving…' : 'Save draft' }}
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="min-h-tap rounded-input bg-gold px-5 text-sm font-semibold text-ink transition hover:brightness-95 disabled:opacity-60"
                    @click="reviewAndSubmit"
                >
                    Review & submit
                </button>
            </div>
        </div>
    </div>
</template>
