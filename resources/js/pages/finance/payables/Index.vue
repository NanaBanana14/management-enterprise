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

interface PayableRow {
    id: number;
    number: string;
    supplier: string;
    date: string;
    due_date: string;
    amount: number;
    status: string;
}

interface RefOption {
    id: number;
    name?: string;
    code?: string;
}

const props = defineProps<{
    payables: PayableRow[];
    suppliers: RefOption[];
    expenseAccounts: RefOption[];
    cashBankAccounts: RefOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payables (AP)', href: '/finance/payables' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('payable.manage');

const supplierOptions = props.suppliers.map((s) => ({ value: s.id, label: s.name as string }));
const expenseOptions = props.expenseAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));
const cashBankOptions = props.cashBankAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const today = new Date().toISOString().slice(0, 10);

const showCreate = ref(false);
const form = useForm({ supplier_id: '' as number | '', expense_account_id: '' as number | '', amount: 0, date: today, due_date: today, description: '' });
function openCreate() {
    form.reset();
    form.date = today;
    form.due_date = today;
    form.clearErrors();
    showCreate.value = true;
}
function submit() {
    form.post(route('finance.payables.store'), { onSuccess: () => (showCreate.value = false) });
}

const showPay = ref(false);
const paying = ref<PayableRow | null>(null);
const payForm = useForm({ cash_bank_account_id: '' as number | '' });
function openPay(payable: PayableRow) {
    paying.value = payable;
    payForm.reset();
    payForm.clearErrors();
    showPay.value = true;
}
function submitPay() {
    if (!paying.value) return;
    payForm.post(route('finance.payables.markPaid', paying.value.id), { onSuccess: () => (showPay.value = false) });
}
</script>

<template>
    <Head title="Payables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Payables (Accounts Payable)" description="Supplier bills and payment status.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        New Payable
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Number</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Due</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canManage" class="w-32" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="payables.length === 0" :colspan="7">No payables yet.</TableEmpty>
                    <TableRow v-for="payable in payables" :key="payable.id">
                        <TableCell class="font-medium">{{ payable.number }}</TableCell>
                        <TableCell>{{ payable.supplier }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ payable.date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ payable.due_date }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(payable.amount) }}</TableCell>
                        <TableCell>
                            <Badge :variant="payable.status === 'paid' ? 'success' : 'warning'">{{ payable.status }}</Badge>
                        </TableCell>
                        <TableCell v-if="canManage">
                            <Button v-if="payable.status === 'unpaid'" variant="outline" size="sm" @click="openPay(payable)">Mark Paid</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreate" @update:open="(v) => (showCreate = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Payable</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label>Supplier</Label>
                        <SearchableSelect v-model="form.supplier_id" placeholder="Select supplier" :options="supplierOptions" />
                        <InputError :message="form.errors.supplier_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Expense account</Label>
                        <SearchableSelect v-model="form.expense_account_id" placeholder="Select account" :options="expenseOptions" />
                        <InputError :message="form.errors.expense_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="pay-amount">Amount</Label>
                            <Input id="pay-amount" v-model.number="form.amount" type="number" min="0" step="0.01" />
                            <InputError :message="form.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="pay-date">Date</Label>
                            <Input id="pay-date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="pay-due-date">Due date</Label>
                        <Input id="pay-due-date" v-model="form.due_date" type="date" />
                        <InputError :message="form.errors.due_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pay-description">Description</Label>
                        <Input id="pay-description" v-model="form.description" placeholder="Optional" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showCreate = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showPay" @update:open="(v) => (showPay = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Mark Payable Paid</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitPay">
                    <p class="text-sm text-muted-foreground">
                        Paying <strong>{{ paying ? formatCurrency(paying.amount) : '' }}</strong> for {{ paying?.number }}.
                    </p>
                    <div class="grid gap-2">
                        <Label>Pay from</Label>
                        <SearchableSelect v-model="payForm.cash_bank_account_id" placeholder="Select account" :options="cashBankOptions" />
                        <InputError :message="payForm.errors.cash_bank_account_id" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showPay = false">Cancel</Button>
                        <Button type="submit" :disabled="payForm.processing">
                            <LoaderCircle v-if="payForm.processing" class="size-4 animate-spin" />
                            Confirm
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
