<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LineRow {
    account: string;
    debit: number;
    credit: number;
    memo: string | null;
}

const props = defineProps<{
    entry: {
        id: number;
        date: string;
        reference: string;
        description: string | null;
        creator: string | null;
        lines: LineRow[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal', href: '/finance/journal' },
    { title: props.entry.reference, href: `/finance/journal/${props.entry.id}` },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const totalDebit = computed(() => props.entry.lines.reduce((sum, l) => sum + l.debit, 0));
const totalCredit = computed(() => props.entry.lines.reduce((sum, l) => sum + l.credit, 0));
</script>

<template>
    <Head :title="entry.reference" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ entry.reference }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ entry.date }} · {{ entry.description ?? 'No description' }}
                    <span v-if="entry.creator"> · Posted by {{ entry.creator }}</span>
                </p>
            </div>

            <Card class="max-w-3xl">
                <CardContent class="pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Account</TableHead>
                                <TableHead>Memo</TableHead>
                                <TableHead class="text-right">Debit</TableHead>
                                <TableHead class="text-right">Credit</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(line, index) in entry.lines" :key="index">
                                <TableCell class="font-medium">{{ line.account }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ line.memo ?? '—' }}</TableCell>
                                <TableCell class="text-right">{{ line.debit ? formatCurrency(line.debit) : '—' }}</TableCell>
                                <TableCell class="text-right">{{ line.credit ? formatCurrency(line.credit) : '—' }}</TableCell>
                            </TableRow>
                        </TableBody>
                        <TableFooter>
                            <TableRow>
                                <TableCell colspan="2" class="font-medium">Total</TableCell>
                                <TableCell class="text-right font-medium">{{ formatCurrency(totalDebit) }}</TableCell>
                                <TableCell class="text-right font-medium">{{ formatCurrency(totalCredit) }}</TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
