<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, LoaderCircle, MinusCircle, PlusCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface AccountRow {
    id: number;
    code: string;
    name: string;
    balance: number;
}

interface TransactionRow {
    id: number;
    date: string;
    reference: string;
    description: string | null;
    account: string;
    debit: number;
    credit: number;
}

interface RefAccount {
    id: number;
    code: string;
    name: string;
}

const props = defineProps<{
    accounts: AccountRow[];
    transactions: TransactionRow[];
    revenueAccounts: RefAccount[];
    expenseAccounts: RefAccount[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cash & Bank', href: '/finance/cashbank' },
];

const page = usePage<SharedData>();
const canRecordIncome = page.props.auth.permissions.includes('income.manage');
const canRecordExpense = page.props.auth.permissions.includes('expense.manage');
const canTransfer = page.props.auth.permissions.includes('cashbank.manage');

const cashBankOptions = props.accounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));
const revenueOptions = props.revenueAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));
const expenseOptions = props.expenseAccounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const today = new Date().toISOString().slice(0, 10);

const showIncome = ref(false);
const incomeForm = useForm({ account_id: '' as number | '', revenue_account_id: '' as number | '', amount: 0, date: today, description: '' });
function openIncome() {
    incomeForm.reset();
    incomeForm.date = today;
    incomeForm.clearErrors();
    showIncome.value = true;
}
function submitIncome() {
    incomeForm.post(route('finance.cashbank.income'), { onSuccess: () => (showIncome.value = false) });
}

const showExpense = ref(false);
const expenseForm = useForm({ account_id: '' as number | '', expense_account_id: '' as number | '', amount: 0, date: today, description: '' });
function openExpense() {
    expenseForm.reset();
    expenseForm.date = today;
    expenseForm.clearErrors();
    showExpense.value = true;
}
function submitExpense() {
    expenseForm.post(route('finance.cashbank.expense'), { onSuccess: () => (showExpense.value = false) });
}

const showTransfer = ref(false);
const transferForm = useForm({ from_account_id: '' as number | '', to_account_id: '' as number | '', amount: 0, date: today, description: '' });
function openTransfer() {
    transferForm.reset();
    transferForm.date = today;
    transferForm.clearErrors();
    showTransfer.value = true;
}
function submitTransfer() {
    transferForm.post(route('finance.cashbank.transfer'), { onSuccess: () => (showTransfer.value = false) });
}
</script>

