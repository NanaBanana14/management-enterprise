<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, LoaderCircle, Plus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface LeaveRow {
    id: number;
    start_date: string;
    end_date: string;
    days: number;
    reason: string | null;
    status: string;
    rejection_reason: string | null;
    employee: { id: number; name: string; employee_number: string };
    leave_type: { id: number; name: string };
    approver: { id: number; name: string } | null;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    requests: Paginated<LeaveRow>;
    leaveTypes: { id: number; name: string }[];
    balances: { leave_type: string; allocated: number; used: number; remaining: number }[];
    statuses: { value: string; label: string }[];
    filters: { status?: string };
    canApprove: boolean;
    hasEmployeeProfile: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave', href: '/hris/leave' },
];

const status = ref(props.filters.status ?? '');
watch(status, (value) => {
    router.get(route('hris.leave.index'), { status: value || undefined }, { preserveState: true, replace: true });
});

const statusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive' | 'secondary'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'destructive',
    cancelled: 'outline',
};

// Request form
const showRequestDialog = ref(false);
const requestForm = useForm({
    leave_type_id: '' as number | '',
    start_date: '',
    end_date: '',
    reason: '',
});

function submitRequest() {
    requestForm.post(route('hris.leave.store'), {
        onSuccess: () => {
            showRequestDialog.value = false;
            requestForm.reset();
        },
    });
}

// Reject flow
const rejectTarget = ref<LeaveRow | null>(null);
const rejectForm = useForm({ rejection_reason: '' });

function submitReject() {
    if (!rejectTarget.value) return;
    rejectForm.post(route('hris.leave.reject', rejectTarget.value.id), {
        onSuccess: () => {
            rejectTarget.value = null;
            rejectForm.reset();
        },
    });
}

function approve(row: LeaveRow) {
    router.post(route('hris.leave.approve', row.id));
}

const cancelTarget = ref<LeaveRow | null>(null);
function cancelRequest() {
    if (!cancelTarget.value) return;
    router.post(route('hris.leave.cancel', cancelTarget.value.id), {}, { onSuccess: () => (cancelTarget.value = null) });
}
</script>

<template>
    <Head title="Leave" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Leave" :description="canApprove ? 'Review and approve leave requests.' : 'Your leave requests and balance.'">
                <template #actions>
                    <Button v-if="hasEmployeeProfile" @click="showRequestDialog = true">
                        <Plus class="size-4" />
                        Request Leave
                    </Button>
                </template>
            </PageHeader>

            <div v-if="balances.length" class="grid gap-4 sm:grid-cols-3">
                <Card v-for="balance in balances" :key="balance.leave_type">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ balance.leave_type }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ balance.remaining }}</div>
                        <p class="text-xs text-muted-foreground">of {{ balance.allocated }} days remaining</p>
                    </CardContent>
                </Card>
            </div>

            <Select v-if="canApprove" v-model="status" class="sm:w-44">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </Select>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-if="canApprove">Employee</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Dates</TableHead>
                        <TableHead>Days</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="requests.data.length === 0" :colspan="canApprove ? 6 : 5">No leave requests.</TableEmpty>
                    <TableRow v-for="row in requests.data" :key="row.id">
                        <TableCell v-if="canApprove">
                            <div>{{ row.employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ row.employee.employee_number }}</div>
                        </TableCell>
                        <TableCell>{{ row.leave_type.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ row.start_date }} – {{ row.end_date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ row.days }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[row.status] ?? 'outline'">{{ row.status }}</Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <template v-if="canApprove && row.status === 'pending'">
                                    <Button variant="ghost" size="icon" @click="approve(row)">
                                        <Check class="size-4 text-emerald-600" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="rejectTarget = row">
                                        <X class="size-4 text-destructive" />
                                    </Button>
                                </template>
                                <Button
                                    v-else-if="!canApprove && row.status === 'pending'"
                                    variant="outline"
                                    size="sm"
                                    @click="cancelTarget = row"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="requests.from" :to="requests.to" :total="requests.total" :links="requests.links" />
        </div>

        <Dialog :open="showRequestDialog" @update:open="(v) => (showRequestDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Request Leave</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitRequest">
                    <div class="grid gap-2">
                        <Label for="leave_type_id">Leave type</Label>
                        <Select id="leave_type_id" v-model="requestForm.leave_type_id">
                            <option value="" disabled>Select a type</option>
                            <option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </Select>
                        <InputError :message="requestForm.errors.leave_type_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start date</Label>
                            <Input id="start_date" v-model="requestForm.start_date" type="date" />
                            <InputError :message="requestForm.errors.start_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_date">End date</Label>
                            <Input id="end_date" v-model="requestForm.end_date" type="date" />
                            <InputError :message="requestForm.errors.end_date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="reason">Reason</Label>
                        <Textarea id="reason" v-model="requestForm.reason" rows="3" />
                        <InputError :message="requestForm.errors.reason" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showRequestDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="requestForm.processing">
                            <LoaderCircle v-if="requestForm.processing" class="size-4 animate-spin" />
                            Submit
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="rejectTarget !== null" @update:open="(v) => !v && (rejectTarget = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Leave Request</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitReject">
                    <div class="grid gap-2">
                        <Label for="rejection_reason">Reason (optional)</Label>
                        <Textarea id="rejection_reason" v-model="rejectForm.rejection_reason" rows="3" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="rejectTarget = null">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="rejectForm.processing">Reject</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="cancelTarget !== null"
            title="Cancel leave request"
            description="This withdraws your pending leave request."
            confirm-label="Cancel Request"
            @update:open="(v) => !v && (cancelTarget = null)"
            @confirm="cancelRequest"
        />
    </AppLayout>
</template>
