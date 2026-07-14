<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    announcements: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({ body: '' });
function send() {
    form.post(route('lt.announcements.store'), { onSuccess: () => form.reset() });
}
function ago(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' · ' +
        d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Announcements" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Announcements</h1>
        <p class="mt-1 text-sm text-slate">Broadcast a message to every active team.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <!-- Past announcements -->
        <section class="order-2 lg:order-1">
            <ul class="space-y-2">
                <li
                    v-for="a in announcements"
                    :key="a.id"
                    class="rounded-card border border-line bg-white px-5 py-4"
                >
                    <p class="text-sm text-ink">{{ a.body }}</p>
                    <p class="mt-2 font-mono text-[11px] text-slate">
                        {{ a.author || 'Leadership' }} · {{ ago(a.created_at) }}
                    </p>
                </li>
                <li v-if="!announcements.length" class="rounded-card border border-dashed border-line bg-white px-4 py-8 text-center text-sm text-slate">
                    No announcements yet.
                </li>
            </ul>
        </section>

        <!-- Compose -->
        <section class="order-1 lg:order-2">
            <form class="space-y-3 rounded-lg border border-line bg-white p-5" @submit.prevent="send">
                <p class="font-display text-[15px] font-semibold text-ink">New announcement</p>
                <textarea
                    v-model="form.body"
                    rows="4"
                    placeholder="e.g. Meeting 7 is now open — please submit by Friday."
                    class="w-full rounded-input border border-line bg-white px-3.5 py-2 text-sm text-ink outline-none focus:border-gold"
                    :class="{ 'border-bronze': form.errors.body }"
                ></textarea>
                <p v-if="form.errors.body" class="text-xs font-medium text-bronze">{{ form.errors.body }}</p>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="min-h-tap w-full rounded-input bg-ink text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                >
                    {{ form.processing ? 'Sending…' : 'Send to all teams' }}
                </button>
            </form>
        </section>
    </div>
</template>
