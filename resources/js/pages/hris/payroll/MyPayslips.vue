<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface PayslipRow {
    id: number;
    period: string;
    net_salary: number;
    status: string;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    payslips: Paginated<PayslipRow>;
    canManage: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payroll', href: '/hris/payroll' },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const statusVariant: Record<string, 'success' | 'outline'> = {
    approved: 'success',
    paid: 'outline',
};
</script>

<template>
    <Head title="Payroll" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Payroll" description="Your payslip history." />

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Period</TableHead>
                        <TableHead>Net Salary</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="payslips.data.length === 0" :colspan="3">No payslips yet.</TableEmpty>
                    <TableRow
                        v-for="payslip in payslips.data"
                        :key="payslip.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.payroll.payslips.show', payslip.id))"
                    >
                        <TableCell class="font-medium">{{ payslip.period }}</TableCell>
                        <TableCell>{{ formatCurrency(payslip.net_salary) }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[payslip.status] ?? 'outline'">{{ payslip.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="payslips.from" :to="payslips.to" :total="payslips.total" :links="payslips.links" />
        </div>
    </AppLayout>
</template>
