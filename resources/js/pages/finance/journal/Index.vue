<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { BookText, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

const props = defineProps<{ entries: Paginated<EntryRow>; filters: { search?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal', href: '/finance/journal' },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('journal.create');

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(
        route('finance.journal.index'),
        { search: value || undefined },
        { preserveState: true, replace: true },
    );
});

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

            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search reference or description" class="pl-8" />
            </div>

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
                    <TableEmpty
                        v-if="entries.data.length === 0 && search"
                        :colspan="4"
                        :icon="Search"
                        title="No matching journal entries"
                        description="Try a different reference or description."
                    >
                        <Button variant="outline" size="sm" @click="search = ''">Clear search</Button>
                    </TableEmpty>
                    <TableEmpty
                        v-else-if="entries.data.length === 0"
                        :colspan="4"
                        :icon="BookText"
                        title="No journal entries yet"
                        description="Create your first journal entry to start the ledger."
                    >
                        <Button v-if="canCreate" size="sm" as-child>
                            <Link :href="route('finance.journal.create')">
                                <Plus class="size-4" />
                                New Entry
                            </Link>
                        </Button>
                    </TableEmpty>
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
