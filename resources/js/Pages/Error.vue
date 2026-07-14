<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CompanyFooter from '@/Components/CompanyFooter.vue';

// On-brand error screen (design.md §11 error states). Rendered by the
// exception handler for 403/404/419/429/500/503.
const props = defineProps({
    status: { type: Number, required: true },
});

const MAP = {
    403: {
        title: 'Not allowed',
        message: 'You don’t have access to this page. If that seems wrong, check with your Leadership Team.',
        accent: 'text-bronze',
    },
    404: {
        title: 'Page not found',
        message: 'The page you’re after doesn’t exist or has moved. Let’s get you back on the table.',
        accent: 'text-gold-ink',
    },
    419: {
        title: 'Session expired',
        message: 'Your session timed out for safety. Please sign in again to continue.',
        accent: 'text-gold-ink',
    },
    429: {
        title: 'Too many requests',
        message: 'You’re moving a little fast. Give it a moment, then try again.',
        accent: 'text-gold-ink',
    },
    500: {
        title: 'Something broke',
        message: 'An unexpected error occurred on our side. It’s been logged — please try again shortly.',
        accent: 'text-bronze',
    },
    503: {
        title: 'Down for maintenance',
        message: 'We’re making things better and will be back shortly. Thanks for your patience.',
        accent: 'text-slate',
    },
};

const info = computed(() => MAP[props.status] ?? MAP[500]);
</script>

<template>
    <Head :title="`${status} · ${info.title}`" />

    <main class="flex min-h-screen flex-col bg-paper px-6">
        <div class="flex flex-1 items-center justify-center">
        <div class="w-full max-w-md text-center">
            <p
                class="font-display text-[80px] font-bold leading-none"
                :class="info.accent"
            >
                {{ status }}
            </p>
            <h1 class="mt-2 font-display text-2xl font-bold text-ink">{{ info.title }}</h1>
            <p class="mt-3 leading-relaxed text-slate">{{ info.message }}</p>
            <Link
                href="/"
                class="mt-8 inline-flex min-h-tap items-center justify-center rounded-input bg-ink px-6 text-sm font-semibold text-paper transition hover:bg-ink-2"
            >
                Back to home
            </Link>
        </div>
        </div>
        <CompanyFooter />
    </main>
</template>
