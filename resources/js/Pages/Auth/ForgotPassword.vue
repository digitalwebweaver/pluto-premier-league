<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import CompanyFooter from '@/Components/CompanyFooter.vue';

defineProps({
    status: { type: String, default: null },
});

const TABS = [
    { guard: 'team', label: 'Team Captain' },
    { guard: 'lt', label: 'Leadership' },
];

const form = useForm({ guard: 'team', email: '' });

function submit() {
    form.post(route('password.email'), { onSuccess: () => form.reset('email') });
}
</script>

<template>
    <Head title="Forgot password" />

    <main
        class="flex min-h-screen flex-col px-6 py-10"
        style="background-image: radial-gradient(circle at 20% 0%, var(--ink-2) 0%, var(--ink) 60%)"
    >
        <div class="flex flex-1 items-center justify-center">
        <div class="w-full max-w-[400px]">
            <div class="mb-7 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gold font-display text-[26px] font-bold text-ink"
                >
                    P
                </div>
                <h1 class="font-display text-[26px] font-bold text-paper">Reset your password</h1>
                <p class="mt-2 text-sm text-silver">
                    Enter your email and we’ll send a reset link.
                </p>
            </div>

            <div class="rounded-lg bg-paper p-7 shadow-modal">
                <p
                    v-if="status"
                    class="mb-4 rounded-input bg-turf/10 px-3 py-2 text-sm font-medium text-turf"
                >
                    {{ status }}
                </p>

                <div class="mb-6 flex gap-1.5 border-b border-line" role="tablist">
                    <button
                        v-for="tab in TABS"
                        :key="tab.guard"
                        type="button"
                        role="tab"
                        class="-mb-px flex-1 border-b-2 py-3 text-sm font-semibold transition"
                        :class="
                            form.guard === tab.guard
                                ? 'border-gold text-ink'
                                : 'border-transparent text-slate hover:text-ink'
                        "
                        @click="form.guard = tab.guard"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-semibold text-slate"
                            >Email</label
                        >
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            autofocus
                            class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                            :class="{ 'border-bronze': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-sm font-medium text-bronze">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-tap w-full rounded-input bg-ink text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Sending…' : 'Email reset link' }}
                    </button>

                    <div class="text-center">
                        <Link href="/login" class="text-[13px] font-semibold text-bronze hover:text-ink"
                            >Back to sign in</Link
                        >
                    </div>
                </form>
            </div>
        </div>
        </div>
        <CompanyFooter dark />
    </main>
</template>
