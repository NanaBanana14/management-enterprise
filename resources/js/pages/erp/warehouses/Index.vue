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

interface WarehouseRow {
    id: number;
    code: string;
    name: string;
    address: string | null;
    is_active: boolean;
}

defineProps<{ warehouses: WarehouseRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warehouses', href: '/erp/warehouses' },
];

const page = usePage<SharedData>();
const canManage = page.props.auth.permissions.includes('warehouse.manage');

const showDialog = ref(false);
const editing = ref<WarehouseRow | null>(null);
const form = useForm({ code: '', name: '', address: '', is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(warehouse: WarehouseRow) {
    editing.value = warehouse;
    form.code = warehouse.code;
    form.name = warehouse.name;
    form.address = warehouse.address ?? '';
    form.is_active = warehouse.is_active;
    form.clearErrors();
    showDialog.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('erp.warehouses.update', editing.value.id), { onSuccess: () => (showDialog.value = false) });
    } else {
        form.post(route('erp.warehouses.store'), { onSuccess: () => (showDialog.value = false) });
    }
}
</script>

<template>
    <Head title="Warehouses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="Warehouses" description="Locations that hold inventory.">
                <template #actions>
                    <Button v-if="canManage" @click="openCreate">
                        <Plus class="size-4" />
                        Add Warehouse
                    </Button>
                </template>
            </PageHeader>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Address</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead v-if="canManage" class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="warehouses.length === 0" :colspan="5">No warehouses defined yet.</TableEmpty>
                    <TableRow v-for="warehouse in warehouses" :key="warehouse.id">
                        <TableCell class="font-medium">{{ warehouse.code }}</TableCell>
                        <TableCell>{{ warehouse.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ warehouse.address ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="warehouse.is_active ? 'success' : 'outline'">{{ warehouse.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </TableCell>
                        <TableCell v-if="canManage">
                            <Button variant="ghost" size="icon" @click="openEdit(warehouse)">
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
                    <DialogTitle>{{ editing ? 'Edit Warehouse' : 'New Warehouse' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="code">Code</Label>
                        <Input id="code" v-model="form.code" placeholder="e.g. WH-01" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Main Warehouse" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input id="address" v-model="form.address" placeholder="Optional" />
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
