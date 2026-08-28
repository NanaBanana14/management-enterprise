<script setup lang="ts">
defineProps<{ colspan?: number }>();
</script>

<template>
    <tr>
        <td :colspan="colspan ?? 1" class="px-4 py-12 text-center text-sm text-muted-foreground">
            <slot />
        </td>
    </tr>
</template>
