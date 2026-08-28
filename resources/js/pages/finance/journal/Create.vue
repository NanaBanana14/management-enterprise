<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    accounts: { id: number; code: string; name: string }[];
    nextReference: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal', href: '/finance/journal' },
    { title: 'New Entry', href: '/finance/journal/create' },
];

const accountOptions = props.accounts.map((a) => ({ value: a.id, label: `${a.code} — ${a.name}` }));

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    reference: props.nextReference,
    description: '',
    lines: [
        { account_id: '' as number | '', debit: '', credit: '', memo: '' },
        { account_id: '' as number | '', debit: '', credit: '', memo: '' },
    ],
});

function addLine() {
    form.lines.push({ account_id: '', debit: '', credit: '', memo: '' });
}

function removeLine(index: number) {
    if (form.lines.length <= 2) return;
    form.lines.splice(index, 1);
}

const totalDebit = computed(() => form.lines.reduce((sum, l) => sum + (Number(l.debit) || 0), 0));
const totalCredit = computed(() => form.lines.reduce((sum, l) => sum + (Number(l.credit) || 0), 0));
const balanced = computed(() => totalDebit.value === totalCredit.value && totalDebit.value > 0);

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const submit = () => form.post(route('finance.journal.store'));
</script>

<template>
    <Head title="New Journal Entry" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <PageHeader title="New Journal Entry" description="Total debit must equal total credit." />

            <form class="flex max-w-4xl flex-col gap-6" @submit.prevent="submit">
                <Card>
                    <CardContent class="grid grid-cols-1 gap-4 pt-6 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="date">Date</Label>
                            <Input id="date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="reference">Reference</Label>
                            <Input id="reference" v-model="form.reference" />
                            <InputError :message="form.errors.reference" />
                        </div>
                        <div class="grid gap-2 sm:col-span-1">
                            <Label for="description">Description</Label>
                            <Textarea id="description" v-model="form.description" rows="1" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="space-y-3 pt-6">
                        <div class="grid grid-cols-[1fr_8rem_8rem_2.5rem] gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <span>Account</span>
                            <span>Debit</span>
                            <span>Credit</span>
                            <span></span>
                        </div>
                        <div v-for="(line, index) in form.lines" :key="index" class="grid grid-cols-[1fr_8rem_8rem_2.5rem] items-start gap-2">
                            <SearchableSelect v-model="line.account_id" placeholder="Select account" :options="accountOptions" />
                            <Input v-model="line.debit" type="number" min="0" step="1000" placeholder="0" />
                            <Input v-model="line.credit" type="number" min="0" step="1000" placeholder="0" />
                            <Button type="button" variant="ghost" size="icon" :disabled="form.lines.length <= 2" @click="removeLine(index)">
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
                        </div>
                        <InputError :message="form.errors.lines" />

                        <Button type="button" variant="outline" size="sm" @click="addLine">
                            <Plus class="size-4" />
                            Add Line
                        </Button>

                        <div class="flex items-center justify-end gap-6 border-t pt-4 text-sm">
                            <span>Total debit: <span class="font-medium">{{ formatCurrency(totalDebit) }}</span></span>
                            <span>Total credit: <span class="font-medium">{{ formatCurrency(totalCredit) }}</span></span>
                            <span :class="balanced ? 'text-emerald-600' : 'text-destructive'" class="font-medium">
                                {{ balanced ? 'Balanced' : 'Not balanced' }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" type="button" as-child>
                        <Link :href="route('finance.journal.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing || !balanced">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Post Entry
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
