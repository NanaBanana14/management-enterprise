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
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OrderRow {
    id: number;
    number: string;
    supplier: string;
    warehouse: string;
    date: string;
    status: string;
    total: number;
}

interface RefOption {
    id: number;
    name?: string;
    sku?: string;
    price?: number;
}

const props = defineProps<{
    orders: OrderRow[];
    suppliers: RefOption[];
    warehouses: RefOption[];
    products: RefOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Orders', href: '/erp/purchase-orders' },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('purchase.create');
const canApprove = page.props.auth.permissions.includes('purchase.approve');

const supplierOptions = props.suppliers.map((s) => ({ value: s.id, label: s.name as string }));
const warehouseOptions = props.warehouses.map((w) => ({ value: w.id, label: w.name as string }));
const productOptions = props.products.map((p) => ({ value: p.id, label: `${p.sku} — ${p.name}` }));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const today = new Date().toISOString().slice(0, 10);

const showCreate = ref(false);
const form = useForm({
    supplier_id: '' as number | '',
    warehouse_id: '' as number | '',
    date: today,
    notes: '',
    lines: [{ product_id: '' as number | '', quantity: 1, unit_price: 0 }],
});

function addLine() {
    form.lines.push({ product_id: '', quantity: 1, unit_price: 0 });
}

function removeLine(index: number) {
    form.lines.splice(index, 1);
}

function onProductChange(index: number) {
    const product = props.products.find((p) => p.id === form.lines[index].product_id);
    if (product) {
        form.lines[index].unit_price = product.price ?? 0;
    }
}

const total = computed(() => form.lines.reduce((sum, l) => sum + (Number(l.quantity) || 0) * (Number(l.unit_price) || 0), 0));

function openCreate() {
    form.reset();
    form.date = today;
    form.lines = [{ product_id: '', quantity: 1, unit_price: 0 }];
    form.clearErrors();
    showCreate.value = true;
}

function submit() {
    form.post(route('erp.purchase-orders.store'), { onSuccess: () => (showCreate.value = false) });
}

const receiveForm = useForm({});
function receive(order: OrderRow) {
    receiveForm.post(route('erp.purchase-orders.receive', order.id));
}
</script>

<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Purchase Orders" description="Orders placed with suppliers to restock inventory.">
                <template #actions>
                    <Button v-if="canCreate" @click="openCreate">
                        <Plus class="size-4" />
                        New Purchase Order
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Number</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canApprove" class="w-28" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="orders.length === 0" :colspan="7">No purchase orders yet.</TableEmpty>
                    <TableRow v-for="order in orders" :key="order.id">
                        <TableCell class="font-medium">{{ order.number }}</TableCell>
                        <TableCell>{{ order.supplier }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ order.warehouse }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ order.date }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(order.total) }}</TableCell>
                        <TableCell>
                            <Badge :variant="order.status === 'received' ? 'success' : 'warning'">{{ order.status }}</Badge>
                        </TableCell>
                        <TableCell v-if="canApprove">
                            <Button v-if="order.status === 'draft'" variant="outline" size="sm" @click="receive(order)">Receive</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreate" @update:open="(v) => (showCreate = v)">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>New Purchase Order</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Supplier</Label>
                            <SearchableSelect v-model="form.supplier_id" placeholder="Select supplier" :options="supplierOptions" />
                            <InputError :message="form.errors.supplier_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Warehouse</Label>
                            <SearchableSelect v-model="form.warehouse_id" placeholder="Select warehouse" :options="warehouseOptions" />
                            <InputError :message="form.errors.warehouse_id" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="po-date">Date</Label>
                        <Input id="po-date" v-model="form.date" type="date" />
                        <InputError :message="form.errors.date" />
                    </div>

                    <div class="space-y-2">
                        <Label>Lines</Label>
                        <div v-for="(line, index) in form.lines" :key="index" class="grid grid-cols-[1fr_80px_120px_28px] items-start gap-2">
                            <SearchableSelect
                                v-model="line.product_id"
                                placeholder="Product"
                                :options="productOptions"
                                @update:model-value="onProductChange(index)"
                            />
                            <Input v-model.number="line.quantity" type="number" min="0" step="0.01" placeholder="Qty" />
                            <Input v-model.number="line.unit_price" type="number" min="0" step="0.01" placeholder="Unit price" />
                            <Button type="button" variant="ghost" size="icon" :disabled="form.lines.length === 1" @click="removeLine(index)">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="addLine">
                            <Plus class="size-4" />
                            Add line
                        </Button>
                        <InputError :message="form.errors.lines" />
                    </div>

                    <div class="flex justify-between border-t pt-3 text-sm font-medium">
                        <span>Total</span>
                        <span>{{ formatCurrency(total) }}</span>
                    </div>

                    <div class="grid gap-2">
                        <Label for="po-notes">Notes</Label>
                        <Input id="po-notes" v-model="form.notes" placeholder="Optional" />
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
    </AppLayout>
</template>
