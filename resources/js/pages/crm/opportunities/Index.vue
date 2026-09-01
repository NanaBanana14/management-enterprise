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
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OpportunityRow {
    id: number;
    title: string;
    customer: string;
    warehouse: string;
    stage: string;
    expected_close_date: string | null;
    total: number;
}

interface RefOption {
    id: number;
    name?: string;
    sku?: string;
    price?: number;
}

const props = defineProps<{
    opportunities: OpportunityRow[];
    customers: RefOption[];
    warehouses: RefOption[];
    products: RefOption[];
    assignableUsers: RefOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Opportunities', href: '/crm/opportunities' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('opportunity.manage');

const customerOptions = props.customers.map((c) => ({ value: c.id, label: c.name as string }));
const warehouseOptions = props.warehouses.map((w) => ({ value: w.id, label: w.name as string }));
const productOptions = props.products.map((p) => ({ value: p.id, label: `${p.sku} — ${p.name}` }));
const userOptions = props.assignableUsers.map((u) => ({ value: u.id, label: u.name as string }));

const stageBadge: Record<string, 'outline' | 'secondary' | 'warning' | 'success' | 'destructive'> = {
    prospecting: 'outline',
    qualified: 'secondary',
    proposal: 'warning',
    negotiation: 'warning',
    won: 'success',
    lost: 'destructive',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const today = new Date().toISOString().slice(0, 10);

const showCreate = ref(false);
const form = useForm({
    customer_id: '' as number | '',
    warehouse_id: '' as number | '',
    title: '',
    source: '',
    expected_close_date: '',
    assigned_to: '' as number | '',
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
    form.lines = [{ product_id: '', quantity: 1, unit_price: 0 }];
    form.clearErrors();
    showCreate.value = true;
}

function submit() {
    form.post(route('crm.opportunities.store'), { onSuccess: () => (showCreate.value = false) });
}
</script>

<template>
    <Head title="Opportunities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Opportunities" description="Sales pipeline, from first contact to a won deal.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        New Opportunity
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Expected Close</TableHead>
                        <TableHead class="text-right">Value</TableHead>
                        <TableHead>Stage</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="opportunities.length === 0" :colspan="5">No opportunities yet.</TableEmpty>
                    <TableRow
                        v-for="opportunity in opportunities"
                        :key="opportunity.id"
                        class="cursor-pointer"
                        @click="router.visit(route('crm.opportunities.show', opportunity.id))"
                    >
                        <TableCell class="font-medium">{{ opportunity.title }}</TableCell>
                        <TableCell>{{ opportunity.customer }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ opportunity.expected_close_date ?? '—' }}</TableCell>
                        <TableCell class="text-right">{{ formatCurrency(opportunity.total) }}</TableCell>
                        <TableCell>
                            <Badge :variant="stageBadge[opportunity.stage] ?? 'outline'">{{ opportunity.stage }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreate" @update:open="(v) => (showCreate = v)">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>New Opportunity</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="opp-title">Title</Label>
                        <Input id="opp-title" v-model="form.title" placeholder="e.g. Office refresh — chairs & desks" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Customer</Label>
                            <SearchableSelect v-model="form.customer_id" placeholder="Select customer" :options="customerOptions" />
                            <InputError :message="form.errors.customer_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Warehouse</Label>
                            <SearchableSelect v-model="form.warehouse_id" placeholder="Fulfilling warehouse" :options="warehouseOptions" />
                            <InputError :message="form.errors.warehouse_id" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="opp-source">Source</Label>
                            <Input id="opp-source" v-model="form.source" placeholder="e.g. Referral" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="opp-close">Expected close</Label>
                            <Input id="opp-close" v-model="form.expected_close_date" type="date" :min="today" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Assigned to</Label>
                            <SearchableSelect v-model="form.assigned_to" placeholder="Unassigned" :options="userOptions" />
                        </div>
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
                        <span>Estimated value</span>
                        <span>{{ formatCurrency(total) }}</span>
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
