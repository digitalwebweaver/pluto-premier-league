<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import AppButton from '@/Components/AppButton.vue';
import { CREST_PALETTE } from '@/palette';

const props = defineProps({
    team: { type: Object, required: true },
    member: { type: Object, required: true },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({
    name: props.member.name,
    business_category: props.member.business_category ?? '',
    avatar_color: props.member.avatar_color,
});

function submit() {
    form.put(route('lt.teams.roster.update', [props.team.id, props.member.id]));
}
</script>

<template>
    <Head :title="`Edit ${member.name}`" />

    <header class="mb-6">
        <Link :href="route('lt.teams.roster', team.id)" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← {{ team.name }} roster</Link
        >
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">Edit member</h1>
    </header>

    <form class="max-w-xl space-y-4 rounded-lg border border-line bg-white p-6" @submit.prevent="submit">
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
                <TeamCrest :name="form.name" :color="form.avatar_color" size="sm" />
            </div>
        </div>

        <div class="flex gap-3 pt-1">
            <AppButton type="submit" variant="primary" :disabled="form.processing">Save changes</AppButton>
            <AppButton :href="route('lt.teams.roster', team.id)" variant="ghost">Cancel</AppButton>
        </div>
    </form>
</template>
