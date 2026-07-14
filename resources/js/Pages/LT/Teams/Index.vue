<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import StatusPill from '@/Components/StatusPill.vue';
import AppButton from '@/Components/AppButton.vue';
import { CREST_PALETTE } from '@/palette';

defineProps({
    teams: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({ name: '', short_code: '', crest_color: CREST_PALETTE[0] });

function create() {
    form.post(route('lt.teams.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
function toggle(id) {
    router.patch(`/lt/teams/${id}/toggle`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="All teams" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Teams</h1>
        <p class="mt-1 text-sm text-slate">Create teams and manage their identity and status.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <!-- Team list -->
        <section class="order-2 lg:order-1">
            <ul class="space-y-2">
                <li
                    v-for="t in teams"
                    :key="t.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
                    :class="{ 'opacity-60': !t.is_active }"
                >
                    <TeamCrest :name="t.name" :initials="t.short_code" :color="t.crest_color" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-[15px] font-semibold text-ink">
                            {{ t.name }}
                            <span class="ml-1 font-mono text-xs font-normal text-slate">{{ t.short_code }}</span>
                        </p>
                        <p class="truncate text-xs text-slate">
                            <template v-if="t.captain">
                                {{ t.captain.name }} · {{ t.captain.email }}
                            </template>
                            <template v-else>No captain login yet</template>
                        </p>
                    </div>
                    <StatusPill v-if="t.is_active" status="approved" label="Active" />
                    <StatusPill v-else status="closed" label="Inactive" />
                    <Link
                        :href="`/lt/teams/${t.id}/edit`"
                        class="min-h-9 shrink-0 rounded-input border border-ink px-3 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border border-line px-3 text-[13px] font-semibold text-slate transition hover:bg-paper-2"
                        @click="toggle(t.id)"
                    >
                        {{ t.is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                </li>
                <li
                    v-if="!teams.length"
                    class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate"
                >
                    No teams yet — create your first one.
                </li>
            </ul>
        </section>

        <!-- Create form -->
        <section class="order-1 lg:order-2">
            <form
                class="space-y-4 rounded-lg border border-line bg-white p-5"
                @submit.prevent="create"
            >
                <p class="font-display text-[15px] font-semibold text-ink">New team</p>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Team name</label>
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
                    <label class="mb-1 block text-xs font-semibold text-slate">
                        Short code
                        <span class="font-normal text-slate/70">(optional — auto from name)</span>
                    </label>
                    <input
                        v-model="form.short_code"
                        type="text"
                        maxlength="4"
                        placeholder="Auto"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm uppercase text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.short_code }"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate">Crest colour</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="c in CREST_PALETTE"
                            :key="c"
                            type="button"
                            class="h-8 w-8 rounded-full ring-offset-2 transition"
                            :class="form.crest_color.toUpperCase() === c ? 'ring-2 ring-ink' : ''"
                            :style="{ backgroundColor: c }"
                            :aria-label="c"
                            @click="form.crest_color = c"
                        ></button>
                        <TeamCrest
                            :name="form.name || 'New Team'"
                            :initials="form.short_code"
                            :color="form.crest_color"
                            size="sm"
                        />
                    </div>
                    <p v-if="form.errors.crest_color" class="mt-1 text-xs font-medium text-bronze">
                        {{ form.errors.crest_color }}
                    </p>
                </div>

                <AppButton type="submit" variant="primary" :disabled="form.processing" block>
                    Create team
                </AppButton>
            </form>
        </section>
    </div>
</template>
