<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Package, Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface ProductRow {
    id: number;
    sku: string;
    name: string;
    unit: string;
    price: number;
    stock: number;
    is_active: boolean;
}

defineProps<{ products: ProductRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/erp/products' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('product.manage');

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const showDialog = ref(false);
const editing = ref<ProductRow | null>(null);
const form = useForm({ sku: '', name: '', unit: 'pcs', price: 0, is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(product: ProductRow) {
    editing.value = product;
    form.sku = product.sku;
    form.name = product.name;
    form.unit = product.unit;
    form.price = product.price;
    form.is_active = product.is_active;
    form.clearErrors();
    showDialog.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('erp.products.update', editing.value.id), { onSuccess: () => (showDialog.value = false) });
    } else {
        form.post(route('erp.products.store'), { onSuccess: () => (showDialog.value = false) });
    }
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Products" description="Products and services tracked across warehouses.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        Add Product
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>SKU</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Unit</TableHead>
                        <TableHead class="text-right">Price</TableHead>
                        <TableHead class="text-right">Stock</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canManage" class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="products.length === 0"
                        :colspan="7"
                        :icon="Package"
                        title="No products yet"
                        description="Add your first product to start tracking inventory."
                    >
                        <Button v-if="canManage" size="sm" @click="openCreate">
                            <Plus class="size-4" />
                            Add Product
                        </Button>
                    </TableEmpty>
                    <TableRow v-for="product in products" :key="product.id">
                        <TableCell class="font-medium">{{ product.sku }}</TableCell>
                        <TableCell>{{ product.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ product.unit }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(product.price) }}</TableCell>
                        <TableCell class="text-right font-medium">{{ product.stock }}</TableCell>
                        <TableCell>
                            <Badge :variant="product.is_active ? 'success' : 'outline'">{{ product.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </TableCell>
                        <TableCell v-if="canManage">
                            <Button variant="ghost" size="icon" @click="openEdit(product)">
                                <Pencil class="size-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Edit Product' : 'New Product' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="sku">SKU</Label>
                            <Input id="sku" v-model="form.sku" placeholder="e.g. SKU-001" />
                            <InputError :message="form.errors.sku" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="unit">Unit</Label>
                            <Input id="unit" v-model="form.unit" placeholder="pcs" />
                            <InputError :message="form.errors.unit" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Office Chair" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="price">Price</Label>
                        <Input id="price" v-model.number="form.price" type="number" min="0" step="0.01" />
                        <InputError :message="form.errors.price" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            {{ editing ? 'Save' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
