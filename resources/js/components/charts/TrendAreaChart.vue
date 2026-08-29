<script setup lang="ts">
import { computed, ref } from 'vue';

interface Point {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        data: Point[];
        color?: string;
        valueFormatter?: (value: number) => string;
        height?: number;
    }>(),
    {
        color: '#059669',
        height: 180,
    },
);

const width = 560;
const padding = { top: 12, right: 12, bottom: 24, left: 12 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = computed(() => props.height - padding.top - padding.bottom);

const maxValue = computed(() => Math.max(1, ...props.data.map((d) => d.value)));

const points = computed(() =>
    props.data.map((d, i) => {
        const x = props.data.length > 1 ? (i / (props.data.length - 1)) * innerWidth + padding.left : padding.left + innerWidth / 2;
        const y = padding.top + innerHeight.value - (d.value / maxValue.value) * innerHeight.value;

        return { ...d, x, y };
    }),
);

const linePath = computed(() => points.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' '));

const areaPath = computed(() => {
    if (points.value.length === 0) return '';
    const first = points.value[0];
    const last = points.value[points.value.length - 1];
    const base = padding.top + innerHeight.value;

    return `${linePath.value} L ${last.x} ${base} L ${first.x} ${base} Z`;
});

const gradientId = `trend-gradient-${Math.random().toString(36).slice(2, 9)}`;

const hovered = ref<number | null>(null);
const format = (v: number) => (props.valueFormatter ? props.valueFormatter(v) : String(v));

const labelStep = computed(() => Math.max(1, Math.ceil(points.value.length / 7)));
</script>

<template>
    <div class="relative">
        <svg :viewBox="`0 0 ${width} ${height}`" class="w-full overflow-visible" preserveAspectRatio="none">
            <defs>
                <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="color" stop-opacity="0.25" />
                    <stop offset="100%" :stop-color="color" stop-opacity="0" />
                </linearGradient>
            </defs>

            <line
                v-for="frac in [0, 0.5, 1]"
                :key="frac"
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="padding.top + innerHeight * frac"
                :y2="padding.top + innerHeight * frac"
                stroke="currentColor"
                class="text-muted-foreground/15"
                stroke-width="1"
            />

            <path :d="areaPath" :fill="`url(#${gradientId})`" stroke="none" />
            <path :d="linePath" fill="none" :stroke="color" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

            <g v-for="(p, i) in points" :key="p.label">
                <line :x1="p.x" :x2="p.x" :y1="padding.top" :y2="padding.top + innerHeight" stroke="transparent" stroke-width="24" class="cursor-pointer" @mouseenter="hovered = i" @mouseleave="hovered = null" />
                <circle :cx="p.x" :cy="p.y" :r="hovered === i ? 4.5 : 3" :fill="color" stroke="white" stroke-width="1.5" class="pointer-events-none transition-all duration-150" />
                <text v-if="i % labelStep === 0" :x="p.x" :y="height - 4" text-anchor="middle" class="fill-muted-foreground text-[10px]">{{ p.label }}</text>
            </g>
        </svg>

        <div
            v-if="hovered !== null"
            class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-md border bg-popover px-2 py-1 text-xs shadow-md"
            :style="{ left: `${(points[hovered].x / width) * 100}%`, top: `${(points[hovered].y / height) * 100}%` }"
        >
            <div class="font-medium text-popover-foreground">{{ format(points[hovered].value) }}</div>
            <div class="text-muted-foreground">{{ points[hovered].label }}</div>
        </div>
    </div>
</template>
