<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    inputShapes: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const SHAPE_LABELS = {
    count_subtype: 'Count × subtype',
    roster_flat_penalty: 'Roster (flat/penalty)',
    binary_flat: 'Binary',
    amount_subtype: 'Amount',
    conditional_multiplier: 'Multiplier',
};

// Local, reorderable copy.
const list = ref([...props.categories]);
watch(() => props.categories, (v) => { list.value = [...v]; });

function persistOrder() {
    router.post(route('lt.categories.reorder'), { ids: list.value.map((c) => c.id) }, {
        preserveScroll: true,
        preserveState: true,
    });
}
function move(index, delta) {
    const to = index + delta;
    if (to < 0 || to >= list.value.length) return;
    const arr = list.value;
    [arr[index], arr[to]] = [arr[to], arr[index]];
    persistOrder();
}
function toggle(id) {
    router.patch(`/lt/categories/${id}/toggle`, {}, { preserveScroll: true });
}

const form = useForm({ name: '', code: '', input_shape: 'count_subtype' });
function add() {
    form.post(route('lt.categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Categories" />

    <header class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Categories</h1>
        <p class="mt-1 text-sm text-slate">
            Enable, disable, and order the scoring categories. Point values live under Scoring rules.
        </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <!-- Category list -->
        <section class="order-2 lg:order-1">
            <ul class="space-y-2">
                <li
                    v-for="(c, i) in list"
                    :key="c.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-white px-4 py-3"
                    :class="{ 'opacity-60': !c.is_active }"
                >
                    <div class="flex flex-col">
                        <button
                            type="button"
                            class="text-slate transition hover:text-ink disabled:opacity-30"
                            :disabled="i === 0"
                            aria-label="Move up"
                            @click="move(i, -1)"
                        >▲</button>
                        <button
                            type="button"
                            class="text-slate transition hover:text-ink disabled:opacity-30"
                            :disabled="i === list.length - 1"
                            aria-label="Move down"
                            @click="move(i, 1)"
                        >▼</button>
                    </div>
                    <span
                        class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold tracking-wide text-slate"
                        >{{ c.code }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-[15px] font-semibold text-ink">{{ c.name }}</p>
                        <p class="font-mono text-[11px] text-slate">
                            {{ SHAPE_LABELS[c.input_shape] ?? c.input_shape }}
                            <span v-if="c.rules_count"> · {{ c.rules_count }} rule(s)</span>
                        </p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            :checked="c.is_active"
                            @change="toggle(c.id)"
                        />
                        <span
                            class="relative h-6 w-11 rounded-full bg-line transition peer-checked:bg-turf after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"
                        ></span>
                    </label>
                </li>
            </ul>
        </section>

        <!-- Add category -->
        <section class="order-1 lg:order-2">
            <form class="space-y-4 rounded-lg border border-line bg-white p-5" @submit.prevent="add">
                <p class="font-display text-[15px] font-semibold text-ink">Add category</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-bronze">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Code</label>
                    <input
                        v-model="form.code"
                        type="text"
                        maxlength="8"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm uppercase text-ink outline-none focus:border-gold"
                        :class="{ 'border-bronze': form.errors.code }"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-xs font-medium text-bronze">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate">Input shape</label>
                    <select
                        v-model="form.input_shape"
                        class="min-h-tap w-full rounded-input border border-line bg-white px-3.5 text-sm text-ink outline-none focus:border-gold"
                    >
                        <option v-for="s in inputShapes" :key="s" :value="s">{{ SHAPE_LABELS[s] ?? s }}</option>
                    </select>
                </div>
                <AppButton type="submit" variant="primary" :disabled="form.processing" block>Add category</AppButton>
            </form>
        </section>
    </div>
</template>
