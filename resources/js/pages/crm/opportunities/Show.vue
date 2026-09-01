<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Trophy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface LineRow {
    product: string;
    quantity: number;
    unit_price: number;
}

interface NoteRow {
    id: number;
    note: string;
    author: string;
    created_at: string;
}

const props = defineProps<{
    opportunity: {
        id: number;
        title: string;
        stage: string;
        source: string | null;
        expected_close_date: string | null;
        customer: { id: number; name: string };
        warehouse: { id: number; name: string };
        assignee: string | null;
        creator: string;
        salesOrder: { id: number; number: string } | null;
        lines: LineRow[];
        notes: NoteRow[];
    };
    stages: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Opportunities', href: '/crm/opportunities' },
    { title: props.opportunity.title, href: `/crm/opportunities/${props.opportunity.id}` },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('opportunity.manage');

const isTerminal = props.opportunity.stage === 'won' || props.opportunity.stage === 'lost';

const stageVariant: Record<string, 'outline' | 'warning' | 'success' | 'destructive' | 'secondary'> = {
    prospecting: 'outline',
    qualified: 'secondary',
    proposal: 'warning',
    negotiation: 'warning',
    won: 'success',
    lost: 'destructive',
};

const stageOptions = computed(() => props.stages.filter((s) => s.value !== 'won'));

function moveStage(stage: string) {
    router.post(route('crm.opportunities.stage', props.opportunity.id), { stage });
}

const showWinConfirm = ref(false);

function markWon() {
    router.post(
        route('crm.opportunities.win', props.opportunity.id),
        {},
        {
            onFinish: () => (showWinConfirm.value = false),
        },
    );
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const total = computed(() => props.opportunity.lines.reduce((sum, l) => sum + l.quantity * l.unit_price, 0));

const noteForm = useForm({ note: '' });
function submitNote() {
    noteForm.post(route('crm.opportunities.notes.store', props.opportunity.id), {
        onSuccess: () => noteForm.reset(),
    });
}
</script>

<template>
    <Head :title="opportunity.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ opportunity.title }}</h1>
                        <Badge :variant="stageVariant[opportunity.stage] ?? 'outline'">{{ opportunity.stage }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ opportunity.customer.name }} · {{ opportunity.warehouse.name }}
                        <span v-if="opportunity.assignee"> · Assigned to {{ opportunity.assignee }}</span>
                        <span v-if="opportunity.expected_close_date"> · Expected close {{ opportunity.expected_close_date }}</span>
                    </p>
                </div>
                <div v-if="canManage && !isTerminal" class="flex items-center gap-2">
                    <SearchableSelect class="w-44" :model-value="opportunity.stage" :options="stageOptions" @update:model-value="moveStage" />
                    <Button variant="default" @click="showWinConfirm = true">
                        <Trophy class="size-4" />
                        Mark Won
                    </Button>
                </div>
            </div>

            <Card v-if="opportunity.salesOrder">
                <CardContent class="pt-6 text-sm">
                    This opportunity was won and converted into sales order
                    <Link :href="route('erp.sales-orders.index')" class="font-medium text-primary underline">
                        {{ opportunity.salesOrder.number }}
                    </Link>
                    .
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <h2 class="mb-3 text-sm font-medium">Line Items</h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead class="text-right">Qty</TableHead>
                                <TableHead class="text-right">Unit Price</TableHead>
                                <TableHead class="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(line, index) in opportunity.lines" :key="index">
                                <TableCell>{{ line.product }}</TableCell>
                                <TableCell class="text-right">{{ line.quantity }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(line.unit_price) }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(line.quantity * line.unit_price) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="mt-3 flex justify-end border-t pt-3 text-sm font-medium">
                        <span class="mr-2">Total</span>
                        <span>{{ formatCurrency(total) }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-4 pt-6">
                    <h2 class="text-sm font-medium">Notes</h2>
                    <div v-for="note in opportunity.notes" :key="note.id" class="rounded-md border p-3 text-sm">
                        <p>{{ note.note }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ note.author }} · {{ note.created_at }}</p>
                    </div>
                    <p v-if="opportunity.notes.length === 0" class="text-sm text-muted-foreground">No notes yet.</p>

                    <form v-if="canManage" class="flex gap-2" @submit.prevent="submitNote">
                        <Textarea v-model="noteForm.note" rows="2" placeholder="Add a note..." class="flex-1" />
                        <Button type="submit" :disabled="noteForm.processing || !noteForm.note">
                            <LoaderCircle v-if="noteForm.processing" class="size-4 animate-spin" />
                            Add
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>

        <ConfirmDialog
            :open="showWinConfirm"
            title="Mark opportunity as won"
            :description="`This will create a sales order for ${opportunity.customer.name} from this opportunity's line items. This cannot be undone.`"
            confirm-label="Mark Won"
            @update:open="(v) => (showWinConfirm = v)"
            @confirm="markWon"
        />
    </AppLayout>
</template>
