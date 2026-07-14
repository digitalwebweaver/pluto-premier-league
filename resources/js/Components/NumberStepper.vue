<script setup>
import { computed } from 'vue';

// ± counter for small counts. 44px targets (design.md §3 / §8).
const props = defineProps({
    modelValue: { type: Number, default: 0 },
    min: { type: Number, default: 0 },
    max: { type: Number, default: 999 },
    step: { type: Number, default: 1 },
    ariaLabel: { type: String, default: 'Count' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const canDec = computed(() => !props.disabled && props.modelValue - props.step >= props.min);
const canInc = computed(() => !props.disabled && props.modelValue + props.step <= props.max);

function dec() {
    if (canDec.value) emit('update:modelValue', props.modelValue - props.step);
}
function inc() {
    if (canInc.value) emit('update:modelValue', props.modelValue + props.step);
}
</script>

<template>
    <div
        class="inline-flex h-10 items-center overflow-hidden rounded-input border border-line"
        role="group"
        :aria-label="ariaLabel"
    >
        <button
            type="button"
            class="h-full w-9 bg-paper-2 text-lg leading-none text-ink transition enabled:hover:bg-line disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!canDec"
            aria-label="Decrease"
            @click="dec"
        >
            −
        </button>
        <span
            class="min-w-[3ch] flex-1 text-center font-mono text-[15px] font-semibold tabular-nums"
            aria-live="polite"
        >
            {{ modelValue }}
        </span>
        <button
            type="button"
            class="h-full w-9 bg-paper-2 text-lg leading-none text-ink transition enabled:hover:bg-line disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!canInc"
            aria-label="Increase"
            @click="inc"
        >
            +
        </button>
    </div>
</template>
