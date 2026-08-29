<script setup lang="ts">
import { computed } from 'vue';

interface Bar {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        data: Bar[];
        color?: string;
        valueFormatter?: (value: number) => string;
    }>(),
    {
        color: '#059669',
    },
);

const maxValue = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
const format = (v: number) => (props.valueFormatter ? props.valueFormatter(v) : String(v));
</script>

<template>
    <div class="space-y-3">
        <div v-for="bar in data" :key="bar.label" class="space-y-1">
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-foreground">{{ bar.label }}</span>
                <span class="text-muted-foreground">{{ format(bar.value) }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :style="{ width: `${(bar.value / maxValue) * 100}%`, backgroundColor: color }"
                />
            </div>
        </div>
        <p v-if="data.length === 0" class="text-sm text-muted-foreground">No data yet.</p>
    </div>
</template>
