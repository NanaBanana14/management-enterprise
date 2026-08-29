<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCheck, Lock, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PayslipRow {
    id: number;
    employee: { id: number; name: string; employee_number: string };
    basic_salary: number;
    overtime_amount: number;
    allowance_total: number;
    bonus_total: number;
    deduction_total: number;
    net_salary: number;
    status: string;
}

const props = defineProps<{
    period: { id: number; name: string; start_date: string; end_date: string; status: string };
    payslips: PayslipRow[];
    canProcess: boolean;
    canApprove: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payroll', href: '/hris/payroll/periods' },
    { title: props.period.name, href: `/hris/payroll/periods/${props.period.id}` },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const statusVariant: Record<string, 'success' | 'warning' | 'outline'> = {
    draft: 'warning',
    approved: 'success',
    paid: 'outline',
};

const generating = ref(false);
function generate() {
    generating.value = true;
    router.post(route('hris.payroll.periods.generate', props.period.id), {}, { onFinish: () => (generating.value = false) });
}

const draftCount = computed(() => props.payslips.filter((p) => p.status === 'draft').length);
const canClose = computed(() => props.canApprove && props.period.status === 'open' && props.payslips.length > 0 && draftCount.value === 0);

const showApproveAll = ref(false);
const approvingAll = ref(false);
function approveAll() {
    approvingAll.value = true;
    router.post(
        route('hris.payroll.periods.approveAll', props.period.id),
        {},
        { onFinish: () => (approvingAll.value = false), onSuccess: () => (showApproveAll.value = false) },
    );
}

const showClosePeriod = ref(false);
const closing = ref(false);
function closePeriod() {
    closing.value = true;
    router.post(
        route('hris.payroll.periods.close', props.period.id),
        {},
        { onFinish: () => (closing.value = false), onSuccess: () => (showClosePeriod.value = false) },
    );
}
</script>

<template>
    <Head :title="period.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="period.name" :description="`${period.start_date} – ${period.end_date}`">
                <template #actions>
                    <div class="flex gap-2">
                        <Button v-if="canProcess" variant="outline" :disabled="generating" @click="generate">
                            <RefreshCw class="size-4" :class="{ 'animate-spin': generating }" />
                            Generate Payslips
                        </Button>
                        <Button v-if="canApprove && draftCount > 0" :disabled="approvingAll" @click="showApproveAll = true">
                            <CheckCheck class="size-4" />
                            Approve All ({{ draftCount }})
                        </Button>
                        <Button v-if="canClose" :disabled="closing" @click="showClosePeriod = true">
                            <Lock class="size-4" />
                            Close Period
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Employee</TableHead>
                        <TableHead>Basic</TableHead>
                        <TableHead>Overtime</TableHead>
                        <TableHead>Allowances</TableHead>
                        <TableHead>Bonuses</TableHead>
                        <TableHead>Deductions</TableHead>
                        <TableHead>Net</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="payslips.length === 0" :colspan="8">
                        No payslips generated yet for this period.
                    </TableEmpty>
                    <TableRow
                        v-for="payslip in payslips"
                        :key="payslip.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.payroll.payslips.show', payslip.id))"
                    >
                        <TableCell class="font-medium">
                            <div>{{ payslip.employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ payslip.employee.employee_number }}</div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ formatCurrency(payslip.basic_salary) }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ formatCurrency(payslip.overtime_amount) }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ formatCurrency(payslip.allowance_total) }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ formatCurrency(payslip.bonus_total) }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ formatCurrency(payslip.deduction_total) }}</TableCell>
                        <TableCell class="font-medium">{{ formatCurrency(payslip.net_salary) }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[payslip.status] ?? 'outline'">{{ payslip.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDialog
            :open="showApproveAll"
            title="Approve all draft payslips"
            :description="`This approves all ${draftCount} draft payslip(s) in ${period.name}. This cannot be undone.`"
            confirm-label="Approve All"
            :destructive="false"
            :processing="approvingAll"
            @update:open="(v) => (showApproveAll = v)"
            @confirm="approveAll"
        />

        <ConfirmDialog
            :open="showClosePeriod"
            title="Close payroll period"
            :description="`This marks every payslip in ${period.name} as paid and posts one journal entry to Finance for the total net salary. This cannot be undone.`"
            confirm-label="Close Period"
            :processing="closing"
            @update:open="(v) => (showClosePeriod = v)"
            @confirm="closePeriod"
        />
    </AppLayout>
</template>
