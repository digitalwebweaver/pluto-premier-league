<script setup>
// Never a dead end — always a next step (design.md §3 / §9).
defineProps({
    icon: { type: String, default: '◎' },
    title: { type: String, required: true },
    message: { type: String, default: '' },
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed border-line bg-white px-6 py-12 text-center"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-paper-2 text-xl text-slate"
            aria-hidden="true"
        >
            {{ icon }}
        </div>
        <h3 class="font-display text-lg font-semibold text-ink">{{ title }}</h3>
        <p v-if="message" class="mt-1.5 max-w-sm text-sm leading-relaxed text-slate">
            {{ message }}
        </p>
        <!-- Action slot: buttons/links that point the way forward. -->
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
