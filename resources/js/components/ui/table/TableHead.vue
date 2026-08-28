<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <th :class="cn('h-10 px-4 text-left align-middle text-xs font-medium uppercase tracking-wide text-muted-foreground', props.class)">
        <slot />
    </th>
</template>
