<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface RoleRow {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
}

defineProps<{ roles: RoleRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles & Permissions', href: '/admin/roles' },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const confirmDelete = ref<RoleRow | null>(null);
const deleting = computed(() => router.processing);

function destroyRole() {
    if (!confirmDelete.value) return;
    router.delete(route('admin.roles.destroy', confirmDelete.value.id), {
        onSuccess: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="Roles & Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Roles & Permissions" description="Define what each role is allowed to do.">
                <template #actions>
                    <Button v-if="can('roles.create')" as-child>
                        <Link :href="route('admin.roles.create')">
                            <Plus class="size-4" />
                            Add Role
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Role</TableHead>
                        <TableHead>Permissions</TableHead>
                        <TableHead>Users</TableHead>
                        <TableHead class="w-24 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="roles.length === 0" :colspan="4">No roles found.</TableEmpty>
                    <TableRow v-for="role in roles" :key="role.id">
                        <TableCell class="font-medium">{{ role.name }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{ role.permissions_count }} permissions</Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ role.users_count }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button v-if="can('roles.update')" as-child variant="ghost" size="icon">
                                    <Link :href="route('admin.roles.edit', role.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="can('roles.delete')"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="role.users_count > 0"
                                    @click="confirmDelete = role"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDialog
            :open="confirmDelete !== null"
            title="Delete role"
            :description="confirmDelete ? `This removes the '${confirmDelete.name}' role.` : ''"
            confirm-label="Delete"
            :processing="deleting"
            @update:open="(value) => !value && (confirmDelete = null)"
            @confirm="destroyRole"
        />
    </AppLayout>
</template>
