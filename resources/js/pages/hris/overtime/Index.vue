<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, LoaderCircle, Plus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface OvertimeRow {
    id: number;
    date: string;
    hours: number;
    reason: string | null;
    status: string;
    rejection_reason: string | null;
    employee: { id: number; name: string; employee_number: string };
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
    requests: Paginated<OvertimeRow>;
    statuses: { value: string; label: string }[];
    filters: { status?: string };
    canApprove: boolean;
    hasEmployeeProfile: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Overtime', href: '/hris/overtime' },
];

const status = ref(props.filters.status ?? '');
watch(status, (value) => {
    router.get(route('hris.overtime.index'), { status: value || undefined }, { preserveState: true, replace: true });
});

const statusOptions = [{ value: '', label: 'All statuses' }, ...props.statuses.map((s) => ({ value: s.value, label: s.label }))];

const statusVariant: Record<string, 'success' | 'warning' | 'outline' | 'destructive' | 'secondary'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'destructive',
    cancelled: 'outline',
};

const showRequestDialog = ref(false);
const requestForm = useForm({
    date: '',
    hours: '',
    reason: '',
});

function submitRequest() {
    requestForm.post(route('hris.overtime.store'), {
        onSuccess: () => {
            showRequestDialog.value = false;
            requestForm.reset();
        },
    });
}

const rejectTarget = ref<OvertimeRow | null>(null);
const rejectForm = useForm({ rejection_reason: '' });

function submitReject() {
    if (!rejectTarget.value) return;
    rejectForm.post(route('hris.overtime.reject', rejectTarget.value.id), {
        onSuccess: () => {
            rejectTarget.value = null;
            rejectForm.reset();
        },
    });
}

function approve(row: OvertimeRow) {
    router.post(route('hris.overtime.approve', row.id));
}

const cancelTarget = ref<OvertimeRow | null>(null);
function cancelRequest() {
    if (!cancelTarget.value) return;
    router.post(route('hris.overtime.cancel', cancelTarget.value.id), {}, { onSuccess: () => (cancelTarget.value = null) });
}
</script>

<template>
    <Head title="Overtime" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Overtime" :description="canApprove ? 'Review and approve overtime requests.' : 'Your overtime requests.'">
                <template #actions>
                    <Button v-if="hasEmployeeProfile" @click="showRequestDialog = true">
                        <Plus class="size-4" />
                        Log Overtime
                    </Button>
                </template>
            </PageHeader>

            <SearchableSelect v-if="canApprove" v-model="status" class="sm:w-44" :options="statusOptions" />

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-if="canApprove">Employee</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Hours</TableHead>
                        <TableHead>Reason</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="requests.data.length === 0" :colspan="canApprove ? 6 : 5">No overtime requests.</TableEmpty>
                    <TableRow v-for="row in requests.data" :key="row.id">
                        <TableCell v-if="canApprove">
                            <div>{{ row.employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ row.employee.employee_number }}</div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ row.date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ row.hours }}h</TableCell>
                        <TableCell class="max-w-xs truncate text-muted-foreground">{{ row.reason ?? '—' }}</TableCell>
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
                    <DialogTitle>Log Overtime</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitRequest">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="date">Date</Label>
                            <Input id="date" v-model="requestForm.date" type="date" />
                            <InputError :message="requestForm.errors.date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="hours">Hours</Label>
                            <Input id="hours" v-model="requestForm.hours" type="number" min="0.5" max="12" step="0.5" />
                            <InputError :message="requestForm.errors.hours" />
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
                    <DialogTitle>Reject Overtime Request</DialogTitle>
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
            title="Cancel overtime request"
            description="This withdraws your pending overtime request."
            confirm-label="Cancel Request"
            @update:open="(v) => !v && (cancelTarget = null)"
            @confirm="cancelRequest"
        />
    </AppLayout>
</template>
