<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface ReviewRow {
    id: number;
    employee: { id: number; name: string; employee_number: string };
    reviewer: { id: number; name: string };
    status: string;
    overall_score: number | null;
}

const props = defineProps<{
    period: { id: number; name: string };
    reviews: ReviewRow[];
    availableEmployees: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance', href: '/hris/performance/periods' },
    { title: props.period.name, href: `/hris/performance/periods/${props.period.id}` },
];

const showCreateDialog = ref(false);
const form = useForm({ employee_id: '' as number | '' });
const submit = () => form.post(route('hris.performance.periods.reviews.store', props.period.id), { onSuccess: () => (showCreateDialog.value = false) });

const statusVariant: Record<string, 'warning' | 'success'> = { draft: 'warning', submitted: 'success' };
const employeeOptions = props.availableEmployees.map((e) => ({ value: e.id, label: e.name }));
</script>

<template>
    <Head :title="period.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="period.name" description="Employee performance reviews for this period.">
                <template #actions>
                    <Button v-if="availableEmployees.length > 0" @click="showCreateDialog = true">
                        <Plus class="size-4" />
                        New Review
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Employee</TableHead>
                        <TableHead>Reviewer</TableHead>
                        <TableHead>Score</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="reviews.length === 0" :colspan="4">No reviews yet.</TableEmpty>
                    <TableRow
                        v-for="review in reviews"
                        :key="review.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.performance.reviews.show', review.id))"
                    >
                        <TableCell class="font-medium">
                            <div>{{ review.employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ review.employee.employee_number }}</div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ review.reviewer.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ review.overall_score ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant[review.status] ?? 'outline'">{{ review.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreateDialog" @update:open="(v) => (showCreateDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Performance Review</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="employee_id">Employee</Label>
                        <SearchableSelect v-model="form.employee_id" placeholder="Select an employee" :options="employeeOptions" />
                        <InputError :message="form.errors.employee_id" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showCreateDialog = false">Cancel</Button>
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
