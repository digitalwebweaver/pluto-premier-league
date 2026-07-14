<script setup>
import { computed } from 'vue';

// Status = colour + icon + label, never colour alone (design.md §2.5 / WCAG).
const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (v) =>
            [
                'draft',
                'submitted',
                'approved',
                'sentback',
                'open',
                'closed',
                'scheduled',
                'upcoming',
            ].includes(v),
    },
    // Override the default label if a screen needs different copy.
    label: { type: String, default: null },
});

const MAP = {
    draft: { icon: '✎', label: 'Draft', classes: 'bg-white text-slate border-slate' },
    submitted: {
        icon: '↑',
        label: 'Submitted',
        classes: 'bg-gold/10 text-gold-ink border-gold',
    },
    approved: {
        icon: '✓',
        label: 'Approved',
        classes: 'bg-turf text-white border-turf',
    },
    sentback: {
        icon: '↵',
        label: 'Sent back',
        classes: 'bg-white text-bronze border-bronze/60',
    },
    open: { icon: '●', label: 'Open', classes: 'bg-turf/10 text-turf border-turf' },
    closed: {
        icon: '○',
        label: 'Closed',
        classes: 'bg-paper-2 text-slate border-line',
    },
    scheduled: {
        icon: '◴',
        label: 'Scheduled',
        classes: 'bg-gold/10 text-gold-ink border-gold',
    },
    upcoming: {
        icon: '◴',
        label: 'Upcoming',
        classes: 'bg-paper text-slate border-line',
    },
};

const cfg = computed(() => MAP[props.status] ?? MAP.draft);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border-[1.5px] px-2.5 py-1 text-[11px] font-semibold"
        :class="cfg.classes"
    >
        <span class="text-[12px] leading-none" aria-hidden="true">{{ cfg.icon }}</span>
        <span>{{ label ?? cfg.label }}</span>
    </span>
</template>
