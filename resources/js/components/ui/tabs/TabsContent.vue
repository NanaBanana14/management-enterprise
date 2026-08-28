<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsContent, type TabsContentProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsContentProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <TabsContent v-bind="props" :class="cn('pt-6 focus-visible:outline-none', props.class)">
        <slot />
    </TabsContent>
</template>
