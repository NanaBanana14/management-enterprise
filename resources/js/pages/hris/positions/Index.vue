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

interface PositionRow {
    id: number;
    name: string;
    code: string;
    salary_min: string | null;
    salary_max: string | null;
    department: { id: number; name: string };
    employees_count: number;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    positions: Paginated<PositionRow>;
    departments: { id: number; name: string }[];
    filters: { search?: string; department_id?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Positions', href: '/hris/positions' },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const search = ref(props.filters.search ?? '');
const departmentId = ref(props.filters.department_id ?? '');

watch([search, departmentId], ([searchValue, departmentValue]) => {
    router.get(
        route('hris.positions.index'),
        { search: searchValue || undefined, department_id: departmentValue || undefined },
        { preserveState: true, replace: true },
    );
});

function formatSalary(value: string | null): string {
    if (!value) return '—';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}

const confirmDelete = ref<PositionRow | null>(null);
const deleting = computed(() => router.processing);

function destroyPosition() {
    if (!confirmDelete.value) return;
    router.delete(route('hris.positions.destroy', confirmDelete.value.id), {
        onSuccess: () => (confirmDelete.value = null),
    });
}
</script>

<template>
    <Head title="Positions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Positions" description="Job titles and their salary bands.">
                <template #actions>
                    <Button v-if="can('position.manage')" as-child>
                        <Link :href="route('hris.positions.create')">
                            <Plus class="size-4" />
                            Add Position
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search name or code" class="pl-8" />
                </div>
                <Select v-model="departmentId" class="sm:w-56">
                    <option value="">All departments</option>
                    <option v-for="department in departments" :key="department.id" :value="String(department.id)">{{ department.name }}</option>
                </Select>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Code</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Salary Range</TableHead>
                        <TableHead>Employees</TableHead>
                        <TableHead class="w-24 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="positions.data.length === 0" :colspan="6">No positions found.</TableEmpty>
                    <TableRow v-for="position in positions.data" :key="position.id">
                        <TableCell class="font-medium">{{ position.name }}</TableCell>
                        <TableCell>
                            <Badge variant="outline">{{ position.code }}</Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ position.department.name }}</TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatSalary(position.salary_min) }} – {{ formatSalary(position.salary_max) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ position.employees_count }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button v-if="can('position.manage')" as-child variant="ghost" size="icon">
                                    <Link :href="route('hris.positions.edit', position.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="can('position.manage')"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="position.employees_count > 0"
                                    @click="confirmDelete = position"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="positions.from" :to="positions.to" :total="positions.total" :links="positions.links" />
        </div>

        <ConfirmDialog
            :open="confirmDelete !== null"
            title="Delete position"
            :description="confirmDelete ? `This removes the '${confirmDelete.name}' position.` : ''"
            confirm-label="Delete"
            :processing="deleting"
            @update:open="(value) => !value && (confirmDelete = null)"
            @confirm="destroyPosition"
        />
    </AppLayout>
</template>
