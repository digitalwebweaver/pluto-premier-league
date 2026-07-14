<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import AppButton from '@/Components/AppButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { CREST_PALETTE } from '@/palette';

const props = defineProps({
    team: { type: Object, default: null },
    status: { type: String, default: null },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});

const form = useForm({ crest_color: props.team?.crest_color ?? CREST_PALETTE[0] });

function submit() {
    form.put(route('team.profile.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="My team" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">My team</h1>
        <p class="mt-1 text-sm text-slate">
            You can update your crest colour. Name and status are managed by the Leadership Team.
        </p>
    </header>

    <EmptyState
        v-if="!team"
        icon="⚑"
        title="No team linked yet"
        message="Your account isn’t linked to a team. Ask the Leadership Team to assign you."
    />

    <div v-else class="max-w-xl space-y-4">
        <div class="flex items-center gap-4 rounded-lg border border-line bg-white p-6">
            <TeamCrest :name="team.name" :initials="team.short_code" :color="form.crest_color" size="lg" />
            <div>
                <p class="font-display text-xl font-bold text-ink">{{ team.name }}</p>
                <p class="font-mono text-xs text-slate">{{ team.short_code }}</p>
            </div>
        </div>

        <form class="space-y-4 rounded-lg border border-line bg-white p-6" @submit.prevent="submit">
            <p
                v-if="status"
                class="rounded-input bg-turf/10 px-3 py-2 text-sm font-medium text-turf"
            >
                {{ status }}
            </p>

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
                </div>
                <p v-if="form.errors.crest_color" class="mt-1 text-xs font-medium text-bronze">
                    {{ form.errors.crest_color }}
                </p>
            </div>

            <AppButton type="submit" variant="primary" :disabled="form.processing">Save crest</AppButton>
        </form>
    </div>
</template>
