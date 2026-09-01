<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, PackageX } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface DepreciationEntryRow {
    id: number;
    period: string;
    amount: number;
}

interface RefOption {
    id: number;
    name: string;
}

const props = defineProps<{
    asset: {
        id: number;
        code: string;
        name: string;
        category: string;
        description: string | null;
        status: string;
        warehouse: { id: number; name: string } | null;
        employee: { id: number; name: string } | null;
        acquisition_date: string;
        acquisition_cost: number;
        salvage_value: number;
        useful_life_months: number;
        accumulated_depreciation: number;
        book_value: number;
        disposal_date: string | null;
        disposal_value: number | null;
        depreciation_entries: DepreciationEntryRow[];
    };
    warehouses: RefOption[];
    employees: RefOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fixed Assets', href: '/assets' },
    { title: props.asset.name, href: `/assets/${props.asset.id}` },
];

const page = usePage<SharedData>();
const canCreate = page.props.auth.permissions.includes('asset.create');
const canManage = page.props.auth.permissions.includes('asset.manage');

const isActive = props.asset.status === 'active';

const statusBadge: Record<string, 'success' | 'outline'> = {
    active: 'success',
    disposed: 'outline',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const custody = computed(() => props.asset.employee?.name ?? props.asset.warehouse?.name ?? 'Unassigned');

const warehouseOptions = props.warehouses.map((w) => ({ value: w.id, label: w.name }));
const employeeOptions = props.employees.map((e) => ({ value: e.id, label: e.name }));

const custodyType = ref<'warehouse' | 'employee'>(props.asset.employee ? 'employee' : 'warehouse');
const reassignForm = useForm({
    warehouse_id: (props.asset.warehouse?.id ?? '') as number | '',
    employee_id: (props.asset.employee?.id ?? '') as number | '',
});

function submitReassign() {
    if (custodyType.value === 'warehouse') {
        reassignForm.employee_id = '';
    } else {
        reassignForm.warehouse_id = '';
    }

    reassignForm.post(route('assets.reassign', props.asset.id));
}

const showDispose = ref(false);
const disposeForm = useForm({
    disposal_date: new Date().toISOString().slice(0, 10),
    disposal_value: 0,
});

function dispose() {
    disposeForm.post(route('assets.dispose', props.asset.id), { onSuccess: () => (showDispose.value = false) });
}
</script>

<template>
    <Head :title="asset.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ asset.name }}</h1>
                        <Badge :variant="statusBadge[asset.status] ?? 'outline'">{{ asset.status }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ asset.code }} · {{ asset.category }} · {{ custody }}</p>
                </div>
                <Button v-if="canManage && isActive" variant="destructive" @click="showDispose = true">
                    <PackageX class="size-4" />
                    Dispose Asset
                </Button>
            </div>

            <Card v-if="asset.status === 'disposed'">
                <CardContent class="pt-6 text-sm">
                    Disposed on {{ asset.disposal_date }} for {{ formatCurrency(asset.disposal_value ?? 0) }}.
                </CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Acquisition Cost</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-semibold">{{ formatCurrency(asset.acquisition_cost) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Accumulated Depreciation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-semibold">{{ formatCurrency(asset.accumulated_depreciation) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Book Value</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-semibold">{{ formatCurrency(asset.book_value) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Useful Life</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-semibold">{{ asset.useful_life_months }} mo</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Depreciation History</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Period</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in asset.depreciation_entries" :key="entry.id">
                                <TableCell>{{ entry.period }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(entry.amount) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p v-if="asset.depreciation_entries.length === 0" class="py-2 text-sm text-muted-foreground">No depreciation posted yet.</p>
                </CardContent>
            </Card>

            <Card v-if="canCreate && isActive">
                <CardHeader>
                    <CardTitle class="text-base">Reassign Custody</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
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
                        v-model="reassignForm.warehouse_id"
                        placeholder="Select warehouse"
                        :options="warehouseOptions"
                    />
                    <SearchableSelect
                        v-else
                        v-model="reassignForm.employee_id"
                        placeholder="Select employee"
                        :options="employeeOptions"
                    />
                    <Button :disabled="reassignForm.processing" @click="submitReassign">
                        <LoaderCircle v-if="reassignForm.processing" class="size-4 animate-spin" />
                        Save
                    </Button>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="showDispose" @update:open="(v) => (showDispose = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Dispose asset</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="dispose">
                    <p class="text-sm text-muted-foreground">This posts a journal entry recognizing any gain or loss and cannot be undone.</p>
                    <div class="grid gap-2">
                        <Label for="disposal-date">Disposal date</Label>
                        <Input id="disposal-date" v-model="disposeForm.disposal_date" type="date" />
                        <InputError :message="disposeForm.errors.disposal_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="disposal-value">Disposal value (proceeds received)</Label>
                        <Input id="disposal-value" v-model.number="disposeForm.disposal_value" type="number" min="0" step="1" />
                        <InputError :message="disposeForm.errors.disposal_value" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDispose = false">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="disposeForm.processing">
                            <LoaderCircle v-if="disposeForm.processing" class="size-4 animate-spin" />
                            Dispose
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
