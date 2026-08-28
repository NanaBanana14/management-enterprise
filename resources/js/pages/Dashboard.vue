<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ShieldCheck, UserCheck, Users } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

defineProps<{
    stats: {
        totalUsers: number;
        activeUsers: number;
        totalRoles: number;
    };
    usersByRole: { role: string; count: number }[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Overview</h1>
                <p class="text-sm text-muted-foreground">Foundation metrics — HRIS, Finance, and ERP dashboards land as those modules ship.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
                        <Users class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.totalUsers }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Active Users</CardTitle>
                        <UserCheck class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.activeUsers }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Roles Defined</CardTitle>
                        <ShieldCheck class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.totalRoles }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Users by Role</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="item in usersByRole" :key="item.role" class="flex items-center gap-3">
                        <span class="w-40 shrink-0 truncate text-sm">{{ item.role }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${stats.totalUsers ? (item.count / stats.totalUsers) * 100 : 0}%` }"
                            />
                        </div>
                        <span class="w-6 shrink-0 text-right text-sm text-muted-foreground">{{ item.count }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
