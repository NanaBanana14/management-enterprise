<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import PermissionPicker from '@/components/PermissionPicker.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    role: { id: number; name: string; permissions: string[] };
    permissionGroups: Record<string, string[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles & Permissions', href: '/admin/roles' },
    { title: props.role.name, href: `/admin/roles/${props.role.id}/edit` },
];

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

const submit = () => form.put(route('admin.roles.update', props.role.id));
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="`Edit ${role.name}`" description="Update the role name and its permissions." />

            <form class="space-y-6" @submit.prevent="submit">
                <Card class="max-w-xl">
                    <CardContent class="pt-6">
                        <div class="grid gap-2">
                            <Label for="name">Role name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>
                    </CardContent>
                </Card>

                <div>
                    <h2 class="mb-3 text-sm font-medium">Permissions</h2>
                    <PermissionPicker v-model="form.permissions" :groups="permissionGroups" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" type="button" as-child>
                        <Link :href="route('admin.roles.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
