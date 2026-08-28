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

interface SupplierRow {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
}

defineProps<{ suppliers: SupplierRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers', href: '/erp/suppliers' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('supplier.manage');

const showDialog = ref(false);
const editing = ref<SupplierRow | null>(null);
const form = useForm({ name: '', contact_person: '', phone: '', email: '', address: '', is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(supplier: SupplierRow) {
    editing.value = supplier;
    form.name = supplier.name;
    form.contact_person = supplier.contact_person ?? '';
    form.phone = supplier.phone ?? '';
    form.email = supplier.email ?? '';
    form.address = supplier.address ?? '';
    form.is_active = supplier.is_active;
    form.clearErrors();
    showDialog.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('erp.suppliers.update', editing.value.id), { onSuccess: () => (showDialog.value = false) });
    } else {
        form.post(route('erp.suppliers.store'), { onSuccess: () => (showDialog.value = false) });
    }
}
</script>

<template>
    <Head title="Suppliers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Suppliers" description="Vendors used for purchasing.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        Add Supplier
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
                    <TableEmpty v-if="suppliers.length === 0" :colspan="6">No suppliers defined yet.</TableEmpty>
                    <TableRow v-for="supplier in suppliers" :key="supplier.id">
                        <TableCell class="font-medium">{{ supplier.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ supplier.contact_person ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ supplier.phone ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ supplier.email ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="supplier.is_active ? 'success' : 'outline'">{{ supplier.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </TableCell>
                        <TableCell v-if="canManage">
                            <Button variant="ghost" size="icon" @click="openEdit(supplier)">
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
                    <DialogTitle>{{ editing ? 'Edit Supplier' : 'New Supplier' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. PT Sumber Makmur" />
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
