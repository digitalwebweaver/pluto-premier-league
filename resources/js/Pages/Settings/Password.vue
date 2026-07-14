<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    role: { type: String, default: 'captain' },
    status: { type: String, default: null },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: page.props.role ?? 'captain' }, () => page),
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Change password" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Change password</h1>
        <p class="mt-1 text-sm text-slate">
            Use a strong password you don’t reuse elsewhere.
        </p>
    </header>

    <div class="max-w-xl rounded-lg border border-line bg-white p-6">
        <p
            v-if="status"
            class="mb-4 rounded-input bg-turf/10 px-3 py-2 text-sm font-medium text-turf"
        >
            {{ status }}
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="current_password" class="mb-1.5 block text-xs font-semibold text-slate"
                    >Current password</label
                >
                <input
                    id="current_password"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                    :class="{ 'border-bronze': form.errors.current_password }"
                />
                <p v-if="form.errors.current_password" class="mt-1.5 text-sm font-medium text-bronze">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-xs font-semibold text-slate"
                    >New password</label
                >
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                    :class="{ 'border-bronze': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-medium text-bronze">
                    {{ form.errors.password }}
                </p>
                <p v-else class="mt-1.5 text-xs text-slate">
                    At least 8 characters, with a letter and a number.
                </p>
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="mb-1.5 block text-xs font-semibold text-slate"
                    >Confirm new password</label
                >
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                />
            </div>

            <div class="pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="min-h-tap rounded-input bg-ink px-6 text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                >
                    {{ form.processing ? 'Saving…' : 'Update password' }}
                </button>
            </div>
        </form>
    </div>
</template>
