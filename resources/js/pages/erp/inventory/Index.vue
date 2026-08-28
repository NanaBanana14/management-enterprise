<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, LoaderCircle, PackagePlus } from 'lucide-vue-next';
import { ref } from 'vue';

interface StockRow {
    id: number;
    product: string;
    sku: string;
    unit: string;
    warehouse: string;
    quantity: number;
}

interface Option {
    id: number;
    name?: string;
    sku?: string;
}

const props = defineProps<{ stocks: StockRow[]; products: Option[]; warehouses: Option[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/erp/inventory' },
];

const page = usePage<SharedData>();
const canAdjust = page.props.auth.permissions.includes('inventory.adjust');
const canTransfer = page.props.auth.permissions.includes('inventory.transfer');

const productOptions = props.products.map((p) => ({ value: p.id, label: `${p.sku} — ${p.name}` }));
const warehouseOptions = props.warehouses.map((w) => ({ value: w.id, label: w.name as string }));

const showAdjust = ref(false);
const adjustForm = useForm({ product_id: '' as number | '', warehouse_id: '' as number | '', type: 'in', quantity: 0, note: '' });

function openAdjust() {
    adjustForm.reset();
    adjustForm.clearErrors();
    showAdjust.value = true;
}

function submitAdjust() {
    adjustForm.post(route('erp.inventory.adjust'), { onSuccess: () => (showAdjust.value = false) });
}

const showTransfer = ref(false);
const transferForm = useForm({ product_id: '' as number | '', from_warehouse_id: '' as number | '', to_warehouse_id: '' as number | '', quantity: 0, note: '' });

function openTransfer() {
    transferForm.reset();
    transferForm.clearErrors();
    showTransfer.value = true;
}

function submitTransfer() {
    transferForm.post(route('erp.inventory.transfer'), { onSuccess: () => (showTransfer.value = false) });
}
</script>

<template>
    <Head title="Inventory" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Inventory" description="Live stock levels per warehouse.">
                <template #actions>
                    <div class="flex gap-2">
                        <Button v-if="canTransfer" variant="outline" @click="openTransfer">
                            <ArrowLeftRight class="size-4" />
                            Transfer
                        </Button>
                        <Button v-if="canAdjust" @click="openAdjust">
                            <PackagePlus class="size-4" />
                            Adjust Stock
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>SKU</TableHead>
                        <TableHead>Product</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead class="text-right">Quantity</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="stocks.length === 0" :colspan="4">No stock on hand yet.</TableEmpty>
                    <TableRow v-for="stock in stocks" :key="stock.id">
                        <TableCell class="text-muted-foreground">{{ stock.sku }}</TableCell>
                        <TableCell class="font-medium">{{ stock.product }}</TableCell>
                        <TableCell>{{ stock.warehouse }}</TableCell>
                        <TableCell class="text-right font-medium">{{ stock.quantity }} {{ stock.unit }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showAdjust" @update:open="(v) => (showAdjust = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Adjust Stock</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitAdjust">
                    <div class="grid gap-2">
                        <Label>Product</Label>
                        <SearchableSelect v-model="adjustForm.product_id" placeholder="Select product" :options="productOptions" />
                        <InputError :message="adjustForm.errors.product_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Warehouse</Label>
                        <SearchableSelect v-model="adjustForm.warehouse_id" placeholder="Select warehouse" :options="warehouseOptions" />
                        <InputError :message="adjustForm.errors.warehouse_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Type</Label>
                            <SearchableSelect
                                v-model="adjustForm.type"
                                :options="[
                                    { value: 'in', label: 'Stock In' },
                                    { value: 'out', label: 'Stock Out' },
                                ]"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="adjust-quantity">Quantity</Label>
                            <Input id="adjust-quantity" v-model.number="adjustForm.quantity" type="number" min="0" step="0.01" />
                            <InputError :message="adjustForm.errors.quantity" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="adjust-note">Note</Label>
                        <Input id="adjust-note" v-model="adjustForm.note" placeholder="Optional" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showAdjust = false">Cancel</Button>
                        <Button type="submit" :disabled="adjustForm.processing">
                            <LoaderCircle v-if="adjustForm.processing" class="size-4 animate-spin" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showTransfer" @update:open="(v) => (showTransfer = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Transfer Stock</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitTransfer">
                    <div class="grid gap-2">
                        <Label>Product</Label>
                        <SearchableSelect v-model="transferForm.product_id" placeholder="Select product" :options="productOptions" />
                        <InputError :message="transferForm.errors.product_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>From warehouse</Label>
                            <SearchableSelect v-model="transferForm.from_warehouse_id" placeholder="Source" :options="warehouseOptions" />
                            <InputError :message="transferForm.errors.from_warehouse_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label>To warehouse</Label>
                            <SearchableSelect v-model="transferForm.to_warehouse_id" placeholder="Destination" :options="warehouseOptions" />
                            <InputError :message="transferForm.errors.to_warehouse_id" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="transfer-quantity">Quantity</Label>
                        <Input id="transfer-quantity" v-model.number="transferForm.quantity" type="number" min="0" step="0.01" />
                        <InputError :message="transferForm.errors.quantity" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="transfer-note">Note</Label>
                        <Input id="transfer-note" v-model="transferForm.note" placeholder="Optional" />
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
