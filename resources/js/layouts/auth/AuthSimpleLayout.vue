<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { BarChart3, ShieldCheck, Users } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <div class="relative hidden w-[44%] flex-col justify-between overflow-hidden bg-emerald-700 p-10 text-white md:flex lg:p-14">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.08]"
                style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px"
            />
            <div class="pointer-events-none absolute -right-28 -top-28 size-96 rounded-full bg-emerald-400/30 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-36 -left-20 size-96 rounded-full bg-emerald-950/50 blur-3xl" />

            <Link :href="route('home')" class="relative z-10 flex items-center gap-2.5 font-medium">
                <div class="flex size-9 items-center justify-center rounded-md bg-white/15">
                    <AppLogoIcon class="size-5 text-white" />
                </div>
                <span class="text-lg font-semibold tracking-tight">MENTER</span>
            </Link>

            <div class="relative z-10 space-y-6">
                <h2 class="text-2xl font-semibold leading-snug lg:text-3xl">
                    HRIS, Finance, and ERP&nbsp;— working as one system.
                </h2>
                <p class="max-w-sm text-sm text-emerald-100">
                    Employees, attendance, payroll, and inventory stay connected instead of living in separate spreadsheets.
                </p>

                <div class="flex flex-col gap-3 pt-2">
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-white/10">
                            <Users class="size-4" />
                        </span>
                        Unified employee &amp; payroll records
                    </div>
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-white/10">
                            <BarChart3 class="size-4" />
                        </span>
                        Real-time inventory &amp; financial reporting
                    </div>
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-white/10">
                            <ShieldCheck class="size-4" />
                        </span>
                        Role-based access on every action
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-xs text-emerald-200">© {{ new Date().getFullYear() }} MENTER. Internal business platform.</p>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col gap-4 md:hidden">
                        <Link :href="route('home')" class="flex items-center gap-2.5 font-medium">
                            <div class="flex size-9 items-center justify-center rounded-md bg-emerald-600 text-white">
                                <AppLogoIcon class="size-5 text-white" />
                            </div>
                            <span class="text-lg font-semibold tracking-tight">MENTER</span>
                        </Link>
                    </div>
                    <div class="space-y-1.5">
                        <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
