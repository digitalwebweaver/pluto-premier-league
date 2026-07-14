<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { NAV, ACTIVE_FOR, HOME } from '@/nav';

// Persistent layout so nav + scroll survive Inertia visits (design.md §7).
const props = defineProps({
    // 'captain' | 'lt' — supplied per page until auth wiring (Phase 1) provides it.
    role: { type: String, default: 'captain' },
    // Identity strip in the top bar (placeholder copy until auth lands).
    identityName: { type: String, default: null },
    identityRole: { type: String, default: null },
});

const page = usePage();

const items = computed(() => NAV[props.role] ?? NAV.captain);
const bottomItems = computed(() => items.value.filter((i) => i.bottom));

// Badge value: static `badge`, or a live shared prop via `badgeKey`. Hidden at 0.
function badgeFor(item) {
    const val = item.badgeKey ? page.props[item.badgeKey] : item.badge;
    return val ? val : null;
}
const unreadCount = computed(() => page.props.unreadNotifications ?? 0);

// Resolve the active nav key from the current URL (sub-pages map to a parent).
const currentPath = computed(() => page.url.split('?')[0]);
const activeHref = computed(
    () => ACTIVE_FOR[currentPath.value] ?? currentPath.value
);
const isActive = (item) =>
    item.href === activeHref.value ||
    (item.href !== '/' && activeHref.value.startsWith(item.href + '/'));

// Real signed-in identity (shared by HandleInertiaRequests), with graceful
// fallbacks for placeholder/preview contexts.
const authUser = computed(() => page.props.auth?.user ?? null);
const identity = computed(() => ({
    name: authUser.value?.name ?? props.identityName ?? (props.role === 'lt' ? 'Leadership' : 'Team'),
    role: props.identityRole ?? (props.role === 'lt' ? 'Leadership' : 'Captain'),
}));
const initials = computed(() => {
    const words = identity.value.name.trim().split(/\s+/).filter(Boolean);
    if (!words.length) return '?';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
});

function signOut() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-paper">
        <!-- Top bar -->
        <header
            class="flex h-[60px] shrink-0 items-center justify-between gap-3 bg-ink px-4 text-paper"
        >
            <Link :href="HOME[role]" class="flex items-center gap-2.5">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gold font-display text-base font-bold text-ink"
                    >P</span
                >
                <span class="font-display text-base font-semibold"
                    >Pluto <span class="font-medium text-silver">PL</span></span
                >
            </Link>

            <div class="flex items-center gap-1">
            <!-- Notification bell (captain) -->
            <Link
                v-if="role === 'captain'"
                href="/team/notifications"
                class="relative flex h-9 w-9 items-center justify-center rounded-full text-paper transition hover:bg-white/5"
                title="Notifications"
                aria-label="Notifications"
            >
                <span class="text-lg leading-none" aria-hidden="true">🔔</span>
                <span
                    v-if="unreadCount"
                    class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-gold px-1 font-mono text-[9px] font-bold text-ink"
                    >{{ unreadCount > 9 ? '9+' : unreadCount }}</span
                >
            </Link>

            <Link
                href="/account"
                class="flex items-center gap-2.5 rounded-full py-1 pl-3 pr-1 transition hover:bg-white/5"
                title="My account"
            >
                <div class="hidden text-right leading-tight sm:block">
                    <div class="font-display text-[13px] font-semibold">
                        {{ identity.name }}
                    </div>
                    <div class="text-[11px] text-silver">{{ identity.role }}</div>
                </div>
                <span
                    v-if="role === 'lt'"
                    class="rounded-input bg-gold px-2.5 py-1 font-display text-[13px] font-bold tracking-wide text-ink"
                    >LT</span
                >
                <span
                    v-else
                    class="flex h-[34px] w-[34px] items-center justify-center rounded-full bg-[#5E7B6B] font-display text-[12px] font-semibold text-paper"
                    style="box-shadow: 0 0 0 2px var(--ink), 0 0 0 3.5px var(--slate)"
                    >{{ initials }}</span
                >
            </Link>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <!-- Sidebar (≥680px) -->
            <aside
                class="hidden w-[236px] shrink-0 flex-col gap-1 border-r border-line bg-paper px-3.5 py-5 nav:flex"
            >
                <p
                    class="px-3 pb-2.5 font-mono text-[11px] uppercase tracking-[0.14em] text-slate"
                >
                    {{ role === 'lt' ? 'Leadership' : 'Team Captain' }}
                </p>
                <Link
                    v-for="item in items"
                    :key="item.key"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition"
                    :class="
                        isActive(item)
                            ? 'bg-gold/15 text-ink'
                            : 'text-slate hover:bg-gold/10 hover:text-ink'
                    "
                >
                    <span class="w-5 text-center text-base" aria-hidden="true">{{
                        item.icon
                    }}</span>
                    <span class="flex-1">{{ item.label }}</span>
                    <span
                        v-if="badgeFor(item)"
                        class="rounded-full bg-gold px-1.5 py-px font-mono text-[11px] font-semibold text-ink"
                        >{{ badgeFor(item) }}</span
                    >
                </Link>
                <div class="flex-1"></div>
                <Link
                    href="/account"
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition"
                    :class="
                        currentPath.startsWith('/account') || currentPath.startsWith('/settings')
                            ? 'bg-gold/15 text-ink'
                            : 'text-slate hover:bg-gold/10 hover:text-ink'
                    "
                >
                    <span class="w-5 text-center text-base" aria-hidden="true">◍</span>
                    <span>Account</span>
                </Link>
                <button
                    type="button"
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold text-slate transition hover:bg-gold/10 hover:text-ink"
                    @click="signOut"
                >
                    <span class="w-5 text-center text-base" aria-hidden="true">⏻</span>
                    <span>Sign out</span>
                </button>
            </aside>

            <!-- Content -->
            <main class="min-w-0 flex-1 overflow-auto px-4 py-6 nav:px-9 nav:py-8">
                <div class="mx-auto max-w-5xl animate-fade-up">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Bottom tab bar (<680px) -->
        <nav
            class="sticky bottom-0 flex shrink-0 border-t border-line bg-white nav:hidden"
            style="box-shadow: 0 -6px 20px rgba(18, 33, 61, 0.06)"
        >
            <Link
                v-for="item in bottomItems"
                :key="item.key"
                :href="item.href"
                class="relative flex min-h-[56px] flex-1 flex-col items-center justify-center gap-0.5 py-2 transition"
                :class="isActive(item) ? 'text-ink' : 'text-slate'"
            >
                <span class="text-xl leading-none" aria-hidden="true">{{ item.icon }}</span>
                <span class="text-[10px] font-semibold">{{ item.label }}</span>
                <span
                    v-if="badgeFor(item)"
                    class="absolute left-1/2 top-1.5 ml-2 rounded-full bg-gold px-1.5 font-mono text-[9px] font-semibold text-ink"
                    >{{ badgeFor(item) }}</span
                >
            </Link>
        </nav>
    </div>
</template>
