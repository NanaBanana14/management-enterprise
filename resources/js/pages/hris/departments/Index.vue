<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface DepartmentRow {
    id: number;
    name: string;
    code: string;
    manager: { id: number; name: string } | null;
    employees_count: number;
    positions_count: number;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    departments: Paginated<DepartmentRow>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departments', href: '/hris/departments' },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(route('hris.departments.index'), { search: value || undefined }, { preserveState: true, replace: true });
});

const confirmDelete = ref<DepartmentRow | null>(null);
const deleting = computed(() => router.processing);

function destroyDepartment() {
    if (!confirmDelete.value) return;
    router.delete(route('hris.departments.destroy', confirmDelete.value.id), {
        onSuccess: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="Departments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Departments" description="Organizational units and their managers.">
                <template #actions>
                    <Button v-if="can('department.manage')" as-child>
                        <Link :href="route('hris.departments.create')">
                            <Plus class="size-4" />
                            Add Department
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search name or code" class="pl-8" />
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Code</TableHead>
                        <TableHead>Manager</TableHead>
                        <TableHead>Positions</TableHead>
                        <TableHead>Employees</TableHead>
                        <TableHead class="w-24 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="departments.data.length === 0" :colspan="6">No departments found.</TableEmpty>
                    <TableRow v-for="department in departments.data" :key="department.id">
                        <TableCell class="font-medium">{{ department.name }}</TableCell>
                        <TableCell>
                            <Badge variant="outline">{{ department.code }}</Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ department.manager?.name ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ department.positions_count }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ department.employees_count }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button v-if="can('department.manage')" as-child variant="ghost" size="icon">
                                    <Link :href="route('hris.departments.edit', department.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="can('department.manage')"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="department.employees_count > 0"
                                    @click="confirmDelete = department"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="departments.from" :to="departments.to" :total="departments.total" :links="departments.links" />
        </div>

        <ConfirmDialog
            :open="confirmDelete !== null"
            title="Delete department"
            :description="confirmDelete ? `This removes the '${confirmDelete.name}' department.` : ''"
            confirm-label="Delete"
            :processing="deleting"
            @update:open="(value) => !value && (confirmDelete = null)"
            @confirm="destroyDepartment"
        />
    </AppLayout>
</template>
