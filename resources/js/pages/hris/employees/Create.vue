<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    departments: { id: number; name: string }[];
    positions: { id: number; name: string; department_id: number }[];
    managers: { id: number; name: string }[];
    employmentTypes: { value: string; label: string }[];
    employmentStatuses: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/hris/employees' },
    { title: 'New Employee', href: '/hris/employees/create' },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    department_id: '' as number | '',
    position_id: '' as number | '',
    manager_id: '' as number | '',
    employment_type: 'full_time',
    employment_status: 'active',
    join_date: new Date().toISOString().slice(0, 10),
    basic_salary: '',
    address: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
    photo: null as File | null,
});

const filteredPositions = computed(() => props.positions.filter((p) => Number(p.department_id) === Number(form.department_id)));

const photoPreview = ref<string | null>(null);

function onPhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
}

const submit = () => form.post(route('hris.employees.store'), { forceFormData: true });
</script>

<template>
    <Head title="New Employee" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="New Employee" description="Create an employee record." />

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
                                <Select id="department_id" v-model="form.department_id" @update:model-value="form.position_id = ''">
                                    <option value="" disabled>Select a department</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </Select>
                                <InputError :message="form.errors.department_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="position_id">Position</Label>
                                <Select id="position_id" v-model="form.position_id" :disabled="!form.department_id">
                                    <option value="" disabled>Select a position</option>
                                    <option v-for="p in filteredPositions" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </Select>
                                <InputError :message="form.errors.position_id" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="manager_id">Manager</Label>
                            <Select id="manager_id" v-model="form.manager_id">
                                <option value="">No manager</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </Select>
                            <InputError :message="form.errors.manager_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="employment_type">Employment type</Label>
                                <Select id="employment_type" v-model="form.employment_type">
                                    <option v-for="t in employmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </Select>
                                <InputError :message="form.errors.employment_type" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="employment_status">Employment status</Label>
                                <Select id="employment_status" v-model="form.employment_status">
                                    <option v-for="s in employmentStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </Select>
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
                        <Link :href="route('hris.employees.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Create Employee
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
