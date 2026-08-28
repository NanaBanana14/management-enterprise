<script setup lang="ts">
import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import type { HTMLAttributes } from 'vue';

const alertVariants = cva('flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', {
    variants: {
        variant: {
            default: 'border-border bg-muted/40 text-foreground',
            success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400',
            destructive: 'border-destructive/30 bg-destructive/5 text-destructive',
        },
    },
    defaultVariants: { variant: 'default' },
});

const props = defineProps<{
    variant?: VariantProps<typeof alertVariants>['variant'];
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn(alertVariants({ variant: props.variant }), props.class)" role="alert">
        <slot />
    </div>
</template>
