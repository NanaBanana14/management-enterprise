<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface ProgramRow {
    id: number;
    name: string;
    provider: string | null;
    duration_hours: number | null;
    enrollments_count: number;
    my_enrollment: { id: number; status: string } | null;
}

interface CategoryRow {
    id: number;
    name: string;
    programs: ProgramRow[];
}

const props = defineProps<{
    categories: CategoryRow[];
    hasEmployeeProfile: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Training', href: '/hris/training' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('training.manage');

const statusVariant: Record<string, 'secondary' | 'warning' | 'success' | 'outline'> = {
    enrolled: 'secondary',
    in_progress: 'warning',
    completed: 'success',
    cancelled: 'outline',
};

function enroll(programId: number) {
    router.post(route('hris.training.programs.enroll', programId));
}

const showCategoryDialog = ref(false);
const categoryForm = useForm({ name: '' });
function submitCategory() {
    categoryForm.post(route('hris.training.categories.store'), {
        onSuccess: () => {
            showCategoryDialog.value = false;
            categoryForm.reset();
        },
    });
}

const showProgramDialog = ref(false);
const programForm = useForm({
    training_category_id: '' as number | '',
    name: '',
    provider: '',
    duration_hours: '',
    description: '',
});
function submitProgram() {
    programForm.post(route('hris.training.programs.store'), {
        onSuccess: () => {
            showProgramDialog.value = false;
            programForm.reset();
        },
    });
}

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));
</script>

<template>
    <Head title="Training" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Training" description="Training programs and enrollment history.">
                <template #actions>
                    <div v-if="canManage" class="flex gap-2">
                        <Button variant="outline" @click="showCategoryDialog = true">
                            <Plus class="size-4" />
                            Category
                        </Button>
                        <Button @click="showProgramDialog = true">
                            <Plus class="size-4" />
                            Program
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
                        <div v-for="program in category.programs" :key="program.id" class="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium">{{ program.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ program.provider ?? 'Internal' }}
                                    <span v-if="program.duration_hours"> · {{ program.duration_hours }}h</span>
                                    · {{ program.enrollments_count }} enrolled
                                </div>
                            </div>
                            <Badge v-if="program.my_enrollment" :variant="statusVariant[program.my_enrollment.status] ?? 'outline'">
                                {{ program.my_enrollment.status }}
                            </Badge>
                            <Button v-else-if="hasEmployeeProfile" variant="outline" size="sm" @click="enroll(program.id)">Enroll</Button>
                        </div>
                        <p v-if="category.programs.length === 0" class="text-sm text-muted-foreground">No programs yet.</p>
                    </CardContent>
                </Card>
                <p v-if="categories.length === 0" class="text-sm text-muted-foreground">No training categories defined yet.</p>
            </div>
        </div>

        <Dialog :open="showCategoryDialog" @update:open="(v) => (showCategoryDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Training Category</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitCategory">
                    <div class="grid gap-2">
                        <Label for="cat_name">Name</Label>
                        <Input id="cat_name" v-model="categoryForm.name" />
                        <InputError :message="categoryForm.errors.name" />
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

        <Dialog :open="showProgramDialog" @update:open="(v) => (showProgramDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Training Program</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitProgram">
                    <div class="grid gap-2">
                        <Label for="training_category_id">Category</Label>
                        <SearchableSelect v-model="programForm.training_category_id" placeholder="Select a category" :options="categoryOptions" />
                        <InputError :message="programForm.errors.training_category_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="prog_name">Name</Label>
                        <Input id="prog_name" v-model="programForm.name" />
                        <InputError :message="programForm.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="provider">Provider</Label>
                            <Input id="provider" v-model="programForm.provider" placeholder="e.g. Internal" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="duration_hours">Duration (hours)</Label>
                            <Input id="duration_hours" v-model="programForm.duration_hours" type="number" min="1" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="prog_description">Description</Label>
                        <Textarea id="prog_description" v-model="programForm.description" rows="2" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showProgramDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="programForm.processing">
                            <LoaderCircle v-if="programForm.processing" class="size-4 animate-spin" />
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
