<script setup lang="ts">
import { TabsRoot, useForwardPropsEmits, type TabsRootEmits, type TabsRootProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps<TabsRootProps>();
const emits = defineEmits<TabsRootEmits>();

const forwarded = useForwardPropsEmits(computed(() => props), emits);
</script>

<template>
    <TabsRoot v-bind="forwarded">
        <slot />
    </TabsRoot>
</template>
