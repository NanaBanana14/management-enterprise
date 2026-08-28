<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface AccountRow {
    id: number;
    code: string;
    name: string;
    type: string;
    parent: string | null;
    balance: number;
    is_active: boolean;
}

const props = defineProps<{ accounts: AccountRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Chart of Accounts', href: '/finance/accounts' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('account.manage');

const typeVariant: Record<string, 'secondary' | 'outline' | 'warning' | 'success'> = {
    asset: 'success',
    liability: 'warning',
    equity: 'outline',
    revenue: 'secondary',
    expense: 'secondary',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const showDialog = ref(false);
const form = useForm({
    code: '',
    name: '',
    type: 'asset',
    parent_id: '' as number | '',
});

function submit() {
    form.post(route('finance.accounts.store'), { onSuccess: () => (showDialog.value = false) });
}
</script>

<template>
    <Head title="Chart of Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Chart of Accounts" description="Ledger accounts used across journal entries and reports.">
                <template #actions>
                    <Button v-if="canManage" @click="showDialog = true">
                        <Plus class="size-4" />
                        Add Account
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Parent</TableHead>
                        <TableHead class="text-right">Balance</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="accounts.length === 0" :colspan="5">No accounts defined yet.</TableEmpty>
                    <TableRow v-for="account in accounts" :key="account.id">
                        <TableCell class="font-medium">{{ account.code }}</TableCell>
                        <TableCell>{{ account.name }}</TableCell>
                        <TableCell>
                            <Badge :variant="typeVariant[account.type] ?? 'outline'">{{ account.type }}</Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ account.parent ?? '—' }}</TableCell>
                        <TableCell class="text-right font-medium">{{ formatCurrency(account.balance) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Account</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="code">Code</Label>
                            <Input id="code" v-model="form.code" placeholder="e.g. 1500" />
                            <InputError :message="form.errors.code" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <SearchableSelect
                                v-model="form.type"
                                :options="[
                                    { value: 'asset', label: 'Asset' },
                                    { value: 'liability', label: 'Liability' },
                                    { value: 'equity', label: 'Equity' },
                                    { value: 'revenue', label: 'Revenue' },
                                    { value: 'expense', label: 'Expense' },
                                ]"
                            />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Petty Cash" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="parent_id">Parent account (optional)</Label>
                        <SearchableSelect
                            v-model="form.parent_id"
                            placeholder="No parent"
                            :options="[{ value: '', label: 'No parent' }, ...props.accounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }))]"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
