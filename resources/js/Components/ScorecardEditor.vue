<script setup>
import { reactive, ref, computed, watch, nextTick } from 'vue';
import NumberStepper from '@/Components/NumberStepper.vue';
import RosterChecklist from '@/Components/RosterChecklist.vue';

// Shared accordion editor for all 5 scoring shapes — used by the captain's
// scorecard (Team/Scorecard.vue) and the LT's in-review correction UI
// (LT/Queue/Review.vue) so the entry logic and markup live in exactly one
// place. Purely a controlled editor: takes initial lines/attendance, exposes
// the live running total + a collectPayload() the parent submits.
const props = defineProps({
    categories: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    lines: { type: Array, default: () => [] },
    attendance: { type: Array, default: () => [] },
    editable: { type: Boolean, default: true },
});

const LINE_SHAPES = ['count_subtype', 'amount_subtype'];
const isLineShape = (s) => LINE_SHAPES.includes(s);

// Repeatable rows per category id, hydrated from persisted lines.
let keyCounter = 1;
const rowsByCat = reactive({});
props.categories.forEach((c) => {
    if (isLineShape(c.input_shape)) {
        rowsByCat[c.id] = props.lines
            .filter((l) => l.category_id === c.id)
            .map((l) => ({
                member_id: l.member_id,
                visitor_name: l.visitor_name ?? null,
                scoring_rule_id: l.scoring_rule_id,
                count: l.count,
                amount: l.amount,
                _key: keyCounter++,
            }));
    }
});

// Binary categories (Golden Mic / Abiding Theme): on/off, hydrated from lines.
const binaryOn = reactive({});
props.categories.forEach((c) => {
    if (c.input_shape === 'binary_flat') {
        binaryOn[c.id] = props.lines.some((l) => l.category_id === c.id && l.count > 0);
    }
});

// Conditional multiplier (Trainings): members present + whole-team flag.
const training = reactive({});
props.categories.forEach((c) => {
    if (c.input_shape === 'conditional_multiplier') {
        const line = props.lines.find((l) => l.category_id === c.id);
        training[c.id] = {
            members_present: line ? line.count : 0,
            whole_team: line ? !!line.whole_team : false,
        };
    }
});

// Shared attendance marks — one per active member (Attendance + Punctuality
// both read this). Hydrated from persisted attendance, default present/on-time.
const attendance = reactive(
    props.members.map((m) => {
        const existing = props.attendance.find((a) => a.member_id === m.id);
        return {
            member_id: m.id,
            is_present: existing ? !!existing.is_present : true,
            is_on_time: existing ? !!existing.is_on_time : true,
        };
    })
);

const openCat = ref(null);
function toggle(id) {
    openCat.value = openCat.value === id ? null : id;
}

function rulePoints(cat, ruleId) {
    return cat.rules.find((r) => r.id === ruleId)?.points ?? 0;
}
function rowPoints(cat, row) {
    return (Number(row.count) || 0) * rulePoints(cat, row.scoring_rule_id);
}
// Roster (Attendance/Punctuality) flat-vs-penalty — mirrors ScoringService.
function rosterParams(cat) {
    return cat.rules[0]?.extra_params ?? {};
}
function rosterSubtotal(cat) {
    const p = rosterParams(cat);
    const metric = p.metric ?? 'present';
    const offenders = attendance.filter((a) => (metric === 'on_time' ? !a.is_on_time : !a.is_present)).length;
    return offenders === 0 ? Number(p.flat ?? 0) : offenders * Number(p.penalty ?? 0);
}
function trainingSubtotal(cat) {
    const t = training[cat.id] ?? { members_present: 0, whole_team: false };
    const base = cat.rules[0]?.points ?? 0;
    const mult = Number(cat.rules[0]?.extra_params?.multiplier ?? 2);
    const perMember = t.whole_team ? Math.round(base * mult) : base;
    return (Number(t.members_present) || 0) * perMember;
}
function catSubtotal(cat) {
    if (isLineShape(cat.input_shape)) {
        return (rowsByCat[cat.id] || []).reduce((t, r) => t + rowPoints(cat, r), 0);
    }
    if (cat.input_shape === 'roster_flat_penalty') {
        return rosterSubtotal(cat);
    }
    if (cat.input_shape === 'binary_flat') {
        return binaryOn[cat.id] ? (cat.rules[0]?.points ?? 0) : 0;
    }
    if (cat.input_shape === 'conditional_multiplier') {
        return trainingSubtotal(cat);
    }
    return 0;
}
function metricOf(cat) {
    return rosterParams(cat).metric ?? 'present';
}
const runningTotal = computed(() =>
    props.categories.reduce((t, c) => t + catSubtotal(c), 0)
);

