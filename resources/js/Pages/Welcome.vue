<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';
import CompanyFooter from '@/Components/CompanyFooter.vue';

defineProps({
    appName: { type: String, default: 'Pluto Premier League' },
    teams: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-paper">
        <!-- Public header (no auth chrome) — matches Public/League.vue -->
        <header class="flex h-[60px] items-center justify-between bg-ink px-5 text-paper">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold font-display text-base font-bold text-ink">P</span>
                <span class="font-display text-base font-semibold">Pluto <span class="font-medium text-silver">PL</span></span>
            </div>
            <nav class="flex items-center gap-4 font-mono text-[12px] font-semibold">
                <Link href="/public/league" class="text-silver hover:text-paper">Table</Link>
                <Link href="/login" class="text-gold hover:text-paper">Sign in</Link>
            </nav>
        </header>

        <!-- Hero -->
        <main class="mx-auto max-w-5xl px-5 py-14 text-center sm:py-20">
            <p class="font-mono text-xs uppercase tracking-[0.2em] text-slate">LVB Pluto</p>
            <h1 class="mt-3 font-display text-4xl font-bold text-ink sm:text-5xl">
                {{ appName }}
            </h1>
            <p class="mx-auto mt-4 max-w-lg text-slate leading-relaxed">
                Teams compete over a season of fortnightly meetings — captains log scores,
                the Leadership Team reviews and approves, and the league table tells the
                story.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <AppButton href="/login" variant="gold">Sign in</AppButton>
                <AppButton href="/public/league" variant="outline">View league table</AppButton>
            </div>
        </main>

        <!-- This season's teams -->
        <section v-if="teams.length" class="mx-auto max-w-5xl px-5 pb-16 sm:pb-24">
            <div class="mb-6 text-center">
                <p class="font-mono text-xs uppercase tracking-[0.2em] text-slate">This season</p>
                <h2 class="mt-1 font-display text-2xl font-bold text-ink">Competing teams</h2>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5">
                <div
                    v-for="team in teams"
                    :key="team.short_code"
                    class="flex flex-col overflow-hidden rounded-card border border-line bg-white transition hover:border-gold/60"
                >
                    <div class="flex aspect-[4/3] items-center justify-center bg-paper-2 p-4">
                        <img
                            v-if="team.logo_path"
                            :src="team.logo_path"
                            :alt="`${team.name} logo`"
                            class="max-h-full max-w-full object-contain"
                            loading="lazy"
                        />
                        <span
                            v-else
                            class="flex h-14 w-14 items-center justify-center rounded-full font-display text-lg font-semibold text-paper"
                            :style="{ backgroundColor: team.crest_color }"
                        >
                            {{ team.short_code }}
                        </span>
                    </div>
                    <div class="border-t border-paper-2 px-3 py-3 text-center">
                        <p class="font-display text-[15px] font-semibold leading-tight text-ink">{{ team.name }}</p>
                    </div>
                </div>
            </div>
        </section>

        <p class="px-5 pb-4 text-center font-mono text-[11px] text-slate">Pluto Premier League · LVB Pluto</p>
        <CompanyFooter />
    </div>
</template>
