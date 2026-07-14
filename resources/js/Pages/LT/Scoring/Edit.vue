<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';

const props = defineProps({
    rule: { type: Object, required: true },
    category: { type: Object, required: true },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const isRoster = props.category.input_shape === 'roster_flat_penalty';
const isMultiplier = props.category.input_shape === 'conditional_multiplier';

const form = useForm({
    subtype_label: props.rule.subtype_label,
    points: props.rule.points,
    flat: props.rule.flat ?? 0,
    penalty: props.rule.penalty ?? 0,
    multiplier: props.rule.multiplier ?? 2,
});

function submit() {
    form.put(route('lt.scoring.update', props.rule.id));
}
</script>

<template>
    <Head :title="`Edit rule — ${category.name}`" />

    <header class="mb-6">
        <Link href="/lt/scoring" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← Scoring rules</Link
        >
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">Edit rule</h1>
        <p class="mt-1 text-sm text-slate">{{ category.name }}</p>
    </header>

    <form class="max-w-xl space-y-4 rounded-lg border border-line bg-white p-6" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate">Subtype label</label>
            <input
                v-model="form.subtype_label"
                type="text"
                class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                :class="{ 'border-bronze': form.errors.subtype_label }"
            />
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-slate">
                {{ isRoster ? 'Points (unused for roster — see flat/penalty)' : 'Points' }}
            </label>
            <input
                v-model="form.points"
                type="number"
                class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
            />
        </div>

        <!-- Roster (flat / penalty) params -->
        <div v-if="isRoster" class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate">Flat (no offenders)</label>
                <input
                    v-model="form.flat"
                    type="number"
                    class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate">Penalty / offender</label>
                <input
                    v-model="form.penalty"
                    type="number"
                    class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                />
            </div>
        </div>

        <!-- Multiplier param -->
        <div v-if="isMultiplier">
            <label class="mb-1 block text-xs font-semibold text-slate">
                Whole-team multiplier (per-member points × this)
            </label>
            <input
                v-model="form.multiplier"
                type="number"
                step="0.5"
                min="1"
                class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
            />
        </div>

        <div class="flex gap-3 pt-1">
            <AppButton type="submit" variant="primary" :disabled="form.processing">Save rule</AppButton>
            <AppButton href="/lt/scoring" variant="ghost">Cancel</AppButton>
        </div>
    </form>
</template>
