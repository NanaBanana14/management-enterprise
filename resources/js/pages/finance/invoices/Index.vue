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

interface InvoiceRow {
    id: number;
    number: string;
    customer: string;
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
    invoices: InvoiceRow[];
    customers: RefOption[];
    revenueAccounts: RefOption[];
    cashBankAccounts: RefOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Invoices (AR)', href: '/finance/invoices' },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('invoice.create');
const canApprove = page.props.auth.permissions.includes('invoice.approve');

const customerOptions = props.customers.map((c) => ({ value: c.id, label: c.name as string }));
const revenueOptions = props.revenueAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));
const cashBankOptions = props.cashBankAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const today = new Date().toISOString().slice(0, 10);

const showCreate = ref(false);
const form = useForm({ customer_id: '' as number | '', revenue_account_id: '' as number | '', amount: 0, date: today, due_date: today, description: '' });
function openCreate() {
    form.reset();
    form.date = today;
    form.due_date = today;
    form.clearErrors();
    showCreate.value = true;
}
function submit() {
    form.post(route('finance.invoices.store'), { onSuccess: () => (showCreate.value = false) });
}

const showPay = ref(false);
const paying = ref<InvoiceRow | null>(null);
const payForm = useForm({ cash_bank_account_id: '' as number | '' });
function openPay(invoice: InvoiceRow) {
    paying.value = invoice;
    payForm.reset();
    payForm.clearErrors();
    showPay.value = true;
}
function submitPay() {
    if (!paying.value) return;
    payForm.post(route('finance.invoices.markPaid', paying.value.id), { onSuccess: () => (showPay.value = false) });
}
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Invoices (Accounts Receivable)" description="Customer invoices and payment status.">
                <template #actions>
                    <Button v-if="canCreate" @click="openCreate">
                        <Plus class="size-4" />
                        New Invoice
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Number</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Due</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canApprove" class="w-32" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="invoices.length === 0" :colspan="7">No invoices yet.</TableEmpty>
                    <TableRow v-for="invoice in invoices" :key="invoice.id">
                        <TableCell class="font-medium">{{ invoice.number }}</TableCell>
                        <TableCell>{{ invoice.customer }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ invoice.date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ invoice.due_date }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(invoice.amount) }}</TableCell>
                        <TableCell>
                            <Badge :variant="invoice.status === 'paid' ? 'success' : 'warning'">{{ invoice.status }}</Badge>
                        </TableCell>
                        <TableCell v-if="canApprove">
                            <Button v-if="invoice.status === 'unpaid'" variant="outline" size="sm" @click="openPay(invoice)">Mark Paid</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreate" @update:open="(v) => (showCreate = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Invoice</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label>Customer</Label>
                        <SearchableSelect v-model="form.customer_id" placeholder="Select customer" :options="customerOptions" />
                        <InputError :message="form.errors.customer_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Revenue account</Label>
                        <SearchableSelect v-model="form.revenue_account_id" placeholder="Select account" :options="revenueOptions" />
                        <InputError :message="form.errors.revenue_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="inv-amount">Amount</Label>
                            <Input id="inv-amount" v-model.number="form.amount" type="number" min="0" step="0.01" />
                            <InputError :message="form.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="inv-date">Date</Label>
                            <Input id="inv-date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="inv-due-date">Due date</Label>
                        <Input id="inv-due-date" v-model="form.due_date" type="date" />
                        <InputError :message="form.errors.due_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="inv-description">Description</Label>
                        <Input id="inv-description" v-model="form.description" placeholder="Optional" />
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
                    <DialogTitle>Mark Invoice Paid</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitPay">
                    <p class="text-sm text-muted-foreground">
                        Receiving payment of <strong>{{ paying ? formatCurrency(paying.amount) : '' }}</strong> for {{ paying?.number }}.
                    </p>
                    <div class="grid gap-2">
                        <Label>Deposit into</Label>
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
