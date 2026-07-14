<script setup>
import { computed } from 'vue';

// Initials on the team colour; gold/silver/bronze ring for ranks 1–3 (design.md §3).
// When `logoPath` is set (team context only — never pass it for member avatars),
// renders the real logo in a rounded tile instead of the initials circle.
const props = defineProps({
    // Full team name — initials are derived, or pass `initials` explicitly.
    name: { type: String, default: '' },
    initials: { type: String, default: null },
    // Team brand colour (hex). Defaults to ink.
    color: { type: String, default: '#12213D' },
    logoPath: { type: String, default: null },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v) },
    // Rank ring: 'gold' | 'silver' | 'bronze' | null (neutral ink otherwise).
    ring: { type: String, default: null },
});

const derivedInitials = computed(() => {
    if (props.initials) return props.initials.slice(0, 2).toUpperCase();
    const words = props.name.trim().split(/\s+/).filter(Boolean);
    if (words.length === 0) return '?';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return (words[0][0] + words[1][0]).toUpperCase();
});

const sizeClasses = computed(
    () =>
        ({
            sm: 'h-[34px] w-[34px] text-[12px]',
            md: 'h-11 w-11 text-[15px]',
            lg: 'h-16 w-16 text-[22px]',
            xl: 'h-20 w-20 text-[26px]',
        })[props.size]
);

const RINGS = {
    gold: '#D9A441',
    silver: '#B8C0CC',
    bronze: '#B5473A',
};

// Double ring via box-shadow (paper gap + coloured ring), matching the mockup.
// Follows whatever border-radius the element has, so it works for both the
// circular initials badge and the rounded logo tile.
const ringColor = computed(() => RINGS[props.ring] ?? '#12213D');
const ringWidth = computed(() => (props.size === 'sm' ? 3.5 : 4));
const ringShadow = computed(() => `0 0 0 2px var(--paper), 0 0 0 ${ringWidth.value}px ${ringColor.value}`);

const crestStyle = computed(() => ({
    backgroundColor: props.color,
    boxShadow: ringShadow.value,
}));

const logoTileStyle = computed(() => ({
    boxShadow: ringShadow.value,
}));
</script>

<template>
    <span
        v-if="logoPath"
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-input bg-white p-1"
        :class="sizeClasses"
        :style="logoTileStyle"
    >
        <img :src="logoPath" :alt="`${name} logo`" class="h-full w-full object-contain" loading="lazy" />
    </span>
    <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-full font-display font-semibold text-paper"
        :class="sizeClasses"
        :style="crestStyle"
    >
        {{ derivedInitials }}
    </span>
</template>
