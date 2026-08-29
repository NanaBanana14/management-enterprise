<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { SearchableSelect } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, UserPlus } from 'lucide-vue-next';

interface NoteRow {
    id: number;
    note: string;
    author: string;
    created_at: string;
}

const props = defineProps<{
    applicant: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        stage: string;
        applied_at: string;
        vacancy: { id: number; title: string };
        notes: NoteRow[];
    };
    stages: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recruitment', href: '/hris/recruitment/vacancies' },
    { title: props.applicant.vacancy.title, href: `/hris/recruitment/vacancies/${props.applicant.vacancy.id}` },
    { title: props.applicant.name, href: `/hris/recruitment/applicants/${props.applicant.id}` },
];

const isTerminal = props.applicant.stage === 'hired' || props.applicant.stage === 'rejected';

function moveStage(stage: string) {
    router.post(route('hris.recruitment.applicants.stage', props.applicant.id), { stage });
}

const stageVariant: Record<string, 'outline' | 'warning' | 'success' | 'destructive' | 'secondary'> = {
    applied: 'outline',
    screening: 'secondary',
    interview: 'secondary',
    assessment: 'warning',
    offer: 'warning',
    hired: 'success',
    rejected: 'destructive',
};

const stageOptions = props.stages.map((s) => ({ value: s.value, label: s.label }));

const noteForm = useForm({ note: '' });
function submitNote() {
    noteForm.post(route('hris.recruitment.applicants.notes.store', props.applicant.id), {
        onSuccess: () => noteForm.reset(),
    });
}
</script>

<template>
    <Head :title="applicant.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ applicant.name }}</h1>
                        <Badge :variant="stageVariant[applicant.stage] ?? 'outline'">{{ applicant.stage }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ applicant.email }} · Applied {{ applicant.applied_at }} for {{ applicant.vacancy.title }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <SearchableSelect v-if="!isTerminal" class="w-44" :model-value="applicant.stage" :options="stageOptions" @update:model-value="moveStage" />
                    <Button v-if="applicant.stage === 'hired'" as-child>
                        <Link :href="`/hris/employees/create?name=${encodeURIComponent(applicant.name)}&email=${encodeURIComponent(applicant.email)}`">
                            <UserPlus class="size-4" />
                            Convert to Employee
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardContent class="space-y-4 pt-6">
                    <h2 class="text-sm font-medium">Notes</h2>
                    <div v-for="note in applicant.notes" :key="note.id" class="rounded-md border p-3 text-sm">
                        <p>{{ note.note }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ note.author }} · {{ note.created_at }}</p>
                    </div>
                    <p v-if="applicant.notes.length === 0" class="text-sm text-muted-foreground">No notes yet.</p>

                    <form class="flex gap-2" @submit.prevent="submitNote">
                        <Textarea v-model="noteForm.note" rows="2" placeholder="Add an interview note..." class="flex-1" />
                        <Button type="submit" :disabled="noteForm.processing || !noteForm.note">
                            <LoaderCircle v-if="noteForm.processing" class="size-4 animate-spin" />
                            Add
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
