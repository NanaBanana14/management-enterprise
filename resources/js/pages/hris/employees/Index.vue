<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Archive, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface EmployeeRow {
    id: number;
    employee_number: string;
    name: string;
    email: string;
    employment_status: string;
    department: { id: number; name: string };
    position: { id: number; name: string };
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    employees: Paginated<EmployeeRow>;
    departments: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    filters: { search?: string; department_id?: string; status?: string; archived?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/hris/employees' },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const search = ref(props.filters.search ?? '');
const departmentId = ref(props.filters.department_id ?? '');
const status = ref(props.filters.status ?? '');
const archived = ref(props.filters.archived === 'true');

watch([search, departmentId, status, archived], ([searchValue, departmentValue, statusValue, archivedValue]) => {
    router.get(
        route('hris.employees.index'),
        {
            search: searchValue || undefined,
            department_id: departmentValue || undefined,
            status: statusValue || undefined,
            archived: archivedValue ? 'true' : undefined,
        },
        { preserveState: true, replace: true },
    );
});

const statusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive'> = {
    active: 'success',
    on_leave: 'warning',
    suspended: 'outline',
    terminated: 'destructive',
};

function statusLabel(value: string): string {
    return props.statuses.find((s) => s.value === value)?.label ?? value;
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head title="Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Employees" description="Every employee record, with department, position, and status.">
                <template #actions>
                    <Button v-if="can('employee.create')" as-child>
                        <Link :href="route('hris.employees.create')">
                            <Plus class="size-4" />
                            Add Employee
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search name, email, or ID" class="pl-8" />
                </div>
                <SearchableSelect
                    v-model="departmentId"
                    class="sm:w-56"
                    placeholder="All departments"
                    :options="[
                        { value: '', label: 'All departments' },
                        ...departments.map((d) => ({ value: String(d.id), label: d.name })),
                    ]"
                />
                <SearchableSelect
                    v-model="status"
                    class="sm:w-44"
                    placeholder="All statuses"
                    :options="[{ value: '', label: 'All statuses' }, ...statuses.map((s) => ({ value: s.value, label: s.label }))]"
                />
                <Label class="flex items-center gap-2 whitespace-nowrap text-sm">
                    <Checkbox v-model:checked="archived" />
                    Archived only
                </Label>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Employee</TableHead>
                        <TableHead>ID</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Position</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="w-16 text-right"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="employees.data.length === 0" :colspan="6">No employees found.</TableEmpty>
                    <TableRow v-for="employee in employees.data" :key="employee.id" class="cursor-pointer" @click="router.visit(route('hris.employees.show', employee.id))">
                        <TableCell class="font-medium">
                            <div class="flex items-center gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                    {{ initials(employee.name) }}
                                </div>
                                <div>
                                    <div>{{ employee.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ employee.email }}</div>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ employee.employee_number }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ employee.department.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ employee.position.name }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[employee.employment_status] ?? 'outline'">
                                {{ statusLabel(employee.employment_status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Archive v-if="archived" class="ml-auto size-4 text-muted-foreground" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="employees.from" :to="employees.to" :total="employees.total" :links="employees.links" />
        </div>
    </AppLayout>
</template>
