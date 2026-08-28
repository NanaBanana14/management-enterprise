<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    employee: {
        id: number;
        employee_number: string;
        name: string;
        email: string;
        phone: string | null;
        department_id: number;
        position_id: number;
        manager_id: number | null;
        employment_type: string;
        employment_status: string;
        join_date: string;
        basic_salary: string;
        address: string | null;
        emergency_contact_name: string | null;
        emergency_contact_phone: string | null;
        emergency_contact_relationship: string | null;
        photo_url: string | null;
    };
    departments: { id: number; name: string }[];
    positions: { id: number; name: string; department_id: number }[];
    managers: { id: number; name: string }[];
    employmentTypes: { value: string; label: string }[];
    employmentStatuses: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/hris/employees' },
    { title: props.employee.name, href: `/hris/employees/${props.employee.id}` },
    { title: 'Edit', href: `/hris/employees/${props.employee.id}/edit` },
];

const form = useForm({
    _method: 'put',
    name: props.employee.name,
    email: props.employee.email,
    phone: props.employee.phone ?? '',
    department_id: props.employee.department_id,
    position_id: props.employee.position_id,
    manager_id: props.employee.manager_id ?? ('' as number | ''),
    employment_type: props.employee.employment_type,
    employment_status: props.employee.employment_status,
    join_date: props.employee.join_date,
    basic_salary: props.employee.basic_salary,
    address: props.employee.address ?? '',
    emergency_contact_name: props.employee.emergency_contact_name ?? '',
    emergency_contact_phone: props.employee.emergency_contact_phone ?? '',
    emergency_contact_relationship: props.employee.emergency_contact_relationship ?? '',
    photo: null as File | null,
});

const filteredPositions = computed(() => props.positions.filter((p) => Number(p.department_id) === Number(form.department_id)));

const photoPreview = ref<string | null>(props.employee.photo_url);

function onPhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : props.employee.photo_url;
}

const submit = () => form.post(route('hris.employees.update', props.employee.id), { forceFormData: true });
</script>

<template>
    <Head :title="`Edit ${employee.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="`Edit ${employee.name}`" :description="employee.employee_number" />

            <form class="grid max-w-3xl gap-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Photo</CardTitle>
                    </CardHeader>
                    <CardContent class="flex items-center gap-4">
                        <div class="flex size-16 items-center justify-center overflow-hidden rounded-full bg-muted">
                            <img v-if="photoPreview" :src="photoPreview" alt="" class="size-full object-cover" />
                            <Upload v-else class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <Input type="file" accept="image/*" @change="onPhotoChange" />
                            <InputError :message="form.errors.photo" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Personal Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid gap-2">
                            <Label for="name">Full name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="form.phone" />
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Textarea id="address" v-model="form.address" rows="2" />
                            <InputError :message="form.errors.address" />
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="grid gap-2">
                                <Label for="emergency_contact_name">Emergency contact</Label>
                                <Input id="emergency_contact_name" v-model="form.emergency_contact_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="emergency_contact_phone">Contact phone</Label>
                                <Input id="emergency_contact_phone" v-model="form.emergency_contact_phone" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="emergency_contact_relationship">Relationship</Label>
                                <Input id="emergency_contact_relationship" v-model="form.emergency_contact_relationship" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Employment Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="department_id">Department</Label>
                                <SearchableSelect
                                    v-model="form.department_id"
                                    :options="departments.map((d) => ({ value: d.id, label: d.name }))"
                                    @update:model-value="form.position_id = ''"
                                />
                                <InputError :message="form.errors.department_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="position_id">Position</Label>
                                <SearchableSelect
                                    v-model="form.position_id"
                                    :options="filteredPositions.map((p) => ({ value: p.id, label: p.name }))"
                                />
                                <InputError :message="form.errors.position_id" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="manager_id">Manager</Label>
                            <SearchableSelect
                                v-model="form.manager_id"
                                placeholder="No manager"
                                :options="[{ value: '', label: 'No manager' }, ...managers.map((m) => ({ value: m.id, label: m.name }))]"
                            />
                            <InputError :message="form.errors.manager_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="employment_type">Employment type</Label>
                                <SearchableSelect v-model="form.employment_type" :options="employmentTypes" />
                                <InputError :message="form.errors.employment_type" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="employment_status">Employment status</Label>
                                <SearchableSelect v-model="form.employment_status" :options="employmentStatuses" />
                                <InputError :message="form.errors.employment_status" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="join_date">Join date</Label>
                                <Input id="join_date" v-model="form.join_date" type="date" />
                                <InputError :message="form.errors.join_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="basic_salary">Basic salary (IDR)</Label>
                                <Input id="basic_salary" v-model="form.basic_salary" type="number" min="0" />
                                <InputError :message="form.errors.basic_salary" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" type="button" as-child>
                        <Link :href="route('hris.employees.show', employee.id)">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
