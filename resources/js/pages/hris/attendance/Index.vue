<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Clock, LogIn, LogOut } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface AttendanceRow {
    id: number;
    date: string;
    check_in_at: string | null;
    check_out_at: string | null;
    status: string;
    employee: { id: number; name: string; employee_number: string };
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    records: Paginated<AttendanceRow>;
    departments: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    filters: { department_id?: string; status?: string; from?: string; to?: string };
    canManage: boolean;
    hasEmployeeProfile: boolean;
    today: { checked_in: boolean; checked_out: boolean; check_in_at: string | null; check_out_at: string | null; status: string } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/hris/attendance' },
];

const departmentId = ref(props.filters.department_id ?? '');
const status = ref(props.filters.status ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

watch([departmentId, status, from, to], ([departmentValue, statusValue, fromValue, toValue]) => {
    router.get(
        route('hris.attendance.index'),
        {
            department_id: departmentValue || undefined,
            status: statusValue || undefined,
            from: fromValue || undefined,
            to: toValue || undefined,
        },
        { preserveState: true, replace: true },
    );
});

const statusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive' | 'secondary'> = {
    present: 'success',
    late: 'warning',
    absent: 'destructive',
    leave: 'secondary',
    permission: 'secondary',
    holiday: 'outline',
};

function statusLabel(value: string): string {
    return props.statuses.find((s) => s.value === value)?.label ?? value;
}

const departmentOptions = [{ value: '', label: 'All departments' }, ...props.departments.map((d) => ({ value: String(d.id), label: d.name }))];
const statusOptions = [{ value: '', label: 'All statuses' }, ...props.statuses.map((s) => ({ value: s.value, label: s.label }))];

const checkInForm = useForm({});
const checkOutForm = useForm({});

const punching = ref(false);

function checkIn() {
    punching.value = true;
    checkInForm.post(route('hris.attendance.check-in'), { onFinish: () => (punching.value = false) });
}

function checkOut() {
    punching.value = true;
    checkOutForm.post(route('hris.attendance.check-out'), { onFinish: () => (punching.value = false) });
}
</script>

<template>
    <Head title="Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Attendance" :description="canManage ? 'Company-wide attendance log.' : 'Your attendance history.'" />

            <Card v-if="hasEmployeeProfile" class="max-w-md">
                <CardContent class="flex items-center justify-between pt-6">
                    <div>
                        <p class="text-sm font-medium">Today</p>
                        <p class="text-sm text-muted-foreground">
                            <template v-if="today?.checked_in">
                                In at {{ today.check_in_at }}
                                <template v-if="today.checked_out"> · Out at {{ today.check_out_at }}</template>
                            </template>
                            <template v-else>Not checked in yet</template>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="!today?.checked_in" :disabled="punching" @click="checkIn">
                            <LogIn class="size-4" />
                            Check In
                        </Button>
                        <Button v-else-if="!today.checked_out" variant="outline" :disabled="punching" @click="checkOut">
                            <LogOut class="size-4" />
                            Check Out
                        </Button>
                        <Badge v-else variant="success">
                            <Clock class="mr-1 size-3" />
                            Done for today
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-3 sm:flex-row">
                <SearchableSelect v-if="canManage" v-model="departmentId" class="sm:w-56" :options="departmentOptions" />
                <SearchableSelect v-model="status" class="sm:w-40" :options="statusOptions" />
                <Input v-model="from" type="date" class="sm:w-44" />
                <Input v-model="to" type="date" class="sm:w-44" />
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-if="canManage">Employee</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Check In</TableHead>
                        <TableHead>Check Out</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="records.data.length === 0" :colspan="canManage ? 5 : 4">No attendance records found.</TableEmpty>
                    <TableRow v-for="record in records.data" :key="record.id">
                        <TableCell v-if="canManage">
                            <div>{{ record.employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ record.employee.employee_number }}</div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ record.date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ record.check_in_at ? record.check_in_at.slice(11, 16) : '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ record.check_out_at ? record.check_out_at.slice(11, 16) : '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[record.status] ?? 'outline'">{{ statusLabel(record.status) }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="records.from" :to="records.to" :total="records.total" :links="records.links" />
        </div>
    </AppLayout>
</template>
