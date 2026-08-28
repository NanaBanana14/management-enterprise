<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    position: {
        id: number;
        department_id: number;
        name: string;
        code: string;
        description: string | null;
        salary_min: string | null;
        salary_max: string | null;
    };
    departments: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Positions', href: '/hris/positions' },
    { title: props.position.name, href: `/hris/positions/${props.position.id}/edit` },
];

const form = useForm({
    department_id: props.position.department_id,
    name: props.position.name,
    code: props.position.code,
    description: props.position.description ?? '',
    salary_min: props.position.salary_min ?? '',
    salary_max: props.position.salary_max ?? '',
});

const submit = () => form.put(route('hris.positions.update', props.position.id));
</script>

<template>
    <Head :title="`Edit ${position.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="`Edit ${position.name}`" />

            <Card class="max-w-xl">
                <CardContent class="pt-6">
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="department_id">Department</Label>
                            <Select id="department_id" v-model="form.department_id">
                                <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                            </Select>
                            <InputError :message="form.errors.department_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="code">Code</Label>
                            <Input id="code" v-model="form.code" class="uppercase" />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="salary_min">Salary min (IDR)</Label>
                                <Input id="salary_min" v-model="form.salary_min" type="number" min="0" />
                                <InputError :message="form.errors.salary_min" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="salary_max">Salary max (IDR)</Label>
                                <Input id="salary_max" v-model="form.salary_max" type="number" min="0" />
                                <InputError :message="form.errors.salary_max" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea id="description" v-model="form.description" rows="3" />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button variant="outline" type="button" as-child>
                                <Link :href="route('hris.positions.index')">Cancel</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
