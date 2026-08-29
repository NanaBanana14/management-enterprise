<script setup lang="ts">
import { computed, ref } from 'vue';

interface Group {
    label: string;
    values: number[];
}

const props = withDefaults(
    defineProps<{
        data: Group[];
        series: { label: string; color: string }[];
        valueFormatter?: (value: number) => string;
        height?: number;
    }>(),
    {
        height: 200,
    },
);

const width = 560;
const padding = { top: 12, right: 8, bottom: 24, left: 8 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = computed(() => props.height - padding.top - padding.bottom);

const maxValue = computed(() => Math.max(1, ...props.data.flatMap((g) => g.values)));

const groupWidth = computed(() => innerWidth / Math.max(1, props.data.length));
const barGap = 3;
const barWidth = computed(() => (groupWidth.value - barGap * (props.series.length + 1)) / props.series.length);

const format = (v: number) => (props.valueFormatter ? props.valueFormatter(v) : String(v));

const hovered = ref<{ group: number; series: number } | null>(null);
</script>

<template>
    <div class="relative">
        <svg :viewBox="`0 0 ${width} ${height}`" class="w-full overflow-visible" preserveAspectRatio="none">
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

            <g v-for="(group, gi) in data" :key="group.label">
                <rect
                    v-for="(value, si) in group.values"
                    :key="si"
                    :x="padding.left + gi * groupWidth + barGap + si * (barWidth + barGap)"
                    :y="padding.top + innerHeight - (value / maxValue) * innerHeight"
                    :width="Math.max(0, barWidth)"
                    :height="Math.max(0, (value / maxValue) * innerHeight)"
                    :fill="series[si]?.color"
                    :rx="2"
                    class="cursor-pointer transition-opacity duration-150"
                    :opacity="hovered && (hovered.group !== gi || hovered.series !== si) ? 0.55 : 1"
                    @mouseenter="hovered = { group: gi, series: si }"
                    @mouseleave="hovered = null"
                />
                <text :x="padding.left + gi * groupWidth + groupWidth / 2" :y="height - 4" text-anchor="middle" class="fill-muted-foreground text-[10px]">
                    {{ group.label }}
                </text>
            </g>
        </svg>

        <div
            v-if="hovered"
            class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-md border bg-popover px-2 py-1 text-xs shadow-md"
            :style="{
                left: `${((padding.left + hovered.group * groupWidth + barGap + hovered.series * (barWidth + barGap) + barWidth / 2) / width) * 100}%`,
                top: `${((padding.top + innerHeight - (data[hovered.group].values[hovered.series] / maxValue) * innerHeight) / height) * 100}%`,
            }"
        >
            <div class="font-medium text-popover-foreground">{{ format(data[hovered.group].values[hovered.series]) }}</div>
            <div class="text-muted-foreground">{{ series[hovered.series]?.label }} · {{ data[hovered.group].label }}</div>
        </div>

        <div class="mt-2 flex items-center justify-center gap-4">
            <div v-for="s in series" :key="s.label" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span class="size-2.5 rounded-full" :style="{ backgroundColor: s.color }" />
                {{ s.label }}
            </div>
        </div>
    </div>
</template>
