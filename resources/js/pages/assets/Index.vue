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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus, TrendingDown } from 'lucide-vue-next';
import { ref } from 'vue';

interface AssetRow {
    id: number;
    code: string;
    name: string;
    category: string;
    custody: string;
    acquisition_cost: number;
    book_value: number;
    status: string;
}

interface RefOption {
    id: number;
    name: string;
}

const props = defineProps<{
    assets: AssetRow[];
    warehouses: RefOption[];
    employees: RefOption[];
    categories: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fixed Assets', href: '/assets' },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('asset.create');
const canManage = page.props.auth.permissions.includes('asset.manage');

const warehouseOptions = props.warehouses.map((w) => ({ value: w.id, label: w.name }));
const employeeOptions = props.employees.map((e) => ({ value: e.id, label: e.name }));
const categoryOptions = props.categories.map((c) => ({ value: c.value, label: c.label }));

const statusBadge: Record<string, 'success' | 'outline'> = {
    active: 'success',
    disposed: 'outline',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const showCreate = ref(false);
const custodyType = ref<'warehouse' | 'employee'>('warehouse');

const form = useForm({
    name: '',
    category: '',
    description: '',
    warehouse_id: '' as number | '',
    employee_id: '' as number | '',
    acquisition_date: new Date().toISOString().slice(0, 10),
    acquisition_cost: 0,
    salvage_value: 0,
    useful_life_months: 36,
});

function openCreate() {
    form.reset();
    form.clearErrors();
    custodyType.value = 'warehouse';
    showCreate.value = true;
}

function submit() {
    if (custodyType.value === 'warehouse') {
        form.employee_id = '';
    } else {
        form.warehouse_id = '';
    }

    form.post(route('assets.store'), { onSuccess: () => (showCreate.value = false) });
}

const showDepreciation = ref(false);
const depreciationForm = useForm({ period: new Date().toISOString().slice(0, 7) });

function runDepreciation() {
    depreciationForm.post(route('assets.depreciation.run'), { onSuccess: () => (showDepreciation.value = false) });
}
</script>

<template>
    <Head title="Fixed Assets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Fixed Assets" description="Register, depreciate, and dispose of company assets.">
                <template #actions>
                    <Button v-if="canManage" variant="outline" @click="showDepreciation = true">
                        <TrendingDown class="size-4" />
                        Run Depreciation
                    </Button>
                    <Button v-if="canCreate" @click="openCreate">
                        <Plus class="size-4" />
                        Register Asset
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Custody</TableHead>
                        <TableHead class="text-right">Cost</TableHead>
                        <TableHead class="text-right">Book Value</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="assets.length === 0" :colspan="7">No assets registered yet.</TableEmpty>
                    <TableRow
                        v-for="asset in assets"
                        :key="asset.id"
                        class="cursor-pointer"
                        @click="router.visit(route('assets.show', asset.id))"
                    >
                        <TableCell class="font-mono text-xs">{{ asset.code }}</TableCell>
                        <TableCell class="font-medium">{{ asset.name }}</TableCell>
                        <TableCell class="capitalize">{{ asset.category }}</TableCell>
                        <TableCell>{{ asset.custody }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(asset.acquisition_cost) }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(asset.book_value) }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusBadge[asset.status] ?? 'outline'">{{ asset.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreate" @update:open="(v) => (showCreate = v)">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Register Asset</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="asset-name">Name</Label>
                        <Input id="asset-name" v-model="form.name" placeholder="e.g. Dell Latitude Laptop" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Category</Label>
                            <SearchableSelect v-model="form.category" placeholder="Select category" :options="categoryOptions" />
                            <InputError :message="form.errors.category" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="asset-date">Acquisition date</Label>
                            <Input id="asset-date" v-model="form.acquisition_date" type="date" />
                            <InputError :message="form.errors.acquisition_date" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="asset-cost">Acquisition cost</Label>
                            <Input id="asset-cost" v-model.number="form.acquisition_cost" type="number" min="0" step="1" />
                            <InputError :message="form.errors.acquisition_cost" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="asset-salvage">Salvage value</Label>
                            <Input id="asset-salvage" v-model.number="form.salvage_value" type="number" min="0" step="1" />
                            <InputError :message="form.errors.salvage_value" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="asset-life">Useful life (months)</Label>
                            <Input id="asset-life" v-model.number="form.useful_life_months" type="number" min="1" step="1" />
                            <InputError :message="form.errors.useful_life_months" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="asset-description">Description</Label>
                        <Input id="asset-description" v-model="form.description" placeholder="Optional notes" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Custody</Label>
                        <div class="flex gap-2">
                            <Button type="button" size="sm" :variant="custodyType === 'warehouse' ? 'default' : 'outline'" @click="custodyType = 'warehouse'">
                                Warehouse
                            </Button>
                            <Button type="button" size="sm" :variant="custodyType === 'employee' ? 'default' : 'outline'" @click="custodyType = 'employee'">
                                Employee
                            </Button>
                        </div>
                        <SearchableSelect
                            v-if="custodyType === 'warehouse'"
                            v-model="form.warehouse_id"
                            placeholder="Select warehouse"
                            :options="warehouseOptions"
                        />
                        <SearchableSelect
                            v-else
                            v-model="form.employee_id"
                            placeholder="Select employee"
                            :options="employeeOptions"
                        />
                        <InputError :message="form.errors.warehouse_id ?? form.errors.employee_id" />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showCreate = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Register
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showDepreciation" @update:open="(v) => (showDepreciation = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Run Depreciation</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="runDepreciation">
                    <div class="grid gap-2">
                        <Label for="depreciation-period">Period</Label>
                        <Input id="depreciation-period" v-model="depreciationForm.period" type="month" />
                        <InputError :message="depreciationForm.errors.period" />
                        <p class="text-xs text-muted-foreground">Posts one combined journal entry covering every eligible asset for this month.</p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDepreciation = false">Cancel</Button>
                        <Button type="submit" :disabled="depreciationForm.processing">
                            <LoaderCircle v-if="depreciationForm.processing" class="size-4 animate-spin" />
                            Run
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
