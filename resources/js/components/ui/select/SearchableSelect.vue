<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Check, ChevronDown, Search } from 'lucide-vue-next';
import { computed, nextTick, ref, watch, type HTMLAttributes } from 'vue';

interface Option {
    value: string | number;
    label: string;
}

const props = defineProps<{
    modelValue: string | number | null | undefined;
    options: Option[];
    placeholder?: string;
    disabled?: boolean;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>();

const open = ref(false);
const query = ref('');
const root = ref<HTMLElement>();
const searchInput = ref<HTMLInputElement>();

const selected = computed(() => props.options.find((o) => String(o.value) === String(props.modelValue)));

const filtered = computed(() => {
    if (!query.value.trim()) return props.options;
    const needle = query.value.trim().toLowerCase();
    return props.options.filter((o) => o.label.toLowerCase().includes(needle));
});

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        query.value = '';
        nextTick(() => searchInput.value?.focus());
    }
}

function select(option: Option) {
    emit('update:modelValue', option.value);
    open.value = false;
}

function onClickOutside(event: MouseEvent) {
    if (root.value && !root.value.contains(event.target as Node)) {
        open.value = false;
    }
}

watch(open, (value) => {
    if (value) {
        document.addEventListener('mousedown', onClickOutside);
    } else {
        document.removeEventListener('mousedown', onClickOutside);
    }
});
</script>

<template>
    <div ref="root" :class="cn('relative', props.class)">
        <button
            type="button"
            :disabled="disabled"
            :class="
                cn(
                    'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm outline-none transition-colors focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                )
            "
            @click="toggle"
        >
            <span :class="cn('truncate text-left', !selected && 'text-muted-foreground')">
                {{ selected ? selected.label : (placeholder ?? 'Select…') }}
            </span>
            <ChevronDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
        </button>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full min-w-[12rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
        >
            <div class="flex items-center gap-2 border-b px-3 py-2">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Search…"
                    class="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    @keydown.escape="open = false"
                />
            </div>
            <div class="max-h-60 overflow-y-auto p-1">
                <button
                    v-for="option in filtered"
                    :key="option.value"
                    type="button"
                    class="flex w-full items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                    :class="String(option.value) === String(modelValue) && 'bg-accent/60'"
                    @click="select(option)"
                >
                    <span class="truncate">{{ option.label }}</span>
                    <Check v-if="String(option.value) === String(modelValue)" class="size-4 shrink-0 text-primary" />
                </button>
                <p v-if="filtered.length === 0" class="px-2 py-3 text-center text-sm text-muted-foreground">No results found.</p>
            </div>
        </div>
    </div>
</template>
