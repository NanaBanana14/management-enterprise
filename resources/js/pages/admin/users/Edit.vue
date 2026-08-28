<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    user: { id: number; name: string; email: string; is_active: boolean };
    currentRole?: string;
    roles: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}/edit` },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.currentRole ?? '',
    is_active: props.user.is_active,
});

const submit = () => form.put(route('admin.users.update', props.user.id));
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader :title="`Edit ${user.name}`" description="Update account details and role." />

            <Card class="max-w-xl">
                <CardContent class="pt-6">
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Full name</Label>
                            <Input id="name" v-model="form.name" autocomplete="name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" autocomplete="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="role">Role</Label>
                            <Select id="role" v-model="form.role">
                                <option value="" disabled>Select a role</option>
                                <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                            </Select>
                            <InputError :message="form.errors.role" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" placeholder="Leave blank to keep current password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm new password</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                        </div>

                        <Label class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_active" />
                            <span>Active account</span>
                        </Label>

                        <div class="flex justify-end gap-2">
                            <Button variant="outline" type="button" as-child>
                                <Link :href="route('admin.users.index')">Cancel</Link>
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
