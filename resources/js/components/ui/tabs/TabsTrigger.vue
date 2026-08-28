<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsTrigger, type TabsTriggerProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <TabsTrigger
        v-bind="props"
        :class="
            cn(
                '-mb-px inline-flex items-center border-b-2 border-transparent px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground data-[state=active]:border-primary data-[state=active]:text-foreground',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
