<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Select } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface LogRow {
    id: number;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    ip_address: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
}

interface Paginated<T> {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    logs: Paginated<LogRow>;
    users: { id: number; name: string }[];
    actions: string[];
    filters: { user_id?: string; action?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audit Log', href: '/admin/audit-logs' },
];

const userId = ref(props.filters.user_id ?? '');
const action = ref(props.filters.action ?? '');

watch([userId, action], ([userIdValue, actionValue]) => {
    router.get(
        route('admin.audit-logs.index'),
        { user_id: userIdValue || undefined, action: actionValue || undefined },
        { preserveState: true, replace: true },
    );
});

function modelName(type: string | null): string {
    return type ? type.split('\\').pop()! : '—';
}

const actionVariant: Record<string, 'success' | 'warning' | 'destructive'> = {
    created: 'success',
    updated: 'warning',
    deleted: 'destructive',
};
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Audit Log" description="Every create, update, and delete tracked across the system." />

            <div class="flex flex-col gap-3 sm:flex-row">
                <Select v-model="userId" class="sm:w-56">
                    <option value="">All users</option>
                    <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                </Select>
                <Select v-model="action" class="sm:w-40">
                    <option value="">All actions</option>
                    <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                </Select>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>When</TableHead>
                        <TableHead>User</TableHead>
                        <TableHead>Action</TableHead>
                        <TableHead>Record</TableHead>
                        <TableHead>IP Address</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="logs.data.length === 0" :colspan="5">No activity recorded yet.</TableEmpty>
                    <TableRow v-for="log in logs.data" :key="log.id">
                        <TableCell class="whitespace-nowrap text-muted-foreground">{{ log.created_at }}</TableCell>
                        <TableCell>{{ log.user?.name ?? 'System' }}</TableCell>
                        <TableCell>
                            <Badge :variant="actionVariant[log.action] ?? 'secondary'">{{ log.action }}</Badge>
                        </TableCell>
                        <TableCell>{{ modelName(log.auditable_type) }} #{{ log.auditable_id }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ log.ip_address ?? '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination :from="logs.from" :to="logs.to" :total="logs.total" :links="logs.links" />
        </div>
    </AppLayout>
</template>
