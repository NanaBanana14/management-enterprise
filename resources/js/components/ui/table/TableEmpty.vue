<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    colspan?: number;
    icon?: Component;
    title?: string;
    description?: string;
}>();
</script>

<template>
    <tr>
        <td :colspan="colspan ?? 1" class="px-4 py-12 text-center">
            <div v-if="title" class="mx-auto flex max-w-sm flex-col items-center gap-3">
                <div v-if="icon" class="flex size-12 items-center justify-center rounded-full bg-muted">
                    <component :is="icon" class="size-6 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-foreground">{{ title }}</p>
                    <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <div v-if="$slots.default">
                    <slot />
                </div>
            </div>
            <span v-else class="text-sm text-muted-foreground">
                <slot />
            </span>
        </td>
    </tr>
</template>
