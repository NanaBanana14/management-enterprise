<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { SearchableSelect } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';

interface NoteRow {
    id: number;
    note: string;
    author: string;
    created_at: string;
}

interface TrainingResultRow {
    id: number;
    program: string;
    result: string;
    assessor: string | null;
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
        training_results: TrainingResultRow[];
    };
    stages: { value: string; label: string }[];
    eligiblePrograms: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recruitment', href: '/hris/recruitment/vacancies' },
    { title: props.applicant.vacancy.title, href: `/hris/recruitment/vacancies/${props.applicant.vacancy.id}` },
    { title: props.applicant.name, href: `/hris/recruitment/applicants/${props.applicant.id}` },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('recruitment.manage');

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

const resultVariant: Record<string, 'outline' | 'warning' | 'success' | 'destructive'> = {
    pending: 'warning',
    passed: 'success',
    failed: 'destructive',
};

const stageOptions = props.stages.map((s) => ({ value: s.value, label: s.label }));

const noteForm = useForm({ note: '' });
function submitNote() {
    noteForm.post(route('hris.recruitment.applicants.notes.store', props.applicant.id), {
        onSuccess: () => noteForm.reset(),
    });
}

const assignedProgramIds = computed(() => props.applicant.training_results.map((r) => r.program));
const availablePrograms = computed(() => props.eligiblePrograms.filter((p) => !assignedProgramIds.value.includes(p.name)));

const assignForm = useForm({ training_program_id: '' as number | '' });
function assignTraining() {
    assignForm.post(route('hris.recruitment.applicants.training.store', props.applicant.id), {
        onSuccess: () => assignForm.reset(),
    });
}

function recordResult(resultId: number, result: 'passed' | 'failed') {
    router.post(route('hris.recruitment.applicants.training.update', [props.applicant.id, resultId]), { result });
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

            <Card v-if="canManage && (applicant.training_results.length > 0 || availablePrograms.length > 0)">
                <CardContent class="space-y-4 pt-6">
                    <h2 class="text-sm font-medium">Screening Training</h2>

                    <div v-for="result in applicant.training_results" :key="result.id" class="flex items-center justify-between rounded-md border p-3 text-sm">
                        <div>
                            <div class="font-medium">{{ result.program }}</div>
                            <div v-if="result.assessor" class="text-xs text-muted-foreground">Assessed by {{ result.assessor }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="resultVariant[result.result] ?? 'outline'">{{ result.result }}</Badge>
                            <template v-if="result.result === 'pending' && !isTerminal">
                                <Button size="sm" variant="outline" @click="recordResult(result.id, 'passed')">Pass</Button>
                                <Button size="sm" variant="destructive" @click="recordResult(result.id, 'failed')">Fail</Button>
                            </template>
                        </div>
                    </div>

                    <form v-if="availablePrograms.length > 0 && !isTerminal" class="flex gap-2" @submit.prevent="assignTraining">
                        <SearchableSelect
                            v-model="assignForm.training_program_id"
                            class="flex-1"
                            placeholder="Assign a screening program"
                            :options="availablePrograms.map((p) => ({ value: p.id, label: p.name }))"
                        />
                        <Button type="submit" :disabled="assignForm.processing || !assignForm.training_program_id">
                            <LoaderCircle v-if="assignForm.processing" class="size-4 animate-spin" />
                            Assign
                        </Button>
                    </form>
                </CardContent>
            </Card>

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
