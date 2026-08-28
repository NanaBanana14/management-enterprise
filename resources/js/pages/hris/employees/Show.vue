<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Archive, Pencil, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

interface EmployeeDetail {
    id: number;
    employee_number: string;
    name: string;
    email: string;
    phone: string | null;
    employment_type: string;
    employment_status: string;
    join_date: string;
    basic_salary: string;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    photo_url: string | null;
    department: { id: number; name: string };
    position: { id: number; name: string };
    manager: { id: number; name: string } | null;
    subordinates: { id: number; name: string }[];
}

interface AttendanceRecord {
    date: string;
    check_in_at: string | null;
    check_out_at: string | null;
    status: string;
}

const props = defineProps<{
    employee: EmployeeDetail;
    recentAttendance: AttendanceRecord[];
    monthlyAttendance: Record<string, number>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/hris/employees' },
    { title: props.employee.name, href: `/hris/employees/${props.employee.id}` },
];

const page = usePage<SharedData>();
const can = (permission: string) => page.props.auth.permissions.includes(permission);

const statusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive'> = {
    active: 'success',
    on_leave: 'warning',
    suspended: 'outline',
    terminated: 'destructive',
};

const attendanceStatusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive' | 'secondary'> = {
    present: 'success',
    late: 'warning',
    absent: 'destructive',
    leave: 'secondary',
    permission: 'secondary',
    holiday: 'outline',
};

function formatCurrency(value: string): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

const confirmArchive = ref(false);

function archiveEmployee() {
    router.delete(route('hris.employees.destroy', props.employee.id), {
        onSuccess: () => (confirmArchive.value = false),
    });
}
</script>

<template>
    <Head :title="employee.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 items-center justify-center overflow-hidden rounded-full bg-muted">
                        <img v-if="employee.photo_url" :src="employee.photo_url" alt="" class="size-full object-cover" />
                        <Upload v-else class="size-6 text-muted-foreground" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-semibold tracking-tight">{{ employee.name }}</h1>
                            <Badge :variant="statusVariant[employee.employment_status] ?? 'outline'">{{ employee.employment_status.replace('_', ' ') }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ employee.employee_number }} · {{ employee.position.name }} · {{ employee.department.name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="can('employee.update')" as-child variant="outline">
                        <Link :href="route('hris.employees.edit', employee.id)">
                            <Pencil class="size-4" />
                            Edit
                        </Link>
                    </Button>
                    <Button v-if="can('employee.delete')" variant="outline" @click="confirmArchive = true">
                        <Archive class="size-4" />
                        Archive
                    </Button>
                </div>
            </div>

            <Tabs default-value="personal">
                <TabsList>
                    <TabsTrigger value="personal">Personal Information</TabsTrigger>
                    <TabsTrigger value="employment">Employment</TabsTrigger>
                    <TabsTrigger value="attendance">Attendance</TabsTrigger>
                </TabsList>

                <TabsContent value="personal">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Card>
                            <CardContent class="grid gap-3 pt-6 text-sm">
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Email</span><span class="col-span-2">{{ employee.email }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Phone</span><span class="col-span-2">{{ employee.phone ?? '—' }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Address</span><span class="col-span-2">{{ employee.address ?? '—' }}</span></div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="grid gap-3 pt-6 text-sm">
                                <div class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Emergency Contact</div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Name</span><span class="col-span-2">{{ employee.emergency_contact_name ?? '—' }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Phone</span><span class="col-span-2">{{ employee.emergency_contact_phone ?? '—' }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Relationship</span><span class="col-span-2">{{ employee.emergency_contact_relationship ?? '—' }}</span></div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="employment">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Card>
                            <CardContent class="grid gap-3 pt-6 text-sm">
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Department</span><span class="col-span-2">{{ employee.department.name }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Position</span><span class="col-span-2">{{ employee.position.name }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Employment type</span><span class="col-span-2 capitalize">{{ employee.employment_type.replace('_', ' ') }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Join date</span><span class="col-span-2">{{ formatDate(employee.join_date) }}</span></div>
                                <div class="grid grid-cols-3 gap-2"><span class="text-muted-foreground">Basic salary</span><span class="col-span-2">{{ formatCurrency(employee.basic_salary) }}</span></div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="grid gap-3 pt-6 text-sm">
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="text-muted-foreground">Manager</span>
                                    <span class="col-span-2">
                                        <Link v-if="employee.manager" :href="route('hris.employees.show', employee.manager.id)" class="text-primary hover:underline">
                                            {{ employee.manager.name }}
                                        </Link>
                                        <span v-else>—</span>
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="text-muted-foreground">Direct reports</span>
                                    <div class="col-span-2 flex flex-wrap gap-1">
                                        <Link
                                            v-for="report in employee.subordinates"
                                            :key="report.id"
                                            :href="route('hris.employees.show', report.id)"
                                        >
                                            <Badge variant="secondary" class="hover:bg-muted">{{ report.name }}</Badge>
                                        </Link>
                                        <span v-if="employee.subordinates.length === 0" class="text-muted-foreground">None</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="attendance">
                    <div class="grid gap-4">
                        <div class="flex flex-wrap gap-3">
                            <Badge v-for="(count, status) in monthlyAttendance" :key="status" :variant="attendanceStatusVariant[status] ?? 'outline'">
                                {{ status }}: {{ count }} this month
                            </Badge>
                            <span v-if="Object.keys(monthlyAttendance).length === 0" class="text-sm text-muted-foreground">
                                No attendance recorded this month yet.
                            </span>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Check In</TableHead>
                                    <TableHead>Check Out</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="recentAttendance.length === 0" :colspan="4">No attendance history.</TableEmpty>
                                <TableRow v-for="record in recentAttendance" :key="record.date">
                                    <TableCell class="text-muted-foreground">{{ record.date }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{ record.check_in_at ? record.check_in_at.slice(11, 16) : '—' }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{ record.check_out_at ? record.check_out_at.slice(11, 16) : '—' }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="attendanceStatusVariant[record.status] ?? 'outline'">{{ record.status }}</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <ConfirmDialog
            :open="confirmArchive"
            title="Archive employee"
            :description="`This archives ${employee.name}'s record. Archived employees are hidden from the default list but not permanently deleted.`"
            confirm-label="Archive"
            @update:open="(value) => (confirmArchive = value)"
            @confirm="archiveEmployee"
        />
    </AppLayout>
</template>
