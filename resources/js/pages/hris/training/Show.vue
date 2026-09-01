<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { CheckCircle2, Download, FileText, LoaderCircle, Pencil, Plus, PlayCircle, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface MaterialRow {
    id: number;
    title: string;
    type: 'text' | 'video' | 'document';
    body: string | null;
    video_url: string | null;
    file_url: string | null;
}

interface EnrollmentRow {
    id: number;
    employee: string;
    status: string;
}

const props = defineProps<{
    program: {
        id: number;
        name: string;
        description: string | null;
        provider: string | null;
        duration_hours: number | null;
        audience: string;
        department: string | null;
        category: string;
        materials: MaterialRow[];
    };
    canManage: boolean;
    enrollments: EnrollmentRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Training', href: '/hris/training' },
    { title: props.program.name, href: `/hris/training/programs/${props.program.id}` },
];

const statusVariant: Record<string, 'secondary' | 'warning' | 'success' | 'outline'> = {
    enrolled: 'secondary',
    in_progress: 'warning',
    completed: 'success',
    cancelled: 'outline',
};

const typeIcon = { text: FileText, video: PlayCircle, document: FileText } as const;

function sanitizedBody(body: string | null): string {
    return body ? DOMPurify.sanitize(body) : '';
}

function videoEmbedUrl(url: string | null): string | null {
    if (!url) return null;

    try {
        const parsed = new URL(url);

        if (parsed.hostname.includes('youtube.com')) {
            const id = parsed.searchParams.get('v');
            return id ? `https://www.youtube.com/embed/${id}` : null;
        }

        if (parsed.hostname === 'youtu.be') {
            return `https://www.youtube.com/embed/${parsed.pathname.slice(1)}`;
        }

        if (parsed.hostname.includes('vimeo.com')) {
            const id = parsed.pathname.split('/').filter(Boolean).pop();
            return id ? `https://player.vimeo.com/video/${id}` : null;
        }

        return null;
    } catch {
        return null;
    }
}

function isPdf(url: string | null): boolean {
    return !!url && url.toLowerCase().split('?')[0].endsWith('.pdf');
}

// Lesson player state
const activeMaterialId = ref<number | null>(props.program.materials[0]?.id ?? null);
const activeMaterial = computed(() => props.program.materials.find((m) => m.id === activeMaterialId.value) ?? null);

function selectMaterial(id: number) {
    activeMaterialId.value = id;
}

// Create / edit material dialog (shared form, editingId null = create mode)
const showMaterialDialog = ref(false);
const editingId = ref<number | null>(null);
const materialForm = useForm<{
    _method: 'post' | 'put';
    title: string;
    type: 'text' | 'video' | 'document';
    body: string;
    video_url: string;
    file: File | null;
}>({
    _method: 'post',
    title: '',
    type: 'text',
    body: '',
    video_url: '',
    file: null,
});

function openCreate() {
    editingId.value = null;
    materialForm.reset();
    materialForm.clearErrors();
    showMaterialDialog.value = true;
}

function openEdit(material: MaterialRow) {
    editingId.value = material.id;
    materialForm.clearErrors();
    materialForm._method = 'put';
    materialForm.title = material.title;
    materialForm.type = material.type;
    materialForm.body = material.body ?? '';
    materialForm.video_url = material.video_url ?? '';
    materialForm.file = null;
    showMaterialDialog.value = true;
}

function submitMaterial() {
    const url = editingId.value
        ? route('hris.training.programs.materials.update', [props.program.id, editingId.value])
        : route('hris.training.programs.materials.store', props.program.id);

    materialForm.post(url, {
        forceFormData: true,
        onSuccess: () => {
            showMaterialDialog.value = false;
            materialForm.reset();
        },
    });
}

function onFileChange(event: Event) {
    materialForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

// Delete material
const deleteTarget = ref<MaterialRow | null>(null);
const deleteForm = useForm({});
function confirmDelete() {
    if (!deleteTarget.value) return;

    deleteForm.delete(route('hris.training.programs.materials.destroy', [props.program.id, deleteTarget.value.id]), {
        onSuccess: () => {
            if (activeMaterialId.value === deleteTarget.value?.id) {
                activeMaterialId.value = props.program.materials.find((m) => m.id !== deleteTarget.value?.id)?.id ?? null;
            }
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head :title="program.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ program.name }}</h1>
                        <Badge variant="outline">{{ program.department ?? 'General' }}</Badge>
                        <Badge :variant="program.audience === 'recruitment' ? 'warning' : 'secondary'">
                            {{ program.audience === 'recruitment' ? 'Recruitment Screening' : 'Staff Training' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ program.category }} · {{ program.provider ?? 'Internal' }}
                        <span v-if="program.duration_hours"> · {{ program.duration_hours }}h</span>
                        · {{ program.materials.length }} lesson{{ program.materials.length === 1 ? '' : 's' }}
                    </p>
                </div>
                <Button v-if="canManage" @click="openCreate">
                    <Plus class="size-4" />
                    Add Material
                </Button>
            </div>

            <Card v-if="program.description">
                <CardContent class="pt-6 text-sm text-muted-foreground">{{ program.description }}</CardContent>
            </Card>

            <div v-if="program.materials.length > 0" class="grid gap-4 lg:grid-cols-[280px_1fr] lg:items-start">
                <Card class="lg:sticky lg:top-6">
                    <CardHeader>
                        <CardTitle class="text-base">Course Content</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1 p-3 pt-0">
                        <button
                            v-for="(material, index) in program.materials"
                            :key="material.id"
                            type="button"
                            class="flex w-full items-start gap-3 rounded-md px-3 py-2.5 text-left text-sm transition-colors"
                            :class="material.id === activeMaterialId ? 'bg-primary/10 text-primary' : 'hover:bg-muted'"
                            @click="selectMaterial(material.id)"
                        >
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-medium"
                                :class="material.id === activeMaterialId ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                            >
                                {{ index + 1 }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ material.title }}</span>
                                <span class="flex items-center gap-1 text-xs text-muted-foreground capitalize">
                                    <component :is="typeIcon[material.type]" class="size-3" />
                                    {{ material.type }}
                                </span>
                            </span>
                        </button>
                    </CardContent>
                </Card>

                <Card v-if="activeMaterial">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0">
                        <div>
                            <CardTitle class="text-lg">{{ activeMaterial.title }}</CardTitle>
                        </div>
                        <div v-if="canManage" class="flex shrink-0 gap-1">
                            <Button variant="ghost" size="icon" class="size-8" @click="openEdit(activeMaterial)">
                                <Pencil class="size-4" />
                            </Button>
                            <Button variant="ghost" size="icon" class="size-8 text-destructive hover:text-destructive" @click="deleteTarget = activeMaterial">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="activeMaterial.type === 'text'" class="rich-text-content" v-html="sanitizedBody(activeMaterial.body)" />

                        <div v-else-if="activeMaterial.type === 'video'">
                            <div v-if="videoEmbedUrl(activeMaterial.video_url)" class="aspect-video overflow-hidden rounded-lg bg-black">
                                <iframe
                                    :src="videoEmbedUrl(activeMaterial.video_url) ?? undefined"
                                    class="size-full"
                                    allow="accelerated-video; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                />
                            </div>
                            <a
                                v-else
                                :href="activeMaterial.video_url ?? '#'"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center gap-2 text-sm text-primary underline"
                            >
                                <PlayCircle class="size-4" />
                                Watch video
                            </a>
                        </div>

                        <div v-else>
                            <div v-if="isPdf(activeMaterial.file_url)" class="mb-3 overflow-hidden rounded-lg border">
                                <iframe :src="activeMaterial.file_url ?? undefined" class="h-[600px] w-full" />
                            </div>
                            <a
                                v-if="activeMaterial.file_url"
                                :href="activeMaterial.file_url"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-muted"
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                    <FileText class="size-5" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium">{{ activeMaterial.title }}</span>
                                    <span class="text-xs text-muted-foreground">Click to open or download</span>
                                </span>
                                <Download class="size-4 shrink-0 text-muted-foreground" />
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-else>
                <CardContent class="flex flex-col items-center gap-2 py-10 text-center text-sm text-muted-foreground">
                    <CheckCircle2 class="size-8 text-muted-foreground/50" />
                    No materials added yet.
                </CardContent>
            </Card>

            <Card v-if="canManage">
                <CardHeader>
                    <CardTitle class="text-base">Enrolled Employees</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div v-for="enrollment in enrollments" :key="enrollment.id" class="flex items-center justify-between border-b py-2 text-sm last:border-b-0">
                        <span>{{ enrollment.employee }}</span>
                        <Badge :variant="statusVariant[enrollment.status] ?? 'outline'">{{ enrollment.status }}</Badge>
                    </div>
                    <p v-if="enrollments.length === 0" class="py-2 text-sm text-muted-foreground">No one is enrolled yet.</p>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="showMaterialDialog" @update:open="(v) => (showMaterialDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Edit Material' : 'Add Material' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitMaterial">
                    <div class="grid gap-2">
                        <Label for="material-title">Title</Label>
                        <Input id="material-title" v-model="materialForm.title" />
                        <InputError :message="materialForm.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Type</Label>
                        <div class="flex gap-2">
                            <Button type="button" size="sm" :variant="materialForm.type === 'text' ? 'default' : 'outline'" @click="materialForm.type = 'text'">
                                Text
                            </Button>
                            <Button type="button" size="sm" :variant="materialForm.type === 'video' ? 'default' : 'outline'" @click="materialForm.type = 'video'">
                                Video
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                :variant="materialForm.type === 'document' ? 'default' : 'outline'"
                                @click="materialForm.type = 'document'"
                            >
                                Document
                            </Button>
                        </div>
                    </div>

                    <div v-if="materialForm.type === 'text'" class="grid gap-2">
                        <Label>Content</Label>
                        <RichTextEditor v-model="materialForm.body" />
                        <InputError :message="materialForm.errors.body" />
                    </div>

                    <div v-else-if="materialForm.type === 'video'" class="grid gap-2">
                        <Label for="material-video">Video URL</Label>
                        <Input id="material-video" v-model="materialForm.video_url" placeholder="https://youtube.com/watch?v=..." />
                        <InputError :message="materialForm.errors.video_url" />
                        <p class="text-xs text-muted-foreground">YouTube and Vimeo links are embedded automatically.</p>
                    </div>

                    <div v-else class="grid gap-2">
                        <Label for="material-file">File (PDF, Word, PowerPoint)</Label>
                        <Input id="material-file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx" @change="onFileChange" />
                        <InputError :message="materialForm.errors.file" />
                        <p v-if="editingId" class="text-xs text-muted-foreground">Leave empty to keep the current file.</p>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showMaterialDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="materialForm.processing">
                            <LoaderCircle v-if="materialForm.processing" class="size-4 animate-spin" />
                            {{ editingId ? 'Save' : 'Add' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteTarget !== null"
            title="Remove material"
            :description="deleteTarget ? `Remove '${deleteTarget.title}' from this program?` : ''"
            confirm-label="Remove"
            @update:open="(v) => !v && (deleteTarget = null)"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
