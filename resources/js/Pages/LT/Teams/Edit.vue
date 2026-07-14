<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import AppButton from '@/Components/AppButton.vue';
import { CREST_PALETTE } from '@/palette';

const props = defineProps({
    team: { type: Object, required: true },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({
    name: props.team.name,
    short_code: props.team.short_code,
    crest_color: props.team.crest_color,
});

function submit() {
    form.put(route('lt.teams.update', props.team.id));
}
</script>

<template>
    <Head :title="`Edit ${team.name}`" />

    <header class="mb-6">
        <Link href="/lt/teams" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← All teams</Link
        >
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">Edit team</h1>
    </header>

    <form class="max-w-xl space-y-4 rounded-lg border border-line bg-white p-6" @submit.prevent="submit">
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
            <label class="mb-1 block text-xs font-semibold text-slate">Short code</label>
            <input
                v-model="form.short_code"
                type="text"
                maxlength="4"
                class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm uppercase text-ink outline-none focus:border-gold"
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
                <TeamCrest :name="form.name" :initials="form.short_code" :color="form.crest_color" size="sm" />
            </div>
            <p v-if="form.errors.crest_color" class="mt-1 text-xs font-medium text-bronze">
                {{ form.errors.crest_color }}
            </p>
        </div>

        <div class="flex gap-3 pt-1">
            <AppButton type="submit" variant="primary" :disabled="form.processing">Save changes</AppButton>
            <AppButton href="/lt/teams" variant="ghost">Cancel</AppButton>
        </div>
    </form>
</template>
