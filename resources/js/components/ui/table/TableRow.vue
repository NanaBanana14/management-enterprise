<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <tr :class="cn('border-b transition-colors last:border-0 hover:bg-muted/40', props.class)">
        <slot />
    </tr>
</template>
