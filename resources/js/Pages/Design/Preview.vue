<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import StatusPill from '@/Components/StatusPill.vue';
import TeamCrest from '@/Components/TeamCrest.vue';
import NumberStepper from '@/Components/NumberStepper.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AppButton from '@/Components/AppButton.vue';

const stepper = ref(3);

const swatches = [
    { name: 'Ink', cls: 'bg-ink', hex: '#12213D' },
    { name: 'Ink-2', cls: 'bg-ink-2', hex: '#1B2F52' },
    { name: 'Paper', cls: 'bg-paper border border-line', hex: '#F6F4EF', dark: true },
    { name: 'Paper-2', cls: 'bg-paper-2', hex: '#EDE9DF', dark: true },
    { name: 'Gold', cls: 'bg-gold', hex: '#D9A441', dark: true },
    { name: 'Gold-ink', cls: 'bg-gold-ink', hex: '#9A6F1E' },
    { name: 'Silver', cls: 'bg-silver', hex: '#B8C0CC', dark: true },
    { name: 'Bronze', cls: 'bg-bronze', hex: '#B5473A' },
    { name: 'Turf', cls: 'bg-turf', hex: '#3F8F6B' },
    { name: 'Slate', cls: 'bg-slate', hex: '#5A6684' },
    { name: 'Line', cls: 'bg-line', hex: '#DAD5C6', dark: true },
];

const workflowStates = ['draft', 'submitted', 'approved', 'sentback'];
const windowStates = ['open', 'closed', 'scheduled'];
</script>

<template>
    <Head title="Design preview" />

    <main class="min-h-screen bg-paper px-5 py-10 md:px-10">
        <div class="mx-auto max-w-4xl space-y-12">
            <header>
                <p class="font-mono text-xs uppercase tracking-[0.18em] text-slate">
                    Phase 0B · design system
                </p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">
                    Component preview
                </h1>
                <p class="mt-2 text-slate">
                    Tokens + core components from <code class="font-mono">design.md</code>.
                </p>
            </header>

            <!-- Colour tokens -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">Colour tokens</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    <div
                        v-for="s in swatches"
                        :key="s.name"
                        class="overflow-hidden rounded-card border border-line bg-white"
                    >
                        <div class="h-14" :class="s.cls"></div>
                        <div class="px-3 py-2">
                            <div class="text-[13px] font-semibold text-ink">{{ s.name }}</div>
                            <div class="font-mono text-[11px] text-slate">{{ s.hex }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Typography -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">Typography</h2>
                <div class="space-y-3 rounded-card border border-line bg-white p-6">
                    <p class="font-display text-4xl font-bold text-ink">
                        Space Grotesk · Display 44
                    </p>
                    <p class="font-body text-base text-ink">
                        Inter · body 16 — the quick brown fox scores a hat-trick.
                    </p>
                    <p class="font-mono text-base font-semibold text-ink">
                        IBM Plex Mono · 1,240 pts · 24 Jul 2026 · TYFCB
                    </p>
                </div>
            </section>

            <!-- StatusPill -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">
                    StatusPill — colour + icon + label
                </h2>
                <div class="rounded-card border border-line bg-white p-6">
                    <p class="mb-2 font-mono text-[11px] uppercase tracking-wide text-slate">
                        Workflow states
                    </p>
                    <div class="mb-6 flex flex-wrap gap-3">
                        <StatusPill v-for="s in workflowStates" :key="s" :status="s" />
                    </div>
                    <p class="mb-2 font-mono text-[11px] uppercase tracking-wide text-slate">
                        Meeting-window states
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <StatusPill v-for="s in windowStates" :key="s" :status="s" />
                    </div>
                </div>
            </section>

            <!-- TeamCrest -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">
                    TeamCrest — rank rings
                </h2>
                <div
                    class="flex flex-wrap items-end gap-6 rounded-card border border-line bg-white p-6"
                >
                    <div class="flex flex-col items-center gap-2">
                        <TeamCrest name="Alpha Wolves" color="#12213D" ring="gold" size="lg" />
                        <span class="font-mono text-[11px] text-slate">gold · lg</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <TeamCrest name="Beta Bears" color="#3F8F6B" ring="silver" />
                        <span class="font-mono text-[11px] text-slate">silver · md</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <TeamCrest name="Cobra Kings" color="#B5473A" ring="bronze" />
                        <span class="font-mono text-[11px] text-slate">bronze · md</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <TeamCrest name="Delta Force" color="#1B2F52" />
                        <span class="font-mono text-[11px] text-slate">no ring · md</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <TeamCrest name="Echo Elite" color="#9A6F1E" size="sm" />
                        <span class="font-mono text-[11px] text-slate">no ring · sm</span>
                    </div>
                </div>
            </section>

            <!-- Buttons -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">Buttons</h2>
                <div class="flex flex-wrap gap-3 rounded-card border border-line bg-white p-6">
                    <AppButton variant="primary">Save draft</AppButton>
                    <AppButton variant="gold">Submit to LT</AppButton>
                    <AppButton variant="turf">Approve</AppButton>
                    <AppButton variant="bronze">Send back</AppButton>
                    <AppButton variant="outline">Cancel</AppButton>
                    <AppButton variant="ghost" size="sm">Manage</AppButton>
                </div>
            </section>

            <!-- NumberStepper -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">NumberStepper</h2>
                <div
                    class="flex items-center gap-4 rounded-card border border-line bg-white p-6"
                >
                    <NumberStepper v-model="stepper" :min="0" :max="10" aria-label="Referrals" />
                    <span class="text-sm text-slate"
                        >value = <span class="font-mono font-semibold text-ink">{{ stepper }}</span></span
                    >
                </div>
            </section>

            <!-- EmptyState -->
            <section>
                <h2 class="mb-4 font-display text-lg font-semibold">EmptyState</h2>
                <EmptyState
                    icon="◎"
                    title="No submissions yet"
                    message="When a captain submits their scorecard, it lands here for review."
                >
                    <template #action>
                        <AppButton variant="gold">Go to queue</AppButton>
                    </template>
                </EmptyState>
            </section>
        </div>
    </main>
</template>
