<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface PayslipItem {
    id: number;
    type: string;
    label: string;
    amount: number;
}

const props = defineProps<{
    payslip: {
        id: number;
        basic_salary: number;
        overtime_hours: number;
        overtime_amount: number;
        allowance_total: number;
        bonus_total: number;
        deduction_total: number;
        net_salary: number;
        status: string;
        employee: { id: number; name: string; employee_number: string };
        period: { id: number; name: string };
        items: PayslipItem[];
    };
    canEdit: boolean;
    canApprove: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payroll', href: '/hris/payroll/periods' },
    { title: props.payslip.period.name, href: `/hris/payroll/periods/${props.payslip.period.id}` },
    { title: props.payslip.employee.name, href: `/hris/payroll/payslips/${props.payslip.id}` },
];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const statusVariant: Record<string, 'success' | 'warning' | 'outline'> = {
    draft: 'warning',
    approved: 'success',
    paid: 'outline',
};

const typeLabel: Record<string, string> = { allowance: 'Allowance', bonus: 'Bonus', deduction: 'Deduction' };

const showItemDialog = ref(false);
const itemForm = useForm({
    type: 'allowance',
    label: '',
    amount: '',
});

function submitItem() {
    itemForm.post(route('hris.payroll.payslips.items.store', props.payslip.id), {
        onSuccess: () => {
            showItemDialog.value = false;
            itemForm.reset();
        },
    });
}

const removeTarget = ref<PayslipItem | null>(null);
function removeItem() {
    if (!removeTarget.value) return;
    router.delete(route('hris.payroll.payslips.items.destroy', [props.payslip.id, removeTarget.value.id]), {
        onSuccess: () => (removeTarget.value = null),
    });
}

function approve() {
    router.post(route('hris.payroll.payslips.approve', props.payslip.id));
}
</script>

<template>
    <Head :title="payslip.employee.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ payslip.employee.name }}</h1>
                        <Badge :variant="statusVariant[payslip.status] ?? 'outline'">{{ payslip.status }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ payslip.employee.employee_number }} · {{ payslip.period.name }}</p>
                </div>
                <Button v-if="canApprove" @click="approve">
                    <Check class="size-4" />
                    Approve Payslip
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Earnings</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted-foreground">Basic salary</span><span>{{ formatCurrency(payslip.basic_salary) }}</span></div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Overtime ({{ payslip.overtime_hours }}h)</span>
                            <span>{{ formatCurrency(payslip.overtime_amount) }}</span>
                        </div>
                        <div v-for="item in payslip.items.filter((i) => i.type !== 'deduction')" :key="item.id" class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ item.label }} <span class="text-xs">({{ typeLabel[item.type] }})</span></span>
                            <div class="flex items-center gap-2">
                                <span>{{ formatCurrency(item.amount) }}</span>
                                <button v-if="canEdit" type="button" @click="removeTarget = item">
                                    <Trash2 class="size-3.5 text-destructive" />
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Deductions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div v-for="item in payslip.items.filter((i) => i.type === 'deduction')" :key="item.id" class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ item.label }}</span>
                            <div class="flex items-center gap-2">
                                <span>-{{ formatCurrency(item.amount) }}</span>
                                <button v-if="canEdit" type="button" @click="removeTarget = item">
                                    <Trash2 class="size-3.5 text-destructive" />
                                </button>
                            </div>
                        </div>
                        <p v-if="payslip.items.filter((i) => i.type === 'deduction').length === 0" class="text-muted-foreground">No deductions.</p>
                    </CardContent>
                </Card>
            </div>

            <Button v-if="canEdit" variant="outline" class="w-fit" @click="showItemDialog = true">
                <Plus class="size-4" />
                Add Line Item
            </Button>

            <Card class="max-w-sm">
                <CardContent class="pt-6">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Net Salary</span>
                        <span>{{ formatCurrency(payslip.net_salary) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="showItemDialog" @update:open="(v) => (showItemDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Line Item</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitItem">
                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <Select id="type" v-model="itemForm.type">
                            <option value="allowance">Allowance</option>
                            <option value="bonus">Bonus</option>
                            <option value="deduction">Deduction</option>
                        </Select>
                        <InputError :message="itemForm.errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="label">Label</Label>
                        <Input id="label" v-model="itemForm.label" placeholder="e.g. Transport Allowance" />
                        <InputError :message="itemForm.errors.label" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="amount">Amount (IDR)</Label>
                        <Input id="amount" v-model="itemForm.amount" type="number" min="0" step="1000" />
                        <InputError :message="itemForm.errors.amount" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showItemDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="itemForm.processing">
                            <LoaderCircle v-if="itemForm.processing" class="size-4 animate-spin" />
                            Add
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="removeTarget !== null"
            title="Remove line item"
            :description="removeTarget ? `Remove '${removeTarget.label}' from this payslip?` : ''"
            confirm-label="Remove"
            @update:open="(v) => !v && (removeTarget = null)"
            @confirm="removeItem"
        />
    </AppLayout>
</template>
