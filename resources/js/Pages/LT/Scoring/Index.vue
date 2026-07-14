<script setup>
import { ref } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';

defineProps({
    categories: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const SHAPE_LABELS = {
    count_subtype: 'Count × subtype',
    roster_flat_penalty: 'Roster (flat / penalty)',
    binary_flat: 'Binary (flat)',
    amount_subtype: 'Amount + count',
    conditional_multiplier: 'Per member (doubles if whole team)',
};

// Which category's "add subtype" form is open.
const adding = ref(null);
const addForm = useForm({ category_id: null, subtype_label: '', points: 0 });

function openAdd(catId) {
    adding.value = catId;
    addForm.reset();
    addForm.category_id = catId;
}
function submitAdd() {
    addForm.post(route('lt.scoring.store'), {
        preserveScroll: true,
        onSuccess: () => { adding.value = null; addForm.reset(); },
    });
}
function ruleSummary(rule) {
    if (rule.extra_params?.flat !== undefined) {
        return `flat ${rule.extra_params.flat} · ${rule.extra_params.penalty}/offender`;
    }
    if (rule.extra_params?.multiplier !== undefined) {
        return `${rule.points} pts/member ×${rule.extra_params.multiplier} whole-team`;
    }
    return `${rule.points} pts`;
}
</script>

<template>
    <Head title="Scoring rules" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Scoring rules</h1>
        <p class="mt-1 text-sm text-slate">
            Point values live here — change them any time and entry forms pick them up live.
        </p>
    </header>

    <div class="space-y-3">
        <section
            v-for="c in categories"
            :key="c.id"
            class="overflow-hidden rounded-lg border border-line bg-white"
            :class="{ 'opacity-60': !c.is_active }"
        >
            <div class="flex items-center gap-3 border-b border-paper-2 px-5 py-3">
                <span class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold text-slate">{{ c.code }}</span>
                <div class="flex-1">
                    <p class="font-display text-[15px] font-semibold text-ink">{{ c.name }}</p>
                    <p class="font-mono text-[11px] text-slate">{{ SHAPE_LABELS[c.input_shape] ?? c.input_shape }}</p>
                </div>
                <button
                    v-if="c.input_shape === 'count_subtype' || c.input_shape === 'amount_subtype'"
                    type="button"
                    class="text-[13px] font-semibold text-bronze hover:text-ink"
                    @click="openAdd(c.id)"
                >
                    + Subtype
                </button>
            </div>

            <ul>
                <li
                    v-for="rule in c.rules"
                    :key="rule.id"
                    class="flex items-center justify-between border-b border-[#F1EDE3] px-5 py-2.5 text-sm last:border-b-0"
                    :class="{ 'opacity-50': !rule.is_active }"
                >
                    <span class="text-ink">{{ rule.subtype_label }}</span>
                    <div class="flex items-center gap-4">
                        <span class="font-mono font-semibold text-gold-ink">{{ ruleSummary(rule) }}</span>
                        <Link :href="`/lt/scoring/${rule.id}/edit`" class="text-[13px] font-semibold text-bronze hover:text-ink">
                            Edit
                        </Link>
                    </div>
                </li>
                <li v-if="!c.rules.length" class="px-5 py-3 text-sm text-slate">No rules yet.</li>
            </ul>

            <!-- Inline add-subtype form -->
            <form
                v-if="adding === c.id"
                class="flex flex-wrap items-end gap-3 border-t border-paper-2 bg-paper px-5 py-3"
                @submit.prevent="submitAdd"
            >
                <div class="flex-1">
                    <label class="mb-1 block text-[11px] font-semibold text-slate">Subtype label</label>
                    <input
                        v-model="addForm.subtype_label"
                        type="text"
                        class="min-h-9 w-full rounded-input border border-line bg-white px-3 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': addForm.errors.subtype_label }"
                    />
                </div>
                <div class="w-28">
                    <label class="mb-1 block text-[11px] font-semibold text-slate">Points</label>
                    <input
                        v-model="addForm.points"
                        type="number"
                        class="min-h-9 w-full rounded-input border border-line bg-white px-3 text-sm text-ink outline-none focus:border-gold"
                    />
                </div>
                <AppButton type="submit" variant="primary" size="sm" :disabled="addForm.processing">Add</AppButton>
                <button type="button" class="text-[13px] font-semibold text-slate" @click="adding = null">Cancel</button>
            </form>
        </section>
    </div>
</template>
