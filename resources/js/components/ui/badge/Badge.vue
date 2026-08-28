<script setup lang="ts">
import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import type { HTMLAttributes } from 'vue';

const badgeVariants = cva('inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium transition-colors', {
    variants: {
        variant: {
            default: 'border-transparent bg-primary text-primary-foreground',
            secondary: 'border-transparent bg-secondary text-secondary-foreground',
            outline: 'border-border text-foreground',
            success: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-400',
            warning: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-400',
            destructive: 'border-transparent bg-destructive/10 text-destructive dark:bg-destructive/20',
        },
    },
    defaultVariants: {
        variant: 'default',
    },
});

type BadgeVariants = VariantProps<typeof badgeVariants>;

const props = defineProps<{
    variant?: BadgeVariants['variant'];
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <span :class="cn(badgeVariants({ variant: props.variant }), props.class)">
        <slot />
    </span>
</template>
