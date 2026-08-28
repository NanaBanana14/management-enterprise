<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface ReviewRow {
    id: number;
    period: string;
    overall_score: number;
    submitted_at: string | null;
}

defineProps<{ reviews: ReviewRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance', href: '/hris/performance' },
];
</script>

<template>
    <Head title="Performance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Performance" description="Your completed performance reviews." />

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Period</TableHead>
                        <TableHead>Score</TableHead>
                        <TableHead>Submitted</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="reviews.length === 0" :colspan="3">No reviews yet.</TableEmpty>
                    <TableRow
                        v-for="review in reviews"
                        :key="review.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.performance.reviews.show', review.id))"
                    >
                        <TableCell class="font-medium">{{ review.period }}</TableCell>
                        <TableCell>{{ review.overall_score }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ review.submitted_at }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AppLayout>
</template>
