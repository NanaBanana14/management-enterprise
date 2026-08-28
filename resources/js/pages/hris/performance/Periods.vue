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
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface PeriodRow {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
    reviews_count: number;
}

defineProps<{ periods: PeriodRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance', href: '/hris/performance/periods' },
];

const showCreateDialog = ref(false);
const form = useForm({ name: '', start_date: '', end_date: '' });
const submit = () => form.post(route('hris.performance.periods.store'), { onSuccess: () => (showCreateDialog.value = false) });
</script>

<template>
    <Head title="Performance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Performance" description="Review periods and employee evaluations.">
                <template #actions>
                    <Button @click="showCreateDialog = true">
                        <Plus class="size-4" />
                        New Period
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Period</TableHead>
                        <TableHead>Dates</TableHead>
                        <TableHead>Reviews</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="periods.length === 0" :colspan="4">No performance periods yet.</TableEmpty>
                    <TableRow
                        v-for="period in periods"
                        :key="period.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.performance.periods.show', period.id))"
                    >
                        <TableCell class="font-medium">{{ period.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ period.start_date }} – {{ period.end_date }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ period.reviews_count }}</TableCell>
                        <TableCell>
                            <Badge variant="success">{{ period.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showCreateDialog" @update:open="(v) => (showCreateDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Performance Period</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Q3 2026" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start date</Label>
                            <Input id="start_date" v-model="form.start_date" type="date" />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_date">End date</Label>
                            <Input id="end_date" v-model="form.end_date" type="date" />
                            <InputError :message="form.errors.end_date" />
                        </div>
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
