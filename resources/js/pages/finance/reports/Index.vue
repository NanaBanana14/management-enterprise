<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface AccountBalance {
    code: string;
    name: string;
    balance: number;
}

defineProps<{
    accountsByType: Record<string, AccountBalance[]>;
    profitAndLoss: { revenue: number; expense: number; net: number };
    balanceSheet: { assets: number; liabilities: number; equity: number; liabilitiesPlusEquity: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/finance/reports' },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const typeLabels: Record<string, string> = {
    asset: 'Assets',
    liability: 'Liabilities',
    equity: 'Equity',
    revenue: 'Revenue',
    expense: 'Expenses',
};
</script>

<template>
    <Head title="Financial Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Financial Reports" description="Computed live from posted journal entries." />

            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Profit &amp; Loss</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted-foreground">Revenue</span><span>{{ formatCurrency(profitAndLoss.revenue) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Expenses</span><span>-{{ formatCurrency(profitAndLoss.expense) }}</span></div>
                        <div class="flex justify-between border-t pt-2 font-semibold">
                            <span>Net Income</span>
                            <span :class="profitAndLoss.net >= 0 ? 'text-emerald-600' : 'text-destructive'">{{ formatCurrency(profitAndLoss.net) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Balance Sheet</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted-foreground">Assets</span><span>{{ formatCurrency(balanceSheet.assets) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Liabilities</span><span>{{ formatCurrency(balanceSheet.liabilities) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Equity</span><span>{{ formatCurrency(balanceSheet.equity) }}</span></div>
                        <div class="flex justify-between border-t pt-2 font-semibold">
                            <span>Liabilities + Equity</span>
                            <span>{{ formatCurrency(balanceSheet.liabilitiesPlusEquity) }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-for="(accounts, type) in accountsByType" :key="type">
                <h2 class="mb-2 text-sm font-medium text-muted-foreground">{{ typeLabels[type] ?? type }}</h2>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Code</TableHead>
                            <TableHead>Account</TableHead>
                            <TableHead class="text-right">Balance</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="accounts.length === 0" :colspan="3">No accounts.</TableEmpty>
                        <TableRow v-for="account in accounts" :key="account.code">
                            <TableCell class="text-muted-foreground">{{ account.code }}</TableCell>
                            <TableCell>{{ account.name }}</TableCell>
                            <TableCell class="text-right font-medium">{{ formatCurrency(account.balance) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
