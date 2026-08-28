<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsList, type TabsListProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <TabsList
        v-bind="props"
        :class="cn('inline-flex h-10 items-center gap-1 border-b', props.class)"
    >
        <slot />
    </TabsList>
</template>
