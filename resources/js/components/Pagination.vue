<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}>();
</script>

<template>
    <div v-if="total > 0" class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 sm:flex-row">
        <p class="text-sm text-muted-foreground">Showing {{ from }}–{{ to }} of {{ total }}</p>
        <nav class="flex items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <span
                    v-if="!link.url"
                    class="rounded-md px-3 py-1.5 text-sm text-muted-foreground/50"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-muted"
                    :class="link.active ? 'bg-primary text-primary-foreground hover:bg-primary' : 'text-foreground'"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </nav>
    </div>
</template>
