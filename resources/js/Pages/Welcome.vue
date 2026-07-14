<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    appName: { type: String, default: 'Pluto Premier League' },
    phase: { type: String, default: '' },
});
</script>

<template>
    <Head title="Welcome" />

    <main class="min-h-screen bg-paper text-ink flex items-center justify-center px-6">
        <div class="max-w-lg w-full text-center">
            <p class="font-mono text-xs uppercase tracking-[0.2em] text-slate">
                LVB Pluto
            </p>
            <h1 class="mt-3 font-display text-4xl font-bold text-ink sm:text-5xl">
                {{ appName }}
            </h1>
            <p class="mt-4 text-slate leading-relaxed">
                Multi-team meeting scoring platform. Scaffold is live —
                Laravel 11 · Inertia · Vue 3 · Tailwind · MariaDB.
            </p>
            <div
                class="mt-8 inline-flex items-center gap-2 rounded-full border border-line bg-paper-2 px-4 py-2"
            >
                <span class="h-2 w-2 rounded-full bg-turf"></span>
                <span class="font-mono text-sm text-ink">{{ phase }}</span>
            </div>
        </div>
    </main>
</template>
