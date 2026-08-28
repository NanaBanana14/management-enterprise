<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <thead :class="cn('bg-muted/50 [&_tr]:border-b', props.class)">
        <slot />
    </thead>
</template>