function addRow(cat) {
    rowsByCat[cat.id].push({
        member_id: null,
        visitor_name: null,
        scoring_rule_id: cat.rules[0]?.id ?? null,
        count: 1,
        amount: null,
        _key: keyCounter++,
    });
}
function removeRow(cat, i) {
    rowsByCat[cat.id].splice(i, 1);
}

// Sticky-total pulse on change (design.md §2.4).
const pulse = ref(false);
watch(runningTotal, () => {
    pulse.value = false;
    nextTick(() => { pulse.value = true; });
});

// Collect every line-based category (count/amount rows, binary toggle, training).
function collectPayload() {
    const lines = [];
    props.categories.forEach((c) => {
        if (isLineShape(c.input_shape)) {
            (rowsByCat[c.id] || []).forEach((r) => {
                if (Number(r.count) > 0 && r.scoring_rule_id) {
                    lines.push({
                        category_id: c.id,
                        scoring_rule_id: r.scoring_rule_id,
                        member_id: r.member_id || null,
                        visitor_name: r.visitor_name || null,
                        count: Number(r.count),
                        amount: r.amount ? Number(r.amount) : null,
                    });
                }
            });
        } else if (c.input_shape === 'binary_flat' && binaryOn[c.id] && c.rules[0]) {
            lines.push({ category_id: c.id, scoring_rule_id: c.rules[0].id, member_id: null, count: 1 });
        } else if (c.input_shape === 'conditional_multiplier' && c.rules[0]) {
            const t = training[c.id];
            if (Number(t.members_present) > 0) {
                lines.push({
                    category_id: c.id,
                    scoring_rule_id: c.rules[0].id,
                    member_id: null,
                    count: Number(t.members_present),
                    whole_team: !!t.whole_team,
                });
            }
        }
    });

    return {
        lines,
        attendance: attendance.map((a) => ({
            member_id: a.member_id,
            is_present: a.is_present,
            is_on_time: a.is_on_time,
        })),
    };
}

defineExpose({ runningTotal, pulse, collectPayload });
</script>

