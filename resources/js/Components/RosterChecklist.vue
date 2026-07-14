<script setup>
// Tap-to-toggle roster marks (design.md §3). Drives Attendance (metric=present)
// or Punctuality (metric=on_time); mutates the shared `attendance` model.
const props = defineProps({
    members: { type: Array, default: () => [] },
    attendance: { type: Array, required: true }, // [{member_id, is_present, is_on_time}]
    metric: { type: String, default: 'present' }, // 'present' | 'on_time'
    disabled: { type: Boolean, default: false },
});

function mark(memberId) {
    return props.attendance.find((a) => a.member_id === memberId);
}
function isGood(memberId) {
    const m = mark(memberId);
    if (!m) return true;
    return props.metric === 'on_time' ? m.is_on_time : m.is_present;
}
function set(memberId, good) {
    if (props.disabled) return;
    const m = mark(memberId);
    if (!m) return;
    if (props.metric === 'on_time') m.is_on_time = good;
    else m.is_present = good;
}

const labels =
    props.metric === 'on_time' ? { good: 'On time', bad: 'Late' } : { good: 'Present', bad: 'Absent' };
</script>

<template>
    <ul class="space-y-1.5">
        <li
            v-for="mem in members"
            :key="mem.id"
            class="flex min-h-tap items-center gap-3 rounded-input border border-paper-2 bg-white px-3 py-2"
        >
            <span class="flex-1 truncate text-sm font-medium text-ink">{{ mem.name }}</span>
            <div class="flex overflow-hidden rounded-input border border-line">
                <button
                    type="button"
                    :disabled="disabled"
                    class="px-3 py-1.5 text-[13px] font-semibold transition"
                    :class="isGood(mem.id) ? 'bg-turf text-white' : 'bg-white text-slate'"
                    @click="set(mem.id, true)"
                >
                    {{ labels.good }}
                </button>
                <button
                    type="button"
                    :disabled="disabled"
                    class="border-l border-line px-3 py-1.5 text-[13px] font-semibold transition"
                    :class="!isGood(mem.id) ? 'bg-bronze text-white' : 'bg-white text-slate'"
                    @click="set(mem.id, false)"
                >
                    {{ labels.bad }}
                </button>
            </div>
        </li>
        <li v-if="!members.length" class="px-1 py-2 text-sm text-slate">No active members on your roster.</li>
    </ul>
</template>
