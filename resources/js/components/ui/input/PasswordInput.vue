<script setup lang="ts">
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref, type HTMLAttributes } from 'vue';

const props = defineProps<{
    defaultValue?: string;
    modelValue?: string;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

const visible = ref(false);
const inputRef = ref<HTMLInputElement>();

defineExpose({ focus: () => inputRef.value?.focus() });
</script>

<template>
    <div class="relative">
        <input
            ref="inputRef"
            v-model="modelValue"
            :type="visible ? 'text' : 'password'"
            :class="
                cn(
                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    props.class,
                )
            "
        />
        <button
            type="button"
            tabindex="-1"
            class="absolute right-0 top-0 flex h-10 w-10 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            @click="visible = !visible"
        >
            <EyeOff v-if="visible" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
