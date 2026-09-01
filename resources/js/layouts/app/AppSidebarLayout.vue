<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Info, XCircle } from 'lucide-vue-next';
import { toast, Toaster } from 'vue-sonner';
import { h, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.warning) toast.warning(flash.warning);
        if (flash?.info) toast.info(flash.info);
    },
    { immediate: true, deep: true },
);

// Safety net for actions that have no nearby field to show a validation error next to
// (e.g. Approve, Fulfill, Receive, Close Period — plain router.post() calls with no form).
// Pages using useForm() already render these inline via form.errors; this adds a toast
// on top so an error is never silently invisible.
watch(
    () => page.props.errors,
    (errors) => {
        if (!errors) return;

        const messages = Object.values(errors).filter((message): message is string => Boolean(message));

        if (messages.length === 0) return;

        toast.error(messages.length === 1 ? messages[0] : 'Please check the form for errors.');
    },
    { deep: true },
);

const toastIcons = {
    success: h(CheckCircle2, { class: 'size-5 text-primary' }),
    error: h(XCircle, { class: 'size-5 text-destructive' }),
    warning: h(AlertTriangle, { class: 'size-5 text-amber-500' }),
    info: h(Info, { class: 'size-5 text-blue-500' }),
};

const toastClasses = {
    toast: 'rounded-xl border shadow-lg backdrop-blur-sm px-4 py-3 gap-3 font-sans',
    title: 'text-sm font-medium text-foreground',
    description: 'text-sm text-muted-foreground',
    icon: 'shrink-0',
    success: '!border-l-4 !border-l-primary bg-primary/5',
    error: '!border-l-4 !border-l-destructive bg-destructive/5',
    warning: '!border-l-4 !border-l-amber-500 bg-amber-500/5',
    info: '!border-l-4 !border-l-blue-500 bg-blue-500/5',
    closeButton: '!bg-background !border-border !text-muted-foreground hover:!text-foreground',
};
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster position="top-right" close-button :duration="4000" :icons="toastIcons" :toast-options="{ classes: toastClasses }" />
    </AppShell>
</template>
