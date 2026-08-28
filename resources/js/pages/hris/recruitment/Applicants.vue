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

interface ApplicantRow {
    id: number;
    name: string;
    email: string;
    stage: string;
    applied_at: string;
}

const props = defineProps<{
    vacancy: { id: number; title: string; status: string; department: string; position: string };
    applicants: ApplicantRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recruitment', href: '/hris/recruitment/vacancies' },
    { title: props.vacancy.title, href: `/hris/recruitment/vacancies/${props.vacancy.id}` },
];

const stageVariant: Record<string, 'outline' | 'warning' | 'success' | 'destructive' | 'secondary'> = {
    applied: 'outline',
    screening: 'secondary',
    interview: 'secondary',
    assessment: 'warning',
    offer: 'warning',
    hired: 'success',
    rejected: 'destructive',
};

const showDialog = ref(false);
const form = useForm({ name: '', email: '', phone: '' });
const submit = () => form.post(route('hris.recruitment.applicants.store', props.vacancy.id), { onSuccess: () => (showDialog.value = false) });
</script>

<template>
    <Head :title="vacancy.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="vacancy.title" :description="`${vacancy.department} · ${vacancy.position}`">
                <template #actions>
                    <Button @click="showDialog = true">
                        <Plus class="size-4" />
                        Add Applicant
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Applied</TableHead>
                        <TableHead>Stage</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="applicants.length === 0" :colspan="4">No applicants yet.</TableEmpty>
                    <TableRow
                        v-for="applicant in applicants"
                        :key="applicant.id"
                        class="cursor-pointer"
                        @click="router.visit(route('hris.recruitment.applicants.show', applicant.id))"
                    >
                        <TableCell class="font-medium">{{ applicant.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ applicant.email }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ applicant.applied_at }}</TableCell>
                        <TableCell>
                            <Badge :variant="stageVariant[applicant.stage] ?? 'outline'">{{ applicant.stage }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Applicant</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Add
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
