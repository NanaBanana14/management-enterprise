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

defineProps<{ permissionGroups: Record<string, string[]> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles & Permissions', href: '/admin/roles' },
    { title: 'New Role', href: '/admin/roles/create' },
];

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const submit = () => form.post(route('admin.roles.store'));
</script>

<template>
    <Head title="New Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="New Role" description="Name the role and choose its permissions." />

            <form class="space-y-6" @submit.prevent="submit">
                <Card class="max-w-xl">
                    <CardContent class="pt-6">
                        <div class="grid gap-2">
                            <Label for="name">Role name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Payroll Officer" />
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
                        Create Role
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
