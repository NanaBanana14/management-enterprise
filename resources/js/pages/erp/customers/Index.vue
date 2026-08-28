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
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface CustomerRow {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
}

defineProps<{ customers: CustomerRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/erp/customers' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('customer.manage');

const showDialog = ref(false);
const editing = ref<CustomerRow | null>(null);
const form = useForm({ name: '', contact_person: '', phone: '', email: '', address: '', is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(customer: CustomerRow) {
    editing.value = customer;
    form.name = customer.name;
    form.contact_person = customer.contact_person ?? '';
    form.phone = customer.phone ?? '';
    form.email = customer.email ?? '';
    form.address = customer.address ?? '';
    form.is_active = customer.is_active;
    form.clearErrors();
    showDialog.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('erp.customers.update', editing.value.id), { onSuccess: () => (showDialog.value = false) });
    } else {
        form.post(route('erp.customers.store'), { onSuccess: () => (showDialog.value = false) });
    }
}
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Customers" description="Buyers used for sales.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        Add Customer
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Contact</TableHead>
                        <TableHead>Phone</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canManage" class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="customers.length === 0" :colspan="6">No customers defined yet.</TableEmpty>
                    <TableRow v-for="customer in customers" :key="customer.id">
                        <TableCell class="font-medium">{{ customer.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ customer.contact_person ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ customer.phone ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ customer.email ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="customer.is_active ? 'success' : 'outline'">{{ customer.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </TableCell>
                        <TableCell v-if="canManage">
                            <Button variant="ghost" size="icon" @click="openEdit(customer)">
                                <Pencil class="size-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Edit Customer' : 'New Customer' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. PT Cahaya Abadi" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="contact_person">Contact person</Label>
                            <Input id="contact_person" v-model="form.contact_person" />
                            <InputError :message="form.errors.contact_person" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input id="address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            {{ editing ? 'Save' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
