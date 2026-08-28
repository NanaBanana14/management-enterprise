<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: { id: number; name: string }[];
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    users: Paginated<UserRow>;
    roles: string[];
    filters: { search?: string; role?: string; status?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/admin/users' },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const status = ref(props.filters.status ?? '');

watch([search, role, status], ([searchValue, roleValue, statusValue]) => {
    router.get(
        route('admin.users.index'),
        { search: searchValue || undefined, role: roleValue || undefined, status: statusValue || undefined },
        { preserveState: true, replace: true },
    );
});

const confirmDelete = ref<UserRow | null>(null);
const deleting = computed(() => router.processing);

function destroyUser() {
    if (!confirmDelete.value) return;
    router.delete(route('admin.users.destroy', confirmDelete.value.id), {
        onSuccess: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Users" description="Manage accounts and role assignments.">
                <template #actions>
                    <Button v-if="can('users.create')" as-child>
                        <Link :href="route('admin.users.create')">
                            <Plus class="size-4" />
                            Add User
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search name or email" class="pl-8" />
                </div>
                <Select v-model="role" class="sm:w-48">
                    <option value="">All roles</option>
                    <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                </Select>
                <Select v-model="status" class="sm:w-40">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </Select>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="w-24 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="users.data.length === 0" :colspan="5">No users found.</TableEmpty>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="font-medium">{{ user.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{ user.roles[0]?.name ?? '—' }}</Badge>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="user.is_active ? 'success' : 'outline'">
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button v-if="can('users.update')" as-child variant="ghost" size="icon">
                                    <Link :href="route('admin.users.edit', user.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button v-if="can('users.delete')" variant="ghost" size="icon" @click="confirmDelete = user">
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="users.from" :to="users.to" :total="users.total" :links="users.links" />
        </div>

        <ConfirmDialog
            :open="confirmDelete !== null"
            title="Delete user"
            :description="confirmDelete ? `This permanently deletes ${confirmDelete.name}'s account.` : ''"
            confirm-label="Delete"
            :processing="deleting"
            @update:open="(value) => !value && (confirmDelete = null)"
            @confirm="destroyUser"
        />
    </AppLayout>
</template>
