<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import StatusPill from '@/Components/StatusPill.vue';
import ScorecardEditor from '@/Components/ScorecardEditor.vue';

const props = defineProps({
    entry: { type: Object, required: true },
    team: { type: Object, required: true },
    meeting: { type: Object, required: true },
    categories: { type: Array, default: () => [] }, // editable shape (rules etc.) for ScorecardEditor
    breakdownCategories: { type: Array, default: () => [] }, // read-only computed totals
    members: { type: Array, default: () => [] },
    lines: { type: Array, default: () => [] }, // display shape for the read-only breakdown
    editableLines: { type: Array, default: () => [] },
    editableAttendance: { type: Array, default: () => [] },
    attendance: { type: Object, default: () => ({}) },
    history: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const linesByCat = computed(() => {
    const map = {};
    props.lines.forEach((l) => {
        (map[l.category_id] ||= []).push(l);
    });
    return map;
});

function fmt(d) {
    const [y, mo, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${day} ${months[Number(mo) - 1]} ${y}`;
}
function fmtDateTime(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' · ' +
        d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}

const isSubmitted = computed(() => props.entry.status === 'submitted');

const approveForm = useForm({ version: props.entry.version });
function approve() {
    approveForm.post(route('lt.queue.approve', props.entry.id));
}

const showSendBack = ref(false);
const sendBackForm = useForm({ note: '' });
function sendBack() {
    sendBackForm.post(route('lt.queue.sendback', props.entry.id));
}

// --- Correct the entry directly (owner request) — requires a reason, audited + team-notified. ---
const showEdit = ref(false);
const editor = ref(null);
const editForm = useForm({ reason: '', lines: [], attendance: [] });
function startEdit() {
    showSendBack.value = false;
    showEdit.value = true;
}
function cancelEdit() {
    showEdit.value = false;
    editForm.reset();
    editForm.clearErrors();
}
function saveEdit() {
    const payload = editor.value.collectPayload();
    editForm.lines = payload.lines;
    editForm.attendance = payload.attendance;
    editForm.put(route('lt.queue.update', props.entry.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Review — ${team.name}`" />

    <header class="mb-4">
        <Link href="/lt/queue" class="text-[13px] font-semibold text-slate hover:text-ink">← Approval queue</Link>
    </header>

    <!-- Team + meeting header -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line bg-white px-5 py-4">
        <div class="flex items-center gap-3">
            <TeamCrest :name="team.name" :initials="team.short_code" :color="team.crest_color" />
            <div>
                <p class="font-display text-lg font-bold text-ink">{{ team.name }}</p>
                <p class="font-mono text-xs text-slate">Meeting {{ meeting.sequence_no }} · {{ fmt(meeting.meeting_date) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button
                v-if="isSubmitted && !showEdit"
                type="button"
                class="min-h-9 rounded-input border border-line bg-white px-3.5 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
                @click="startEdit"
            >
                ✎ Edit entry
            </button>
            <StatusPill :status="{ submitted:'submitted', approved:'approved', sent_back:'sentback', draft:'draft' }[entry.status]" />
        </div>
    </div>

    <!-- Correction / send-back history — visible so the team's past corrections aren't invisible. -->
    <div v-if="history.length" class="mb-4 rounded-lg border border-line bg-white px-5 py-4">
        <p class="mb-2 font-mono text-[11px] uppercase tracking-wide text-slate">Correction history</p>
        <ul class="space-y-2">
            <li v-for="(h, i) in history" :key="i" class="text-[13px]">
                <span class="font-semibold" :class="h.to_status === 'sent_back' ? 'text-bronze' : 'text-gold-ink'">
                    {{ h.to_status === 'sent_back' ? '↵ Sent back' : '✎ Corrected by LT' }}
                </span>
                <span class="text-slate"> · {{ fmtDateTime(h.created_at) }}</span>
                <p class="mt-0.5 text-ink">{{ h.note }}</p>
            </li>
        </ul>
    </div>

    <!-- Editable correction form -->
    <template v-if="showEdit">
        <div class="mb-4 rounded-lg border border-gold/50 bg-gold/5 px-5 py-4">
            <label class="mb-1.5 block text-xs font-semibold text-slate">
                Reason for this correction <span class="text-bronze">(required — the team will see this)</span>
            </label>
            <textarea
                v-model="editForm.reason"
                rows="2"
                class="w-full rounded-input border border-line bg-white px-3.5 py-2 text-sm text-ink outline-none focus:border-gold"
                :class="{ 'border-bronze': editForm.errors.reason }"
                placeholder="e.g. Visitor was logged as Hot but is actually Open — corrected the subtype."
            ></textarea>
            <p v-if="editForm.errors.reason" class="mt-1 text-xs font-medium text-bronze">{{ editForm.errors.reason }}</p>
        </div>

        <div class="mb-24 nav:mb-4">
            <ScorecardEditor
                ref="editor"
                :categories="categories"
                :members="members"
                :lines="editableLines"
                :attendance="editableAttendance"
                :editable="true"
            />
        </div>

        <div class="fixed inset-x-0 bottom-[57px] border-t border-line bg-white px-4 py-3 nav:static nav:mb-4 nav:border-0 nav:bg-transparent nav:px-0 nav:py-0">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
                <div>
                    <span class="font-mono text-[11px] uppercase tracking-wide text-slate">New total</span>
                    <div class="font-mono text-2xl font-semibold text-ink">{{ editor?.runningTotal ?? entry.computed_total }}</div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="text-[13px] font-semibold text-slate" @click="cancelEdit">Cancel</button>
                    <button
                        type="button"
                        :disabled="editForm.processing || !editForm.reason"
                        class="min-h-tap rounded-input bg-gold px-5 text-sm font-semibold text-ink transition hover:brightness-95 disabled:opacity-60"
                        @click="saveEdit"
                    >
                        {{ editForm.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Read-only breakdown -->
    <template v-else>
        <div class="mb-4 overflow-hidden rounded-lg border border-line bg-white">
            <div v-for="c in breakdownCategories" :key="c.id" class="border-b border-paper-2 last:border-b-0">
                <div class="flex items-center justify-between px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold text-slate">{{ c.code }}</span>
                        <span class="text-sm font-semibold text-ink">{{ c.name }}</span>
                    </div>
                    <span class="font-mono text-sm font-semibold" :class="c.points ? 'text-ink' : 'text-slate'">{{ c.points }}</span>
                </div>
                <!-- line detail -->
                <ul v-if="linesByCat[c.id]" class="border-t border-[#F1EDE3] bg-[#FCFBF8] px-5 py-2">
                    <li v-for="(l, i) in linesByCat[c.id]" :key="i" class="flex justify-between py-1 text-[13px] text-slate">
                        <span>
                            <template v-if="l.member">{{ l.member }} · </template>
                            <template v-if="l.visitor_name">{{ l.visitor_name }} · </template>
                            <template v-if="l.subtype">{{ l.subtype }}</template>
                            <template v-if="l.whole_team"> · whole team</template>
                            · ×{{ l.count }}
                            <template v-if="l.amount"> · ₹{{ l.amount }}</template>
                        </span>
                        <span class="font-mono font-semibold text-gold-ink">{{ l.points }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Attendance summary -->
        <div v-if="attendance.total" class="mb-4 rounded-lg border border-line bg-white px-5 py-3 text-sm text-slate">
            Attendance: <span class="font-semibold text-ink">{{ attendance.present }}</span> present ·
            <span class="font-semibold text-ink">{{ attendance.absent }}</span> absent ·
            <span class="font-semibold text-ink">{{ attendance.late }}</span> late
            <span class="text-slate/70">(of {{ attendance.total }})</span>
        </div>

        <!-- Grand total -->
        <div class="mb-4 flex items-center justify-between rounded-lg bg-ink px-5 py-4 text-paper">
            <span class="font-mono text-[11px] uppercase tracking-wide text-silver">Server-computed total</span>
            <span class="font-mono text-3xl font-semibold">{{ entry.computed_total }}</span>
        </div>

        <!-- Decision -->
        <div v-if="isSubmitted">
            <div v-if="!showSendBack" class="flex flex-wrap gap-3">
                <button
                    type="button"
                    :disabled="approveForm.processing"
                    class="min-h-tap rounded-input bg-turf px-6 text-sm font-semibold text-white transition hover:brightness-95 disabled:opacity-60"
                    @click="approve"
                >
                    {{ approveForm.processing ? 'Approving…' : '✓ Approve' }}
                </button>
                <button
                    type="button"
                    class="min-h-tap rounded-input border border-bronze/50 bg-white px-6 text-sm font-semibold text-bronze transition hover:bg-bronze/5"
                    @click="showSendBack = true"
                >
                    ↵ Send back
                </button>
            </div>

            <!-- Send-back composer -->
            <div v-else class="rounded-lg border border-line bg-white p-5">
                <label class="mb-1.5 block text-xs font-semibold text-slate">
                    What needs fixing? <span class="text-bronze">(required)</span>
                </label>
                <textarea
                    v-model="sendBackForm.note"
                    rows="3"
                    class="w-full rounded-input border border-line bg-white px-3.5 py-2 text-sm text-ink outline-none focus:border-gold"
                    :class="{ 'border-bronze': sendBackForm.errors.note }"
                    placeholder="e.g. Visitor counts look off — please double-check Hot vs Open."
                ></textarea>
                <p v-if="sendBackForm.errors.note" class="mt-1 text-xs font-medium text-bronze">{{ sendBackForm.errors.note }}</p>
                <div class="mt-3 flex gap-3">
                    <button
                        type="button"
                        :disabled="sendBackForm.processing"
                        class="min-h-tap rounded-input bg-bronze px-6 text-sm font-semibold text-white transition hover:brightness-95 disabled:opacity-60"
                        @click="sendBack"
                    >
                        {{ sendBackForm.processing ? 'Sending…' : 'Send back to team' }}
                    </button>
                    <button type="button" class="text-[13px] font-semibold text-slate" @click="showSendBack = false">Cancel</button>
                </div>
            </div>
        </div>
        <p v-else class="rounded-input border border-line bg-paper-2 px-3 py-2 text-sm text-slate">
            This entry is {{ entry.status }} — no action needed here.
        </p>
    </template>
</template>
