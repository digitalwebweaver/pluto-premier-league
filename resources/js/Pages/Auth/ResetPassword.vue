<script setup>
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, required: true },
    guard: { type: String, default: 'team' },
});

const form = useForm({
    guard: props.guard,
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Set a new password" />

    <main
        class="flex min-h-screen items-center justify-center px-6 py-10"
        style="background-image: radial-gradient(circle at 20% 0%, var(--ink-2) 0%, var(--ink) 60%)"
    >
        <div class="w-full max-w-[400px]">
            <div class="mb-7 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gold font-display text-[26px] font-bold text-ink"
                >
                    P
                </div>
                <h1 class="font-display text-[26px] font-bold text-paper">Choose a new password</h1>
            </div>

            <div class="rounded-lg bg-paper p-7 shadow-modal">
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
                            class="min-h-tap w-full rounded-input border border-line bg-paper-2 px-3.5 text-sm text-slate outline-none"
                            readonly
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-sm font-medium text-bronze">
                            {{ form.errors.email }}
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
                            autofocus
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

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-tap w-full rounded-input bg-ink text-sm font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving…' : 'Reset password' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