<template>
    <div class="space-y-2">
        <div v-for="cat in categories" :key="cat.id" class="overflow-hidden rounded-card border border-line bg-white">
            <button
                type="button"
                class="flex min-h-tap w-full items-center gap-3 px-4 py-3 text-left"
                @click="toggle(cat.id)"
            >
                <span class="w-3 text-slate transition" :class="openCat === cat.id ? 'rotate-90' : ''">›</span>
                <span class="rounded-md bg-paper-2 px-2 py-1 font-mono text-[11px] font-semibold text-slate">{{ cat.code }}</span>
                <span class="flex-1 font-semibold text-ink">{{ cat.name }}</span>
                <span class="font-mono text-[15px] font-semibold" :class="catSubtotal(cat) ? 'text-ink' : 'text-slate'">
                    {{ catSubtotal(cat) }}
                </span>
            </button>

            <div v-if="openCat === cat.id" class="border-t border-paper-2 bg-[#FCFBF8] px-4 py-3">
                <p v-if="cat.code === 'TJM'" class="mb-3 rounded-input bg-gold/12 px-3 py-2 text-[13px] text-bronze">
                    Qualifies only with a minimum of 3 members from each team present.
                </p>
                <!-- count / amount: repeatable rows -->
                <template v-if="isLineShape(cat.input_shape)">
                    <div
                        v-for="(row, i) in rowsByCat[cat.id]"
                        :key="row._key"
                        class="mb-2 grid grid-cols-1 gap-2 sm:items-center"
                        :class="cat.code === 'VIS' ? 'sm:grid-cols-[1.2fr_1.2fr_1.2fr_auto_auto]' : 'sm:grid-cols-[1.4fr_1.4fr_auto_auto]'"
                    >
                        <select
                            v-model="row.member_id"
                            :disabled="!editable"
                            class="min-h-10 rounded-input border border-line bg-white px-3 text-sm text-ink outline-none focus:border-gold"
                        >
                            <option :value="null">{{ cat.code === 'VIS' ? '— member who invited —' : '— member —' }}</option>
                            <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                        <input
                            v-if="cat.code === 'VIS'"
                            v-model="row.visitor_name"
                            type="text"
                            placeholder="Visitor name"
                            :disabled="!editable"
                            class="min-h-10 rounded-input border border-line bg-white px-3 text-sm text-ink outline-none focus:border-gold"
                        />
                        <select
                            v-model="row.scoring_rule_id"
                            :disabled="!editable"
                            class="min-h-10 rounded-input border border-line bg-white px-3 text-sm text-ink outline-none focus:border-gold"
                        >
                            <option v-for="r in cat.rules" :key="r.id" :value="r.id">
                                {{ r.subtype_label }} ({{ r.points }})
                            </option>
                        </select>
                        <NumberStepper v-model="row.count" :min="0" :disabled="!editable" aria-label="Count" />
                        <div class="flex items-center gap-2">
                            <input
                                v-if="cat.input_shape === 'amount_subtype'"
                                v-model="row.amount"
                                type="number"
                                min="0"
                                placeholder="₹ amount"
                                :disabled="!editable"
                                class="min-h-10 w-24 rounded-input border border-line bg-white px-2 text-sm text-ink outline-none focus:border-gold"
                            />
                            <span class="w-14 text-right font-mono text-sm font-semibold text-gold-ink">
                                {{ rowPoints(cat, row) }}
                            </span>
                            <button
                                v-if="editable"
                                type="button"
                                class="text-bronze hover:text-ink"
                                aria-label="Remove row"
                                @click="removeRow(cat, i)"
                            >✕</button>
                        </div>
                    </div>
                    <button
                        v-if="editable"
                        type="button"
                        class="mt-1 text-[13px] font-semibold text-bronze hover:text-ink"
                        @click="addRow(cat)"
                    >
                        + Add row
                    </button>
                    <p v-if="!rowsByCat[cat.id]?.length && !editable" class="text-sm text-slate">No entries.</p>
                </template>

                <!-- roster: attendance / punctuality checklist -->
                <template v-else-if="cat.input_shape === 'roster_flat_penalty'">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-mono text-[11px] uppercase tracking-wide text-slate">
                            {{ metricOf(cat) === 'on_time' ? 'On time / late' : 'Present / absent' }}
                        </p>
                        <p class="text-xs text-slate">
                            Flat {{ rosterParams(cat).flat }} · {{ rosterParams(cat).penalty }}/offender
                        </p>
                    </div>
                    <RosterChecklist
                        :members="members"
                        :attendance="attendance"
                        :metric="metricOf(cat)"
                        :disabled="!editable"
                    />
                </template>

                <!-- binary: single toggle -->
                <template v-else-if="cat.input_shape === 'binary_flat'">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate">
                            Awards <span class="font-mono font-semibold text-gold-ink">{{ cat.rules[0]?.points ?? 0 }}</span> when achieved.
                        </span>
                        <div class="flex overflow-hidden rounded-input border border-line">
                            <button
                                type="button" :disabled="!editable"
                                class="px-4 py-1.5 text-[13px] font-semibold transition"
                                :class="binaryOn[cat.id] ? 'bg-turf text-white' : 'bg-white text-slate'"
                                @click="binaryOn[cat.id] = true"
                            >Awarded</button>
                            <button
                                type="button" :disabled="!editable"
                                class="border-l border-line px-4 py-1.5 text-[13px] font-semibold transition"
                                :class="!binaryOn[cat.id] ? 'bg-paper-2 text-ink' : 'bg-white text-slate'"
                                @click="binaryOn[cat.id] = false"
                            >Not yet</button>
                        </div>
                    </div>
                </template>

                <!-- conditional multiplier (Trainings) -->
                <template v-else-if="cat.input_shape === 'conditional_multiplier'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-ink">Members who attended training</span>
                            <NumberStepper v-model="training[cat.id].members_present" :min="0" :disabled="!editable" aria-label="Members present" />
                        </div>
                        <label class="flex items-center justify-between gap-3">
                            <span class="text-sm text-ink">
                                Whole team present
                                <span class="text-slate">(points ×{{ cat.rules[0]?.extra_params?.multiplier ?? 2 }})</span>
                            </span>
                            <input type="checkbox" v-model="training[cat.id].whole_team" :disabled="!editable" class="h-5 w-5 accent-gold" />
                        </label>
                        <p class="text-right font-mono text-sm font-semibold text-gold-ink">
                            {{ cat.rules[0]?.points ?? 0 }} pts/member{{ training[cat.id].whole_team ? ` ×${cat.rules[0]?.extra_params?.multiplier ?? 2}` : '' }}
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
