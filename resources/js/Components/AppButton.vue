<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// Buttons name the exact action (design.md §9). One class + variant modifier.
const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) =>
            ['primary', 'gold', 'turf', 'bronze', 'outline', 'ghost'].includes(v),
    },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
    // Render as an Inertia <Link> when `href` is set, else a <button>.
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    block: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const VARIANTS = {
    primary: 'bg-ink text-paper border-transparent hover:bg-ink-2',
    gold: 'bg-gold text-ink border-transparent hover:brightness-95',
    turf: 'bg-turf text-white border-transparent hover:brightness-95',
    bronze: 'bg-white text-bronze border-bronze/50 hover:bg-bronze/5',
    outline: 'bg-white text-ink border-ink hover:bg-paper-2',
    ghost: 'bg-white text-ink border-line hover:bg-paper-2',
};

const base =
    'inline-flex items-center justify-center gap-2 rounded-input border font-semibold leading-none transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold disabled:cursor-not-allowed disabled:opacity-50';

const classes = computed(() => [
    base,
    VARIANTS[props.variant],
    props.size === 'sm' ? 'min-h-9 px-3.5 text-[13px]' : 'min-h-tap px-[18px] text-sm',
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
