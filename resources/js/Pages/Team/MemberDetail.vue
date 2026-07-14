<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import StatusPill from '@/Components/StatusPill.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    member: { type: Object, required: true },
});

defineOptions({
    layout: (h, page) => h(AppLayout, { role: 'captain' }, () => page),
});
</script>

<template>
    <Head :title="member.name" />

    <header class="mb-6">
        <Link href="/team/roster" class="text-[13px] font-semibold text-slate hover:text-ink"
            >← My roster</Link
        >
    </header>

    <div class="mb-6 flex items-center gap-4 rounded-lg border border-line bg-white p-6">
        <TeamCrest :name="member.name" :color="member.avatar_color" size="lg" />
        <div class="flex-1">
            <p class="font-display text-xl font-bold text-ink">{{ member.name }}</p>
            <p class="text-sm text-slate">{{ member.business_category || 'No category' }}</p>
        </div>
        <StatusPill v-if="member.is_active" status="approved" label="Active" />
        <StatusPill v-else status="closed" label="Inactive" />
    </div>

    <!-- Season contribution history arrives once scoring exists (FR-MBR-006). -->
    <EmptyState
        icon="◆"
        title="Contribution history coming soon"
        message="Once meetings are scored, this member’s per-category, per-meeting contributions will appear here."
    />
</template>
