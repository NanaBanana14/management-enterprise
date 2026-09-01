<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, type LucideIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
        icon?: LucideIcon;
    }>(),
    { label: 'Platform' },
);

const page = usePage<SharedData>();

const storageKey = `sidebar-group-open:${props.label}`;

function readStoredOpen(): boolean {
    try {
        const stored = localStorage.getItem(storageKey);
        return stored === null ? true : stored === '1';
    } catch {
        return true;
    }
}

const open = ref(readStoredOpen());

watch(open, (value) => {
    try {
        localStorage.setItem(storageKey, value ? '1' : '0');
    } catch {
        // ignore write failures (private browsing, storage disabled, etc.)
    }
});

const { state: sidebarState } = useSidebar();
const isIconMode = computed(() => sidebarState.value === 'collapsed');
const effectiveOpen = computed(() => (isIconMode.value ? true : open.value));
</script>

<template>
    <SidebarGroup v-if="items.length" class="px-2 py-0">
        <Collapsible :open="effectiveOpen" @update:open="open = $event">
            <CollapsibleTrigger
                class="group/trigger flex w-full items-center gap-2 rounded-md px-2 py-1.5 outline-none transition-colors hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring group-data-[collapsible=icon]:hidden"
            >
                <span
                    v-if="icon"
                    class="flex size-5 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary transition-colors group-hover/trigger:bg-primary/15"
                >
                    <component :is="icon" class="size-3.5" />
                </span>
                <SidebarGroupLabel class="h-auto flex-1 p-0 text-left">{{ label }}</SidebarGroupLabel>
                <ChevronRight class="size-3.5 shrink-0 text-sidebar-foreground/50 transition-transform duration-200 group-data-[state=open]/trigger:rotate-90" />
            </CollapsibleTrigger>
            <CollapsibleContent>
                <SidebarMenu class="mt-0.5">
                    <SidebarMenuItem v-for="item in items" :key="item.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="item.href === page.url"
                            class="data-[active=true]:border-l-2 data-[active=true]:border-primary data-[active=true]:bg-primary/10 data-[active=true]:pl-[calc(0.5rem-2px)] data-[active=true]:font-medium data-[active=true]:text-primary [&[data-active=true]_svg]:text-primary"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </CollapsibleContent>
        </Collapsible>
    </SidebarGroup>
</template>
