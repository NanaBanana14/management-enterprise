<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenText,
    Boxes,
    Briefcase,
    Building2,
    CalendarCheck,
    CalendarClock,
    Clock3,
    Contact,
    FileBarChart,
    FileText,
    GraduationCap,
    LayoutGrid,
    Landmark,
    Package,
    PiggyBank,
    ReceiptText,
    ScrollText,
    ShieldCheck,
    ShoppingCart,
    Target,
    Truck,
    TrendingUp,
    UserSearch,
    Users,
    Wallet,
    Warehouse,
} from 'lucide-vue-next';
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

const financeNavItems = computed<NavItem[]>(() =>
    (
        [
            { title: 'Chart of Accounts', href: '/finance/accounts', icon: Landmark, permission: 'account.view' },
            { title: 'Journal Entries', href: '/finance/journal', icon: BookOpenText, permission: 'journal.view' },
            { title: 'Cash & Bank', href: '/finance/cashbank', icon: PiggyBank, permission: 'cashbank.view' },
            { title: 'Invoices (AR)', href: '/finance/invoices', icon: ReceiptText, permission: 'invoice.view' },
            { title: 'Payables (AP)', href: '/finance/payables', icon: FileText, permission: 'payable.view' },
            { title: 'Reports', href: '/finance/reports', icon: FileBarChart, permission: 'report.view' },
        ] satisfies NavItem[]
    ).filter(canSee),
);

const erpNavItems = computed<NavItem[]>(() =>
    (
        [
            { title: 'Products', href: '/erp/products', icon: Package, permission: 'product.view' },
            { title: 'Warehouses', href: '/erp/warehouses', icon: Warehouse, permission: 'warehouse.view' },
            { title: 'Inventory', href: '/erp/inventory', icon: Boxes, permission: 'inventory.view' },
            { title: 'Suppliers', href: '/erp/suppliers', icon: Truck, permission: 'supplier.view' },
            { title: 'Customers', href: '/erp/customers', icon: Contact, permission: 'customer.view' },
            { title: 'Purchase Orders', href: '/erp/purchase-orders', icon: ShoppingCart, permission: 'purchase.view' },
            { title: 'Sales Orders', href: '/erp/sales-orders', icon: ReceiptText, permission: 'sales.view' },
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
            <NavMain :items="financeNavItems" label="Finance" />
            <NavMain :items="erpNavItems" label="ERP" />
            <NavMain :items="administrationNavItems" label="Administration" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
