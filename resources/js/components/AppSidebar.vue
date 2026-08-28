<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Briefcase, Building2, CalendarCheck, CalendarClock, Clock3, GraduationCap, LayoutGrid, ScrollText, ShieldCheck, Target, TrendingUp, UserSearch, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const permissions = computed(() => page.props.auth.permissions);

function canSee(item: NavItem): boolean {
    return !item.permission || permissions.value.includes(item.permission);
}

const platformNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const hrisNavItems = computed<NavItem[]>(() =>
    (
        [
            { title: 'Employees', href: '/hris/employees', icon: Users, permission: 'employee.view' },
            { title: 'Attendance', href: '/hris/attendance', icon: CalendarCheck, permission: 'attendance.view' },
            { title: 'Leave', href: '/hris/leave', icon: CalendarClock, permission: 'leave.view' },
            { title: 'Overtime', href: '/hris/overtime', icon: Clock3, permission: 'overtime.view' },
            {
                title: 'Payroll',
                href: permissions.value.includes('payroll.process') || permissions.value.includes('payroll.approve')
                    ? '/hris/payroll/periods'
                    : '/hris/payroll',
                icon: Wallet,
                permission: 'payroll.view',
            },
            { title: 'KPIs', href: '/hris/kpis', icon: Target, permission: 'kpi.view' },
            {
                title: 'Performance',
                href: permissions.value.includes('performance.manage') ? '/hris/performance/periods' : '/hris/performance',
                icon: TrendingUp,
                permission: 'performance.view',
            },
            { title: 'Recruitment', href: '/hris/recruitment/vacancies', icon: UserSearch, permission: 'recruitment.view' },
            { title: 'Training', href: '/hris/training', icon: GraduationCap, permission: 'training.view' },
            { title: 'Departments', href: '/hris/departments', icon: Building2, permission: 'department.view' },
            { title: 'Positions', href: '/hris/positions', icon: Briefcase, permission: 'position.view' },
        ] satisfies NavItem[]
    ).filter(canSee),
);

const administrationNavItems = computed<NavItem[]>(() =>
    (
        [
            { title: 'Users', href: '/admin/users', icon: Users, permission: 'users.view' },
            { title: 'Roles & Permissions', href: '/admin/roles', icon: ShieldCheck, permission: 'roles.view' },
            { title: 'Audit Log', href: '/admin/audit-logs', icon: ScrollText, permission: 'audit.view' },
        ] satisfies NavItem[]
    ).filter(canSee),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="platformNavItems" label="Platform" />
            <NavMain :items="hrisNavItems" label="HRIS" />
            <NavMain :items="administrationNavItems" label="Administration" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
