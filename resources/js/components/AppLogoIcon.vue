<script setup lang="ts">
import { Building2 } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <Building2 :class="className" v-bind="$attrs" :stroke-width="2.25" />
</template>
