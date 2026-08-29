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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface VacancyRow {
    id: number;
    title: string;
    status: string;
    department: string;
    position: string;
    applicants_count: number;
}

const props = defineProps<{
    vacancies: VacancyRow[];
    departments: { id: number; name: string }[];
    positions: { id: number; name: string; department_id: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recruitment', href: '/hris/recruitment/vacancies' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('recruitment.manage');

const showDialog = ref(false);
const form = useForm({
    department_id: '' as number | '',
    position_id: '' as number | '',
    title: '',
    description: '',
});
const filteredPositions = computed(() => props.positions.filter((p) => Number(p.department_id) === Number(form.department_id)));
const submit = () => form.post(route('hris.recruitment.vacancies.store'), { onSuccess: () => (showDialog.value = false) });

const departmentOptions = computed(() => props.departments.map((d) => ({ value: d.id, label: d.name })));
const positionOptions = computed(() => filteredPositions.value.map((p) => ({ value: p.id, label: p.name })));
</script>

<template>
    <Head title="Recruitment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Recruitment" description="Open vacancies and their applicant pipelines.">
                <template #actions>
                    <Button v-if="canManage" @click="showDialog = true">
                        <Plus class="size-4" />
                        New Vacancy
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Position</TableHead>
                        <TableHead>Applicants</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="vacancies.length === 0" :colspan="5">No vacancies yet.</TableEmpty>
                    <TableRow
                        v-for="vacancy in vacancies"
                        :key="vacancy.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.recruitment.vacancies.show', vacancy.id))"
                    >
                        <TableCell class="font-medium">{{ vacancy.title }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ vacancy.department }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ vacancy.position }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ vacancy.applicants_count }}</TableCell>
                        <TableCell>
                            <Badge :variant="vacancy.status === 'open' ? 'success' : 'outline'">{{ vacancy.status }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Vacancy</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" placeholder="e.g. Backend Engineer" />
                        <InputError :message="form.errors.title" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="department_id">Department</Label>
                            <SearchableSelect
                                v-model="form.department_id"
                                placeholder="Select"
                                :options="departmentOptions"
                                @update:model-value="form.position_id = ''"
                            />
                            <InputError :message="form.errors.department_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="position_id">Position</Label>
                            <SearchableSelect v-model="form.position_id" placeholder="Select" :disabled="!form.department_id" :options="positionOptions" />
                            <InputError :message="form.errors.position_id" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" rows="3" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDialog = false">Cancel</Button>
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
