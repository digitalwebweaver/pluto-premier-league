<script setup>
import { ref, nextTick } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import CompanyFooter from '@/Components/CompanyFooter.vue';

defineProps({
    canResetPassword: { type: Boolean, default: true },
    status: { type: String, default: null },
});

const TABS = [
    { guard: 'team', label: 'Team Captain' },
    { guard: 'lt', label: 'Leadership' },
];

const form = useForm({
    guard: 'team',
    email: '',
    password: '',
});

const emailInput = ref(null);

function selectTab(guard) {
    if (form.guard === guard) return;
    form.guard = guard;
    form.clearErrors();
    form.reset('password');
    nextTick(() => emailInput.value?.focus());
}

function submit() {
    form.transform((data) => ({ ...data })).post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <main
        class="flex min-h-screen flex-col px-6 py-10"
        style="background-image: radial-gradient(circle at 20% 0%, var(--ink-2) 0%, var(--ink) 60%)"
    >
        <div class="flex flex-1 items-center justify-center">
        <div class="w-full max-w-[400px]">
            <!-- Brand -->
            <div class="mb-7 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gold font-display text-[26px] font-bold text-ink"
                >
                    P
                </div>
                <h1 class="font-display text-[26px] font-bold text-paper">
                    Pluto Premier League
                </h1>
                <p class="mt-1 font-mono text-[11px] uppercase tracking-[0.14em] text-silver">
                    Season 4 · 2026
                </p>
            </div>

            <!-- Panel -->
            <div class="rounded-lg bg-paper p-7 shadow-modal">
                <p
                    v-if="status"
                    class="mb-4 rounded-input bg-turf/10 px-3 py-2 text-sm font-medium text-turf"
                >
                    {{ status }}
                </p>

                <!-- Tabs -->
                <div class="mb-6 flex gap-1.5 border-b border-line" role="tablist">
                    <button
                        v-for="tab in TABS"
                        :key="tab.guard"
                        type="button"
                        role="tab"
                        :aria-selected="form.guard === tab.guard"
                        class="-mb-px flex-1 border-b-2 py-3 text-sm font-semibold transition"
                        :class="
                            form.guard === tab.guard
                                ? 'border-gold text-ink'
                                : 'border-transparent text-slate hover:text-ink'
                        "
                        @click="selectTab(tab.guard)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-xs font-semibold text-slate"
                            >Email</label
                        >
                        <input
                            id="email"
                            ref="emailInput"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            autofocus
                            class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                            :class="{ 'border-bronze': form.errors.email }"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-xs font-semibold text-slate"
                            >Password</label
                        >
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                            :class="{ 'border-bronze': form.errors.email }"
                        />
                    </div>

                    <!-- Non-enumerating error surfaces on the email field. -->
                    <p v-if="form.errors.email" class="text-sm font-medium text-bronze">
                        {{ form.errors.email }}
                    </p>

                    <div v-if="canResetPassword" class="text-right">
                        <Link
                            href="/forgot-password"
                            class="text-[13px] font-semibold text-bronze hover:text-ink"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-tap w-full rounded-input bg-ink text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>
            </div>
        </div>
        </div>
        <CompanyFooter dark />
    </main>
</template>
