<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    entries: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

// Guarded unlock (low-prominence correction path, BR-APR-003).
const unlocking = ref(null); // the entry being confirmed
const form = useForm({});
function confirmUnlock() {
    form.post(route('lt.recent.unlock', unlocking.value.id), {
        onSuccess: () => { unlocking.value = null; },
    });
}
function fmtAgo(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
}
</script>

<template>
    <Head title="Recently approved" />

    <header class="mb-6">
        <Link href="/lt/queue" class="text-[13px] font-semibold text-slate hover:text-ink">← Approval queue</Link>
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">Recently approved</h1>
        <p class="mt-1 text-sm text-slate">Locked entries counting on the table. Unlock only to correct a genuine error.</p>
    </header>

    <EmptyState
        v-if="!entries.length"
        icon="✓"
        title="Nothing approved yet"
        message="Approved scorecards will appear here for quick access."
    />

    <ul v-else class="space-y-2">
        <li
            v-for="e in entries"
            :key="e.id"
            class="flex items-center gap-4 rounded-card border border-line bg-white px-4 py-3"
        >
            <TeamCrest :name="e.team.name" :initials="e.team.short_code" :color="e.team.crest_color" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-[15px] font-semibold text-ink">{{ e.team.name }}</p>
                <p class="font-mono text-xs text-slate">
                    Meeting {{ e.meeting.sequence_no }} · approved {{ fmtAgo(e.approved_at) }}
                    <template v-if="e.approved_by"> · {{ e.approved_by }}</template>
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 font-mono text-lg font-semibold text-turf">
                <span aria-hidden="true">🔒</span>{{ e.computed_total }}
            </span>
            <button
                type="button"
                class="min-h-9 shrink-0 rounded-input border border-line px-3 text-[13px] font-semibold text-slate transition hover:bg-paper-2"
                @click="unlocking = e"
            >
                Unlock
            </button>
        </li>
    </ul>

    <!-- Unlock confirmation modal (the second place a shadow is allowed — design.md §2.3). -->
    <div v-if="unlocking" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 px-5">
        <div class="w-full max-w-md rounded-lg bg-paper p-6 shadow-modal">
            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-bronze/10 text-xl text-bronze">↵</div>
            <h3 class="font-display text-xl font-bold text-ink">Unlock this entry?</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate">
                <span class="font-semibold text-ink">{{ unlocking.team.name }} · Meeting {{ unlocking.meeting.sequence_no }}</span>
                will return to the approval queue and stop counting on the league table until it is approved again.
            </p>
            <div class="mt-5 flex gap-3">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="min-h-tap flex-1 rounded-input bg-bronze text-sm font-semibold text-white transition hover:brightness-95 disabled:opacity-60"
                    @click="confirmUnlock"
                >
                    {{ form.processing ? 'Unlocking…' : 'Unlock' }}
                </button>
                <button
                    type="button"
                    class="min-h-tap flex-1 rounded-input border border-ink bg-white text-sm font-semibold text-ink transition hover:bg-paper-2"
                    @click="unlocking = null"
                >
                    Keep locked
                </button>
            </div>
        </div>
    </div>
</template>
