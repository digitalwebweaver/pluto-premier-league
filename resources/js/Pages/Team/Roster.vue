<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import StatusPill from '@/Components/StatusPill.vue';
import AppButton from '@/Components/AppButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { CREST_PALETTE } from '@/palette';

defineProps({
    hasTeam: { type: Boolean, default: true },
    members: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const form = useForm({ name: '', business_category: '', avatar_color: CREST_PALETTE[5] });

function add() {
    form.post(route('team.roster.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
function toggle(id) {
    router.patch(`/team/roster/${id}/toggle`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="My roster" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">My roster</h1>
        <p class="mt-1 text-sm text-slate">Your team’s members and business categories.</p>
    </header>

    <EmptyState
        v-if="!hasTeam"
        icon="⚑"
        title="No team linked yet"
        message="Your account isn’t linked to a team. Ask the Leadership Team to assign you before adding members."
    />

    <div v-else class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <!-- Roster list -->
        <section class="order-2 lg:order-1">
            <ul class="space-y-2">
                <li
                    v-for="m in members"
                    :key="m.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
                    :class="{ 'opacity-60': !m.is_active }"
                >
                    <TeamCrest :name="m.name" :color="m.avatar_color" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-[15px] font-semibold text-ink">{{ m.name }}</p>
                        <p class="truncate text-xs text-slate">
                            {{ m.business_category || 'No category' }}
                        </p>
                    </div>
                    <StatusPill v-if="m.is_active" status="approved" label="Active" />
                    <StatusPill v-else status="closed" label="Inactive" />
                    <Link
                        :href="`/team/roster/${m.id}/edit`"
                        class="min-h-9 shrink-0 rounded-input border border-ink px-3 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border border-line px-3 text-[13px] font-semibold text-slate transition hover:bg-paper-2"
                        @click="toggle(m.id)"
                    >
                        {{ m.is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                </li>
                <li
                    v-if="!members.length"
                    class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate"
                >
                    No members yet — add your first one.
                </li>
            </ul>
        </section>

        <!-- Add form -->
        <section class="order-1 lg:order-2">
            <form class="space-y-4 rounded-lg border border-line bg-white p-5" @submit.prevent="add">
                <p class="font-display text-[15px] font-semibold text-ink">Add member</p>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Full name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-bronze">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Business category</label>
                    <input
                        v-model="form.business_category"
                        type="text"
                        placeholder="e.g. Financial Advisor"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate">Avatar colour</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="c in CREST_PALETTE"
                            :key="c"
                            type="button"
                            class="h-8 w-8 rounded-full ring-offset-2 transition"
                            :class="form.avatar_color.toUpperCase() === c ? 'ring-2 ring-ink' : ''"
                            :style="{ backgroundColor: c }"
                            :aria-label="c"
                            @click="form.avatar_color = c"
                        ></button>
                        <TeamCrest :name="form.name || 'New Member'" :color="form.avatar_color" size="sm" />
                    </div>
                </div>

                <AppButton type="submit" variant="primary" :disabled="form.processing" block>
                    Add to roster
                </AppButton>
            </form>
        </section>
    </div>
</template>
