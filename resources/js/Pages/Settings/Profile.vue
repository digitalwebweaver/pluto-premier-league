<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    role: { type: String, default: 'captain' },
    user: { type: Object, required: true },
    status: { type: String, default: null },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: page.props.role ?? 'captain' }, () => page),
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    notification_pref: props.user.notification_pref ?? 'email',
});

function submit() {
    form.patch(route('profile.update'), { preserveScroll: true });
}

// The sidebar's own Sign out button is hidden below the nav: breakpoint
// (<680px) — this is the only sign-out path reachable on a phone, since the
// top bar's identity badge links here.
function signOut() {
    router.post('/logout');
}
</script>

<template>
    <Head title="My account" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">My account</h1>
        <p class="mt-1 text-sm text-slate">Your name, contact email, and how we reach you.</p>
    </header>

    <div class="max-w-xl space-y-4">
        <div class="rounded-lg border border-line bg-white p-6">
            <p
                v-if="status"
                class="mb-4 rounded-input bg-turf/10 px-3 py-2 text-sm font-medium text-turf"
            >
                {{ status }}
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name" class="mb-1.5 block text-xs font-semibold text-slate"
                        >Name</label
                    >
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm font-medium text-bronze">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-slate"
                        >Email (used to sign in)</label
                    >
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm font-medium text-bronze">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="notification_pref"
                        class="mb-1.5 block text-xs font-semibold text-slate"
                        >Notifications</label
                    >
                    <select
                        id="notification_pref"
                        v-model="form.notification_pref"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                    >
                        <option value="email">Email me</option>
                        <option value="none">Don’t email me</option>
                    </select>
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-tap rounded-input bg-ink px-6 text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Security -->
        <div
            class="flex items-center justify-between rounded-lg border border-line bg-white px-6 py-4"
        >
            <div>
                <p class="font-display text-[15px] font-semibold text-ink">Password</p>
                <p class="text-sm text-slate">Change the password you use to sign in.</p>
            </div>
            <Link
                href="/settings/password"
                class="min-h-9 rounded-input border border-ink px-4 py-2 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
            >
                Change password
            </Link>
        </div>

        <!-- Sign out -->
        <div class="flex items-center justify-between rounded-lg border border-line bg-white px-6 py-4">
            <div>
                <p class="font-display text-[15px] font-semibold text-ink">Sign out</p>
                <p class="text-sm text-slate">End your session on this device.</p>
            </div>
            <button
                type="button"
                class="min-h-9 rounded-input border border-bronze/50 px-4 py-2 text-[13px] font-semibold text-bronze transition hover:bg-bronze/5"
                @click="signOut"
            >
                Sign out
            </button>
        </div>
    </div>
</template>
