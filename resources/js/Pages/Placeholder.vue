<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

// Generic "not built yet" screen for wired-but-unbuilt nav destinations.
defineProps({
    role: { type: String, default: 'captain' },
    title: { type: String, required: true },
    phase: { type: String, default: 'a later phase' },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: page.props.role ?? 'captain' }, () => page),
});
</script>

<template>
    <Head :title="title" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">{{ title }}</h1>
    </header>

    <EmptyState
        icon="◷"
        :title="`${title} is on the way`"
        :message="`This screen is scaffolded and routed. Its real content ships in ${phase}.`"
    />
</template>