<template>
    <Head title="Cash & Bank" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Cash & Bank" description="Cash and bank balances with income, expense, and transfer entries.">
                <template #actions>
                    <div class="flex gap-2">
                        <Button v-if="canTransfer" variant="outline" @click="openTransfer">
                            <ArrowLeftRight class="size-4" />
                            Transfer
                        </Button>
                        <Button v-if="canRecordExpense" variant="outline" @click="openExpense">
                            <MinusCircle class="size-4" />
                            Expense
                        </Button>
                        <Button v-if="canRecordIncome" @click="openIncome">
                            <PlusCircle class="size-4" />
                            Income
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="account in accounts" :key="account.id">
                    <CardHeader>
                        <CardTitle class="text-base">{{ account.name }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">{{ account.code }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ formatCurrency(account.balance) }}</p>
                    </CardContent>
                </Card>
            </div>

            <div>
                <h2 class="mb-2 text-sm font-medium text-muted-foreground">Recent Transactions</h2>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Reference</TableHead>
                            <TableHead>Account</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="text-right">Debit</TableHead>
                            <TableHead class="text-right">Credit</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="transactions.length === 0" :colspan="6">No transactions yet.</TableEmpty>
                        <TableRow v-for="tx in transactions" :key="tx.id">
                            <TableCell class="text-muted-foreground">{{ tx.date }}</TableCell>
                            <TableCell class="font-mono text-xs">{{ tx.reference }}</TableCell>
                            <TableCell>{{ tx.account }}</TableCell>
                            <TableCell class="text-muted-foreground">{{ tx.description ?? '—' }}</TableCell>
                            <TableCell class="text-right">{{ tx.debit > 0 ? formatCurrency(tx.debit) : '—' }}</TableCell>
                            <TableCell class="text-right">{{ tx.credit > 0 ? formatCurrency(tx.credit) : '—' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <Dialog :open="showIncome" @update:open="(v) => (showIncome = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Record Income</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitIncome">
                    <div class="grid gap-2">
                        <Label>Cash / Bank account</Label>
                        <SearchableSelect v-model="incomeForm.account_id" placeholder="Select account" :options="cashBankOptions" />
                        <InputError :message="incomeForm.errors.account_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Revenue account</Label>
                        <SearchableSelect v-model="incomeForm.revenue_account_id" placeholder="Select account" :options="revenueOptions" />
                        <InputError :message="incomeForm.errors.revenue_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="income-amount">Amount</Label>
                            <Input id="income-amount" v-model.number="incomeForm.amount" type="number" min="0" step="0.01" />
                            <InputError :message="incomeForm.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="income-date">Date</Label>
                            <Input id="income-date" v-model="incomeForm.date" type="date" />
                            <InputError :message="incomeForm.errors.date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="income-description">Description</Label>
                        <Input id="income-description" v-model="incomeForm.description" placeholder="Optional" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showIncome = false">Cancel</Button>
                        <Button type="submit" :disabled="incomeForm.processing">
                            <LoaderCircle v-if="incomeForm.processing" class="size-4 animate-spin" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showExpense" @update:open="(v) => (showExpense = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Record Expense</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitExpense">
                    <div class="grid gap-2">
                        <Label>Cash / Bank account</Label>
                        <SearchableSelect v-model="expenseForm.account_id" placeholder="Select account" :options="cashBankOptions" />
                        <InputError :message="expenseForm.errors.account_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Expense account</Label>
                        <SearchableSelect v-model="expenseForm.expense_account_id" placeholder="Select account" :options="expenseOptions" />
                        <InputError :message="expenseForm.errors.expense_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="expense-amount">Amount</Label>
                            <Input id="expense-amount" v-model.number="expenseForm.amount" type="number" min="0" step="0.01" />
                            <InputError :message="expenseForm.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="expense-date">Date</Label>
                            <Input id="expense-date" v-model="expenseForm.date" type="date" />
                            <InputError :message="expenseForm.errors.date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="expense-description">Description</Label>
                        <Input id="expense-description" v-model="expenseForm.description" placeholder="Optional" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showExpense = false">Cancel</Button>
                        <Button type="submit" :disabled="expenseForm.processing">
                            <LoaderCircle v-if="expenseForm.processing" class="size-4 animate-spin" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showTransfer" @update:open="(v) => (showTransfer = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Transfer Between Accounts</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitTransfer">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>From</Label>
                            <SearchableSelect v-model="transferForm.from_account_id" placeholder="Source" :options="cashBankOptions" />
                            <InputError :message="transferForm.errors.from_account_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label>To</Label>
                            <SearchableSelect v-model="transferForm.to_account_id" placeholder="Destination" :options="cashBankOptions" />
                            <InputError :message="transferForm.errors.to_account_id" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="transfer-amount">Amount</Label>
                            <Input id="transfer-amount" v-model.number="transferForm.amount" type="number" min="0" step="0.01" />
                            <InputError :message="transferForm.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="transfer-date">Date</Label>
                            <Input id="transfer-date" v-model="transferForm.date" type="date" />
                            <InputError :message="transferForm.errors.date" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="transfer-description">Description</Label>
                        <Input id="transfer-description" v-model="transferForm.description" placeholder="Optional" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showTransfer = false">Cancel</Button>
                        <Button type="submit" :disabled="transferForm.processing">
                            <LoaderCircle v-if="transferForm.processing" class="size-4 animate-spin" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
