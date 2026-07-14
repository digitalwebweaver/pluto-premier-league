<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';

const props = defineProps({
    meeting: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'lt' }, () => page),
});

const form = useForm({ category_ids: [...props.selectedIds] });

function isOn(id) {
    return form.category_ids.includes(id);
}
function toggle(id) {
    const i = form.category_ids.indexOf(id);
    if (i === -1) form.category_ids.push(id);
    else form.category_ids.splice(i, 1);
}
function selectAll() {
    form.category_ids = props.categories.map((c) => c.id);
}
function clearAll() {
    form.category_ids = [];
}
function submit() {
    form.put(route('lt.meetings.categories.update', props.meeting.id));
}
</script>

<template>
    <Head :title="`Meeting ${meeting.sequence_no} categories`" />

    <header class="mb-6">
        <Link href="/lt/meetings" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← Meetings</Link
        >
        <h1 class="mt-2 font-display text-2xl font-bold text-ink">
            Meeting {{ meeting.sequence_no }} — applicable categories
        </h1>
        <p class="mt-1 text-sm text-slate">
            Tick the categories that count for this meeting. Unticked ones won’t appear in the entry form.
        </p>
    </header>

    <div class="max-w-2xl">
        <div class="mb-3 flex gap-3">
            <button type="button" class="text-[13px] font-semibold text-bronze hover:text-ink" @click="selectAll">
                Select all
            </button>
            <button type="button" class="text-[13px] font-semibold text-bronze hover:text-ink" @click="clearAll">
                Clear all
            </button>
            <span class="ml-auto font-mono text-xs text-slate">
                {{ form.category_ids.length }} / {{ categories.length }} selected
            </span>
        </div>

        <form @submit.prevent="submit">
            <ul class="grid gap-2 sm:grid-cols-2">
                <li v-for="c in categories" :key="c.id">
                    <label
                        class="flex min-h-tap cursor-pointer items-center gap-3 rounded-card border bg-white px-4 py-3 transition"
                        :class="isOn(c.id) ? 'border-gold bg-gold/5' : 'border-line hover:bg-paper-2'"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 accent-gold"
                            :checked="isOn(c.id)"
                            @change="toggle(c.id)"
                        />
                        <span
                            class="rounded bg-paper-2 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate"
                            >{{ c.code }}</span
                        >
                        <span class="text-sm font-medium text-ink">{{ c.name }}</span>
                    </label>
                </li>
            </ul>

            <div class="mt-5 flex gap-3">
                <AppButton type="submit" variant="primary" :disabled="form.processing">Save categories</AppButton>
                <AppButton href="/lt/meetings" variant="ghost">Cancel</AppButton>
            </div>
        </form>
    </div>
</template>
