<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    groups: Record<string, string[]>;
    modelValue: string[];
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>();

function toggle(permission: string, checked: boolean) {
    const next = checked ? [...props.modelValue, permission] : props.modelValue.filter((p) => p !== permission);
    emit('update:modelValue', next);
}

function toggleGroup(permissions: string[], checked: boolean) {
    const rest = props.modelValue.filter((p) => !permissions.includes(p));
    emit('update:modelValue', checked ? [...rest, ...permissions] : rest);
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="(permissions, group) in groups" :key="group" class="rounded-lg border p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-medium capitalize">{{ group }}</span>
                <button
                    type="button"
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click="toggleGroup(permissions, !permissions.every((p) => modelValue.includes(p)))"
                >
                    {{ permissions.every((p) => modelValue.includes(p)) ? 'Clear' : 'Select all' }}
                </button>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                <Label v-for="permission in permissions" :key="permission" class="flex items-center gap-2 text-sm font-normal">
                    <Checkbox
                        :checked="modelValue.includes(permission)"
                        @update:checked="(checked) => toggle(permission, !!checked)"
                    />
                    <span>{{ permission.split('.')[1] }}</span>
                </Label>
            </div>
        </div>
    </div>
</template>
