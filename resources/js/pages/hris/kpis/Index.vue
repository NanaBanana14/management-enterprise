<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface KpiRow {
    id: number;
    name: string;
    description: string | null;
    weight: number;
}

interface CategoryRow {
    id: number;
    name: string;
    description: string | null;
    kpis: KpiRow[];
}

const props = defineProps<{ categories: CategoryRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'KPIs', href: '/hris/kpis' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('kpi.manage');

const showCategoryDialog = ref(false);
const categoryForm = useForm({ name: '', description: '' });
function submitCategory() {
    categoryForm.post(route('hris.kpis.categories.store'), {
        onSuccess: () => {
            showCategoryDialog.value = false;
            categoryForm.reset();
        },
    });
}

const showKpiDialog = ref(false);
const kpiForm = useForm({ kpi_category_id: '' as number | '', name: '', description: '', weight: '' });
function submitKpi() {
    kpiForm.post(route('hris.kpis.store'), {
        onSuccess: () => {
            showKpiDialog.value = false;
            kpiForm.reset();
        },
    });
}

const deleteTarget = ref<KpiRow | null>(null);
function destroyKpi() {
    if (!deleteTarget.value) return;
    router.delete(route('hris.kpis.destroy', deleteTarget.value.id), { onSuccess: () => (deleteTarget.value = null) });
}
</script>

<template>
    <Head title="KPIs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="KPIs" description="Key performance indicators used in performance reviews.">
                <template #actions>
                    <div v-if="canManage" class="flex gap-2">
                        <Button variant="outline" @click="showCategoryDialog = true">
                            <Plus class="size-4" />
                            Category
                        </Button>
                        <Button @click="showKpiDialog = true">
                            <Plus class="size-4" />
                            KPI
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card v-for="category in categories" :key="category.id">
                    <CardHeader>
                        <CardTitle class="text-base">{{ category.name }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="kpi in category.kpis" :key="kpi.id" class="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium">{{ kpi.name }}</div>
                                <div v-if="kpi.description" class="text-xs text-muted-foreground">{{ kpi.description }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary">{{ kpi.weight }}%</Badge>
                                <button v-if="canManage" type="button" @click="deleteTarget = kpi">
                                    <Trash2 class="size-3.5 text-destructive" />
                                </button>
                            </div>
                        </div>
                        <p v-if="category.kpis.length === 0" class="text-sm text-muted-foreground">No KPIs in this category yet.</p>
                    </CardContent>
                </Card>
                <p v-if="categories.length === 0" class="text-sm text-muted-foreground">No KPI categories defined yet.</p>
            </div>
        </div>

        <Dialog :open="showCategoryDialog" @update:open="(v) => (showCategoryDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New KPI Category</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitCategory">
                    <div class="grid gap-2">
                        <Label for="cat_name">Name</Label>
                        <Input id="cat_name" v-model="categoryForm.name" />
                        <InputError :message="categoryForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cat_description">Description</Label>
                        <Textarea id="cat_description" v-model="categoryForm.description" rows="2" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showCategoryDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="categoryForm.processing">
                            <LoaderCircle v-if="categoryForm.processing" class="size-4 animate-spin" />
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showKpiDialog" @update:open="(v) => (showKpiDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New KPI</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitKpi">
                    <div class="grid gap-2">
                        <Label for="kpi_category_id">Category</Label>
                        <Select id="kpi_category_id" v-model="kpiForm.kpi_category_id">
                            <option value="" disabled>Select a category</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </Select>
                        <InputError :message="kpiForm.errors.kpi_category_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="kpi_name">Name</Label>
                        <Input id="kpi_name" v-model="kpiForm.name" />
                        <InputError :message="kpiForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="kpi_weight">Weight (%)</Label>
                        <Input id="kpi_weight" v-model="kpiForm.weight" type="number" min="1" max="100" />
                        <InputError :message="kpiForm.errors.weight" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="kpi_description">Description</Label>
                        <Textarea id="kpi_description" v-model="kpiForm.description" rows="2" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showKpiDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="kpiForm.processing">
                            <LoaderCircle v-if="kpiForm.processing" class="size-4 animate-spin" />
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteTarget !== null"
            title="Delete KPI"
            :description="deleteTarget ? `Remove '${deleteTarget.name}' from the catalog?` : ''"
            confirm-label="Delete"
            @update:open="(v) => !v && (deleteTarget = null)"
            @confirm="destroyKpi"
        />
    </AppLayout>
</template>
