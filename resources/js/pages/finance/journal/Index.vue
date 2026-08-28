<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

interface EntryRow {
    id: number;
    date: string;
    reference: string;
    description: string | null;
    total: number;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{ entries: Paginated<EntryRow> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal', href: '/finance/journal' },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('journal.create');

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}
</script>

<template>
    <Head title="Journal Entries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Journal Entries" description="Every posted double-entry transaction.">
                <template #actions>
                    <Button v-if="canCreate" as-child>
                        <Link :href="route('finance.journal.create')">
                            <Plus class="size-4" />
                            New Entry
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Date</TableHead>
                        <TableHead>Reference</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="entries.data.length === 0" :colspan="4">No journal entries yet.</TableEmpty>
                    <TableRow
                        v-for="entry in entries.data"
                        :key="entry.id"
                        class="cursor-pointer"
                        @click="router.visit(route('finance.journal.show', entry.id))"
                    >
                        <TableCell class="text-muted-foreground">{{ entry.date }}</TableCell>
                        <TableCell class="font-medium">{{ entry.reference }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ entry.description ?? '—' }}</TableCell>
                        <TableCell class="text-right font-medium">{{ formatCurrency(entry.total) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="entries.from" :to="entries.to" :total="entries.total" :links="entries.links" />
        </div>
    </AppLayout>
</template>
