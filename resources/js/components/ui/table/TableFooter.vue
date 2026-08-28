<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <tfoot :class="cn('border-t bg-muted/30 font-medium', props.class)">
        <slot />
    </tfoot>
</template>
