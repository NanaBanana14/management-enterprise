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
    <div class="flex min-h-svh bg-[#F0FDF4]">
        <!-- Branding panel -->
        <div class="relative hidden w-[38%] flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-800 via-emerald-700 to-green-600 p-8 text-white md:flex lg:w-[46%] lg:p-14">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px"
            />
            <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 64px 64px" />

            <div class="pointer-events-none absolute -right-24 -top-24 size-80 animate-float-slow rounded-full bg-emerald-400/30 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-32 -left-16 size-96 animate-float-slow rounded-full bg-emerald-950/50 blur-3xl [animation-delay:1.5s]" />
            <div class="pointer-events-none absolute right-10 top-1/3 size-40 rounded-3xl border border-white/10 bg-white/5 backdrop-blur-sm [transform:rotate(18deg)]" />
            <div class="pointer-events-none absolute bottom-24 right-24 size-16 rounded-2xl border border-emerald-300/20 [transform:rotate(-12deg)]" />

            <Link :href="route('home')" class="relative z-10 flex animate-fade-slide-in items-center gap-2.5 font-medium">
                <div class="flex size-9 items-center justify-center rounded-md bg-white/15 backdrop-blur-sm">
                    <AppLogoIcon class="size-5 text-white" />
                </div>
                <span class="text-lg font-semibold tracking-tight">MENTER</span>
            </Link>

            <div class="relative z-10 space-y-6 animate-fade-slide-in [animation-delay:120ms]">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-emerald-200">Welcome back</p>
                    <h2 class="text-2xl font-semibold leading-snug lg:text-3xl">Manage everything.<br />Simply. Efficiently.</h2>
                </div>
                <p class="max-w-sm text-sm text-emerald-100/90">
                    Employees, attendance, payroll, and inventory stay connected instead of living in separate spreadsheets.
                </p>

                <div class="flex flex-col gap-3 pt-2">
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-white/10 bg-white/10 backdrop-blur-sm">
                            <Users class="size-4" />
                        </span>
                        Unified employee &amp; payroll records
                    </div>
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-white/10 bg-white/10 backdrop-blur-sm">
                            <BarChart3 class="size-4" />
                        </span>
                        Real-time inventory &amp; financial reporting
                    </div>
                    <div class="flex items-center gap-3 text-sm text-emerald-50">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-white/10 bg-white/10 backdrop-blur-sm">
                            <ShieldCheck class="size-4" />
                        </span>
                        Role-based access on every action
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-xs text-emerald-200">© {{ new Date().getFullYear() }} MENTER. Internal business platform.</p>
        </div>

        <!-- Form panel -->
        <div class="relative flex flex-1 flex-col items-center justify-center gap-6 overflow-hidden p-6 md:p-10">
            <div class="pointer-events-none absolute -right-32 -top-32 size-80 rounded-full bg-emerald-200/40 blur-3xl md:size-[28rem]" />
            <div class="pointer-events-none absolute -bottom-24 -left-24 size-72 rounded-full bg-emerald-100/60 blur-3xl" />

            <div class="relative z-10 w-full max-w-sm animate-fade-slide-in [animation-delay:80ms]">
                <div class="flex flex-col gap-4 md:hidden">
                    <Link :href="route('home')" class="flex items-center gap-2.5 font-medium">
                        <div class="flex size-9 items-center justify-center rounded-md bg-emerald-600 text-white">
                            <AppLogoIcon class="size-5 text-white" />
                        </div>
                        <span class="text-lg font-semibold tracking-tight text-emerald-900">MENTER</span>
                    </Link>
                </div>

                <div class="rounded-2xl border border-emerald-950/5 bg-white/80 p-8 shadow-[0_8px_40px_-12px_rgba(6,95,70,0.25)] backdrop-blur-sm">
                    <div class="mb-6 space-y-1.5">
                        <h1 class="text-xl font-semibold tracking-tight text-emerald-950">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
