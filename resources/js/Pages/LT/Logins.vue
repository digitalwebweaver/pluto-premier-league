<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

defineProps({
    captains: { type: Array, default: () => [] },
    leadership: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const page = usePage();
const issued = computed(() => page.props.flash?.issued ?? null);

const captainForm = useForm({ name: '', email: '' });
const ltForm = useForm({ name: '', email: '' });

function issueCaptain() {
    captainForm.post(route('lt.logins.captains.store'), {
        preserveScroll: true,
        onSuccess: () => captainForm.reset(),
    });
}
function issueLt() {
    ltForm.post(route('lt.logins.lt.store'), {
        preserveScroll: true,
        onSuccess: () => ltForm.reset(),
    });
}
function resetCaptain(id) {
    router.post(`/lt/logins/captains/${id}/reset`, {}, { preserveScroll: true });
}
function resetLt(id) {
    router.post(`/lt/logins/lt/${id}/reset`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Logins" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Logins</h1>
        <p class="mt-1 text-sm text-slate">
            Issue and reset sign-in credentials for team captains and leadership.
        </p>
    </header>

    <!-- One-time credential to relay -->
    <div
        v-if="issued"
        class="mb-6 rounded-lg border-2 border-gold bg-gold/10 p-5"
    >
        <p class="font-display text-[15px] font-semibold text-ink">
            {{ issued.action === 'reset' ? 'Password reset' : 'Login issued' }} —
            {{ issued.name }}
        </p>
        <p class="mt-1 text-sm text-slate">
            Share these with {{ issued.kind === 'lt' ? 'the leadership member' : 'the captain' }}.
            They’ll be asked to set their own password on first sign-in. This won’t be shown again.
        </p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <div class="rounded-input border border-line bg-white px-3 py-2">
                <div class="font-mono text-[10px] uppercase tracking-wide text-slate">Email</div>
                <div class="font-mono text-sm font-semibold text-ink">{{ issued.email }}</div>
            </div>
            <div class="rounded-input border border-line bg-white px-3 py-2">
                <div class="font-mono text-[10px] uppercase tracking-wide text-slate">
                    Temporary password
                </div>
                <div class="font-mono text-sm font-semibold text-ink">{{ issued.password }}</div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Team captains -->
        <section>
            <h2 class="mb-3 font-display text-lg font-semibold">Team captains</h2>

            <form
                class="mb-4 space-y-3 rounded-lg border border-line bg-white p-5"
                @submit.prevent="issueCaptain"
            >
                <p class="font-display text-[15px] font-semibold text-ink">Issue captain login</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Name</label>
                    <input
                        v-model="captainForm.name"
                        type="text"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': captainForm.errors.name }"
                    />
                    <p v-if="captainForm.errors.name" class="mt-1 text-xs font-medium text-bronze">
                        {{ captainForm.errors.name }}
                    </p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Email</label>
                    <input
                        v-model="captainForm.email"
                        type="email"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': captainForm.errors.email }"
                    />
                    <p v-if="captainForm.errors.email" class="mt-1 text-xs font-medium text-bronze">
                        {{ captainForm.errors.email }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="captainForm.processing"
                    class="min-h-9 rounded-input bg-ink px-4 text-[13px] font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                >
                    Issue login
                </button>
            </form>

            <ul class="space-y-2">
                <li
                    v-for="u in captains"
                    :key="u.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-[15px] font-semibold text-ink">
                            {{ u.name }}
                        </p>
                        <p class="truncate font-mono text-xs text-slate">{{ u.email }}</p>
                    </div>
                    <StatusPill v-if="u.pending_setup" status="submitted" label="Pending setup" />
                    <StatusPill v-else status="approved" label="Active" />
                    <button
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border border-ink px-3 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
                        @click="resetCaptain(u.id)"
                    >
                        Reset
                    </button>
                </li>
                <li v-if="!captains.length" class="rounded-card border border-dashed border-line bg-white px-4 py-6 text-center text-sm text-slate">
                    No captain logins yet.
                </li>
            </ul>
        </section>

        <!-- Leadership -->
        <section>
            <h2 class="mb-3 font-display text-lg font-semibold">Leadership team</h2>

            <form
                class="mb-4 space-y-3 rounded-lg border border-line bg-white p-5"
                @submit.prevent="issueLt"
            >
                <p class="font-display text-[15px] font-semibold text-ink">Issue LT login</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Name</label>
                    <input
                        v-model="ltForm.name"
                        type="text"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': ltForm.errors.name }"
                    />
                    <p v-if="ltForm.errors.name" class="mt-1 text-xs font-medium text-bronze">
                        {{ ltForm.errors.name }}
                    </p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Email</label>
                    <input
                        v-model="ltForm.email"
                        type="email"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': ltForm.errors.email }"
                    />
                    <p v-if="ltForm.errors.email" class="mt-1 text-xs font-medium text-bronze">
                        {{ ltForm.errors.email }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="ltForm.processing"
                    class="min-h-9 rounded-input bg-ink px-4 text-[13px] font-semibold text-paper transition hover:bg-ink-2 disabled:opacity-60"
                >
                    Issue login
                </button>
            </form>

            <ul class="space-y-2">
                <li
                    v-for="u in leadership"
                    :key="u.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-[15px] font-semibold text-ink">
                            {{ u.name }}
                        </p>
                        <p class="truncate font-mono text-xs text-slate">{{ u.email }}</p>
                    </div>
                    <StatusPill v-if="u.pending_setup" status="submitted" label="Pending setup" />
                    <StatusPill v-else status="approved" label="Active" />
                    <button
                        type="button"
                        class="min-h-9 shrink-0 rounded-input border border-ink px-3 text-[13px] font-semibold text-ink transition hover:bg-paper-2"
                        @click="resetLt(u.id)"
                    >
                        Reset
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
