<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ReviewItem {
    id: number;
    score: number | null;
    notes: string | null;
    kpi: { id: number; name: string; weight: number; category: string };
}

const props = defineProps<{
    review: {
        id: number;
        status: string;
        overall_score: number | null;
        summary: string | null;
        employee: { id: number; name: string; employee_number: string };
        period: { id: number; name: string };
        items: ReviewItem[];
    };
    canEdit: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance', href: '/hris/performance/periods' },
    { title: props.review.period.name, href: `/hris/performance/periods/${props.review.period.id}` },
    { title: props.review.employee.name, href: `/hris/performance/reviews/${props.review.id}` },
];

const groupedItems = computed(() => {
    const groups: Record<string, ReviewItem[]> = {};
    for (const item of props.review.items) {
        (groups[item.kpi.category] ??= []).push(item);
    }
    return groups;
});

const scoringTarget = ref<ReviewItem | null>(null);
const scoreForm = useForm({ score: '', notes: '' });

function openScore(item: ReviewItem) {
    scoringTarget.value = item;
    scoreForm.score = item.score !== null ? String(item.score) : '';
    scoreForm.notes = item.notes ?? '';
}

function submitScore() {
    if (!scoringTarget.value) return;
    scoreForm.post(route('hris.performance.reviews.items.score', [props.review.id, scoringTarget.value.id]), {
        onSuccess: () => (scoringTarget.value = null),
    });
}

const showSubmitDialog = ref(false);
const submitForm = useForm({ summary: '' });
function submitReview() {
    submitForm.post(route('hris.performance.reviews.submit', props.review.id), {
        onSuccess: () => (showSubmitDialog.value = false),
    });
}

const allScored = computed(() => props.review.items.every((item) => item.score !== null));
</script>

<template>
    <Head :title="review.employee.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ review.employee.name }}</h1>
                        <Badge :variant="review.status === 'submitted' ? 'success' : 'warning'">{{ review.status }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ review.employee.employee_number }} · {{ review.period.name }}</p>
                </div>
                <div v-if="review.overall_score !== null" class="text-right">
                    <div class="text-2xl font-semibold">{{ review.overall_score }}</div>
                    <p class="text-xs text-muted-foreground">Overall score</p>
                </div>
            </div>

            <div v-for="(items, category) in groupedItems" :key="category" class="space-y-2">
                <h2 class="text-sm font-medium text-muted-foreground">{{ category }}</h2>
                <Card>
                    <CardContent class="divide-y p-0">
                        <div v-for="item in items" :key="item.id" class="flex items-center justify-between px-4 py-3">
                            <div>
                                <div class="text-sm font-medium">{{ item.kpi.name }}</div>
                                <div v-if="item.notes" class="text-xs text-muted-foreground">{{ item.notes }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Badge variant="outline">weight {{ item.kpi.weight }}%</Badge>
                                <span class="w-10 text-right text-sm font-medium">{{ item.score ?? '—' }}</span>
                                <Button v-if="canEdit" variant="ghost" size="sm" @click="openScore(item)">Score</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="review.summary" class="text-sm">
                <h2 class="mb-1 font-medium">Summary</h2>
                <p class="text-muted-foreground">{{ review.summary }}</p>
            </div>

            <Button v-if="canEdit" class="w-fit" :disabled="!allScored" @click="showSubmitDialog = true">
                Submit Review
            </Button>
        </div>

        <Dialog :open="scoringTarget !== null" @update:open="(v) => !v && (scoringTarget = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Score: {{ scoringTarget?.kpi.name }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitScore">
                    <div class="grid gap-2">
                        <Label for="score">Score (0–100)</Label>
                        <Input id="score" v-model="scoreForm.score" type="number" min="0" max="100" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <Textarea id="notes" v-model="scoreForm.notes" rows="3" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="scoringTarget = null">Cancel</Button>
                        <Button type="submit" :disabled="scoreForm.processing">
                            <LoaderCircle v-if="scoreForm.processing" class="size-4 animate-spin" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showSubmitDialog" @update:open="(v) => (showSubmitDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Submit Review</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitReview">
                    <div class="grid gap-2">
                        <Label for="summary">Summary</Label>
                        <Textarea id="summary" v-model="submitForm.summary" rows="4" placeholder="Overall comments for this review period" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showSubmitDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="submitForm.processing">
                            <LoaderCircle v-if="submitForm.processing" class="size-4 animate-spin" />
                            Submit
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
