<script setup>
// Company branding — appears at the end of every page's content (design.md
// elevation rule: flat, no shadow). Scrolls with the page; never fixed/pinned,
// so it can't collide with the mobile bottom-nav or a screen's own sticky bar
// (e.g. the scorecard's running total).
defineProps({
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <footer
        class="mt-10 border-t px-4 py-5 text-center font-mono text-[11px]"
        :class="dark ? 'border-white/10 text-silver' : 'border-line text-slate'"
    >
        <span :class="dark ? 'text-paper/70' : 'text-ink/70'">Digital Web Weaver</span>
        <span class="mx-1.5 opacity-50">·</span>
        Software Development Company
    </footer>
</template>
