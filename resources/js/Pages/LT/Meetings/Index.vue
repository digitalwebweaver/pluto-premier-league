<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import AppButton from '@/Components/AppButton.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    season: { type: Object, default: null },
    activeTeams: { type: Number, default: 0 },
    meetings: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({ meeting_date: '', sequence_no: '' });

function create() {
    form.post(route('lt.meetings.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
function toggle(id) {
    router.patch(`/lt/meetings/${id}/toggle`, {}, { preserveScroll: true });
}
function fmt(d) {
    // d = 'YYYY-MM-DD' → '01 Jul 2026'
    const [y, m, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${day} ${months[Number(m) - 1]} ${y}`;
}
</script>

<template>
    <Head title="Meetings" />

    <header class="mb-6">
        <p v-if="season" class="font-mono text-[11px] uppercase tracking-[0.16em] text-slate">
            {{ season.name }}
        </p>
        <h1 class="mt-1 font-display text-2xl font-bold text-ink">Meetings</h1>
        <p class="mt-1 text-sm text-slate">Create meeting dates and open or close their submission windows.</p>
    </header>

    <EmptyState
        v-if="!season"
        icon="◆"
        title="No active season"
        message="An active season is needed before meetings can be created. (Season management arrives with the seeder for now.)"
    />

    <div v-else class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <!-- Meetings list -->
        <section class="order-2 lg:order-1">
            <ul class="space-y-2">
                <li
                    v-for="m in meetings"
                    :key="m.id"
                    class="flex items-center gap-4 rounded-card border border-line bg-white px-4 py-3"
                >
                    <div class="w-10 shrink-0 text-center">
                        <div class="font-mono text-xl font-semibold text-ink">
                            {{ String(m.sequence_no).padStart(2, '0') }}
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-[15px] font-semibold text-ink">Meeting {{ m.sequence_no }}</p>
                        <p class="font-mono text-xs text-slate">{{ fmt(m.meeting_date) }}</p>
                    </div>
                    <span class="hidden font-mono text-xs text-slate sm:inline">
                        {{ m.approved }} / {{ activeTeams }} approved
                    </span>
                    <Link
                        :href="`/lt/meetings/${m.id}/categories`"
                        class="hidden shrink-0 rounded-input border border-line px-3 py-2 text-[13px] font-semibold text-slate transition hover:bg-paper-2 sm:inline-block"
                    >
                        Categories
                    </Link>
                    <StatusPill :status="m.status" />
                    <button
                        v-if="m.status !== 'scheduled'"
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border px-3 text-[13px] font-semibold transition"
                        :class="m.status === 'open'
                            ? 'border-bronze/50 text-bronze hover:bg-bronze/5'
                            : 'border-turf/50 text-turf hover:bg-turf/5'"
                        @click="toggle(m.id)"
                    >
                        {{ m.status === 'open' ? 'Close' : 'Reopen' }}
                    </button>
                    <button
                        v-else
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border border-turf/50 px-3 text-[13px] font-semibold text-turf transition hover:bg-turf/5"
                        @click="toggle(m.id)"
                    >
                        Open
                    </button>
                </li>
                <li
                    v-if="!meetings.length"
                    class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate"
                >
                    No meetings yet — create the first one.
                </li>
            </ul>
        </section>

        <!-- Create form -->
        <section class="order-1 lg:order-2">
            <form class="space-y-4 rounded-lg border border-line bg-white p-5" @submit.prevent="create">
                <p class="font-display text-[15px] font-semibold text-ink">New meeting</p>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Meeting date</label>
                    <input
                        v-model="form.meeting_date"
                        type="date"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.meeting_date }"
                    />
                    <p v-if="form.errors.meeting_date" class="mt-1 text-xs font-medium text-bronze">
                        {{ form.errors.meeting_date }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">
                        Sequence # <span class="font-normal text-slate/70">(optional — auto next)</span>
                    </label>
                    <input
                        v-model="form.sequence_no"
                        type="number"
                        min="1"
                        placeholder="Auto"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.sequence_no }"
                    />
                    <p v-if="form.errors.sequence_no" class="mt-1 text-xs font-medium text-bronze">
                        {{ form.errors.sequence_no }}
                    </p>
                </div>

                <AppButton type="submit" variant="primary" :disabled="form.processing" block>
                    Create meeting
                </AppButton>
            </form>
        </section>
    </div>
</template>
