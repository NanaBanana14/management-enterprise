<script setup lang="ts">
import GroupedBarChart from '@/components/charts/GroupedBarChart.vue';
import HorizontalBarChart from '@/components/charts/HorizontalBarChart.vue';
import TrendAreaChart from '@/components/charts/TrendAreaChart.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownToLine,
    ArrowLeftRight,
    ArrowUpFromLine,
    Ban,
    Boxes,
    CalendarCheck,
    CalendarClock,
    CheckCircle2,
    Clock,
    Clock3,
    FileText,
    GraduationCap,
    PackageSearch,
    PiggyBank,
    ReceiptText,
    ShieldCheck,
    ShoppingCart,
    UserCheck,
    Users,
    UserSearch,
    Wallet,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface HrisStats {
    activeEmployees: number;
    pendingLeave: number;
    pendingOvertime: number;
    attendanceTrend: { date: string; present: number; absent: number }[];
    leaveStatusBreakdown: { status: string; count: number }[];
    recentLeaveRequests: { employee: string; type: string; days: number; status: string }[] | null;
    recruitmentSummary: { openVacancies: number; activeApplicants: number } | null;
    performanceSummary: { periodName: string | null; submitted: number; total: number } | null;
    trainingSummary: { activeEnrollments: number; completedEnrollments: number } | null;
}

interface FinanceStats {
    cashBankBalance: number;
    receivableOutstanding: number;
    payableOutstanding: number;
    receivableOverdue: number;
    payableOverdue: number;
    cashFlowTrend: { month: string; income: number; expense: number }[];
    recentTransactions: { reference: string; description: string | null; date: string; total: number }[];
}

interface ErpStats {
    totalProducts: number;
    lowStockProducts: number;
    draftPurchaseOrders: number | null;
    draftSalesOrders: number | null;
    topProductsByStock: { name: string; quantity: number }[];
    recentStockMovements: { product: string; warehouse: string; type: string; quantity: number }[];
}

interface PlatformStats {
    stats: {
        totalUsers: number;
        activeUsers: number;
        totalRoles: number;
    };
    usersByRole: { role: string; count: number }[];
}

interface MyOverview {
    presentThisMonth: number;
    leaveBalance: number;
    latestPayslip: { netSalary: number; status: string } | null;
}

const props = defineProps<{
    hris: HrisStats | null;
    finance: FinanceStats | null;
    erp: ErpStats | null;
    platform: PlatformStats | null;
    me: MyOverview | null;
}>();

const hasAnySection = computed(() => Boolean(props.hris || props.finance || props.erp || props.platform || props.me));

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

function formatCompactCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', notation: 'compact', maximumFractionDigits: 1 }).format(value);
}

function formatShortDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
}

const attendancePoints = computed(
    () => props.hris?.attendanceTrend.map((d) => ({ label: formatShortDate(d.date), value: d.present })) ?? [],
);

const cashFlowGroups = computed(
    () => props.finance?.cashFlowTrend.map((m) => ({ label: m.month, values: [m.income, m.expense] })) ?? [],
);

const cashFlowSeries = [
    { label: 'Income', color: '#059669' },
    { label: 'Expense', color: '#94a3b8' },
];

const stockBars = computed(() => props.erp?.topProductsByStock.map((p) => ({ label: p.name, value: p.quantity })) ?? []);

const leaveStatusMeta: Record<string, { label: string; color: string; icon: typeof Clock }> = {
    pending: { label: 'Pending', color: '#f59e0b', icon: Clock },
    approved: { label: 'Approved', color: '#10b981', icon: CheckCircle2 },
    rejected: { label: 'Rejected', color: '#ef4444', icon: XCircle },
    cancelled: { label: 'Cancelled', color: '#94a3b8', icon: Ban },
};

const leaveStatusBadge: Record<string, 'warning' | 'success' | 'destructive' | 'outline'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'destructive',
    cancelled: 'outline',
};

const leaveBreakdown = computed(() => {
    const counts = new Map(props.hris?.leaveStatusBreakdown.map((r) => [r.status, r.count]) ?? []);
    const order = ['pending', 'approved', 'rejected', 'cancelled'];
    const total = order.reduce((sum, key) => sum + (counts.get(key) ?? 0), 0);

    return order.map((key) => ({
        key,
        ...leaveStatusMeta[key],
        count: counts.get(key) ?? 0,
        percent: total ? ((counts.get(key) ?? 0) / total) * 100 : 0,
    }));
});

const movementIcon: Record<string, typeof ArrowDownToLine> = {
    in: ArrowDownToLine,
    out: ArrowUpFromLine,
    transfer_in: ArrowLeftRight,
    transfer_out: ArrowLeftRight,
};

const movementLabel: Record<string, string> = {
    in: 'Stock in',
    out: 'Stock out',
    transfer_in: 'Transfer in',
    transfer_out: 'Transfer out',
};

const payslipStatusBadge: Record<string, 'warning' | 'success' | 'outline'> = {
    draft: 'warning',
    approved: 'success',
    paid: 'outline',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-8 p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Overview</h1>
                <p class="text-sm text-muted-foreground">A snapshot across HRIS, Finance, and ERP.</p>
            </div>

            <div v-if="me" class="space-y-3">
                <h2 class="text-sm font-medium text-muted-foreground">My Snapshot</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link href="/hris/attendance">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Present This Month</CardTitle>
                                <CalendarCheck class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ me.presentThisMonth }} days</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/hris/leave">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Leave Balance</CardTitle>
                                <CalendarClock class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ me.leaveBalance }} days</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/hris/payroll">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Latest Payslip</CardTitle>
                                <Wallet class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent v-if="me.latestPayslip" class="flex items-center justify-between">
                                <div class="text-2xl font-semibold">{{ formatCurrency(me.latestPayslip.netSalary) }}</div>
                                <Badge :variant="payslipStatusBadge[me.latestPayslip.status] ?? 'outline'">{{ me.latestPayslip.status }}</Badge>
                            </CardContent>
                            <CardContent v-else>
                                <p class="text-sm text-muted-foreground">No payslip yet.</p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>

            <div v-if="hris" class="space-y-3">
                <h2 class="text-sm font-medium text-muted-foreground">HRIS</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link href="/hris/employees">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Active Employees</CardTitle>
                                <Users class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.activeEmployees }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/hris/leave">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Pending Leave</CardTitle>
                                <CalendarClock class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.pendingLeave }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/hris/overtime">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Pending Overtime</CardTitle>
                                <Clock3 class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.pendingOvertime }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <div v-if="hris.recruitmentSummary || hris.performanceSummary || hris.trainingSummary" class="grid gap-4 sm:grid-cols-3">
                    <Link v-if="hris.recruitmentSummary" href="/hris/recruitment/vacancies">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Recruitment</CardTitle>
                                <UserSearch class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.recruitmentSummary.openVacancies }} open</div>
                                <p class="text-xs text-muted-foreground">{{ hris.recruitmentSummary.activeApplicants }} applicants in pipeline</p>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link v-if="hris.performanceSummary" href="/hris/performance/periods">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Performance Reviews</CardTitle>
                                <ShieldCheck class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.performanceSummary.submitted }} / {{ hris.performanceSummary.total }}</div>
                                <p class="text-xs text-muted-foreground">
                                    {{ hris.performanceSummary.periodName ?? 'No active period' }}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link v-if="hris.trainingSummary" href="/hris/training">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Training</CardTitle>
                                <GraduationCap class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ hris.trainingSummary.activeEnrollments }} active</div>
                                <p class="text-xs text-muted-foreground">{{ hris.trainingSummary.completedEnrollments }} completed</p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle class="text-base">Attendance — last 14 working days</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <TrendAreaChart v-if="attendancePoints.length > 0" :data="attendancePoints" color="#059669" />
                            <p v-else class="text-sm text-muted-foreground">No attendance records yet.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Leave Requests by Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="item in leaveBreakdown" :key="item.key" class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 font-medium text-foreground">
                                        <component :is="item.icon" class="size-3.5" :style="{ color: item.color }" />
                                        {{ item.label }}
                                    </span>
                                    <span class="text-muted-foreground">{{ item.count }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${item.percent}%`, backgroundColor: item.color }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-if="hris.recentLeaveRequests">
                    <CardHeader>
                        <CardTitle class="text-base">Recent Leave Requests</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div
                            v-for="(item, index) in hris.recentLeaveRequests"
                            :key="index"
                            class="flex items-center justify-between border-b py-2 text-sm last:border-b-0"
                        >
                            <div>
                                <div class="font-medium">{{ item.employee }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.type }} · {{ item.days }} day(s)</div>
                            </div>
                            <Badge :variant="leaveStatusBadge[item.status] ?? 'outline'">{{ item.status }}</Badge>
                        </div>
                        <p v-if="hris.recentLeaveRequests.length === 0" class="py-2 text-sm text-muted-foreground">No leave requests yet.</p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="finance" class="space-y-3">
                <h2 class="text-sm font-medium text-muted-foreground">Finance</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link href="/finance/cashbank">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Cash & Bank</CardTitle>
                                <PiggyBank class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ formatCurrency(finance.cashBankBalance) }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/finance/invoices">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Receivable (AR)</CardTitle>
                                <ReceiptText class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ formatCurrency(finance.receivableOutstanding) }}</div>
                                <p v-if="finance.receivableOverdue > 0" class="mt-1 text-xs font-medium text-destructive">
                                    {{ finance.receivableOverdue }} overdue
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/finance/payables">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Payable (AP)</CardTitle>
                                <FileText class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ formatCurrency(finance.payableOutstanding) }}</div>
                                <p v-if="finance.payableOverdue > 0" class="mt-1 text-xs font-medium text-destructive">
                                    {{ finance.payableOverdue }} overdue
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle class="text-base">Cash Flow — last 6 months</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <GroupedBarChart
                                v-if="cashFlowGroups.length > 0"
                                :data="cashFlowGroups"
                                :series="cashFlowSeries"
                                :value-formatter="formatCompactCurrency"
                            />
                            <p v-else class="text-sm text-muted-foreground">No journal activity yet.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Recent Transactions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <div
                                v-for="(tx, index) in finance.recentTransactions"
                                :key="index"
                                class="flex items-center justify-between border-b py-2 text-sm last:border-b-0"
                            >
                                <div class="truncate pr-2">
                                    <div class="truncate font-medium">{{ tx.description ?? tx.reference }}</div>
                                    <div class="text-xs text-muted-foreground">{{ tx.date }}</div>
                                </div>
                                <div class="shrink-0 text-right text-xs font-medium">{{ formatCompactCurrency(tx.total) }}</div>
                            </div>
                            <p v-if="finance.recentTransactions.length === 0" class="py-2 text-sm text-muted-foreground">No transactions yet.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="erp" class="space-y-3">
                <h2 class="text-sm font-medium text-muted-foreground">ERP</h2>
                <div class="grid gap-4 sm:grid-cols-4">
                    <Link href="/erp/products">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Products</CardTitle>
                                <PackageSearch class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ erp.totalProducts }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/erp/inventory">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Low Stock</CardTitle>
                                <AlertTriangle class="size-4" :class="erp.lowStockProducts > 0 ? 'text-amber-500' : 'text-muted-foreground'" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ erp.lowStockProducts }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link v-if="erp.draftPurchaseOrders !== null" href="/erp/purchase-orders">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Draft POs</CardTitle>
                                <ShoppingCart class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ erp.draftPurchaseOrders }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link v-if="erp.draftSalesOrders !== null" href="/erp/sales-orders">
                        <Card class="transition-colors hover:bg-muted/40">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Draft SOs</CardTitle>
                                <Boxes class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold">{{ erp.draftSalesOrders }}</div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle class="text-base">Stock by Product</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <HorizontalBarChart :data="stockBars" color="#059669" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Recent Stock Movements</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <div
                                v-for="(m, index) in erp.recentStockMovements"
                                :key="index"
                                class="flex items-center justify-between border-b py-2 text-sm last:border-b-0"
                            >
                                <div class="flex items-center gap-2 truncate pr-2">
                                    <component :is="movementIcon[m.type] ?? ArrowLeftRight" class="size-3.5 shrink-0 text-muted-foreground" />
                                    <div class="truncate">
                                        <div class="truncate font-medium">{{ m.product }}</div>
                                        <div class="text-xs text-muted-foreground">{{ movementLabel[m.type] ?? m.type }} · {{ m.warehouse }}</div>
                                    </div>
                                </div>
                                <div class="shrink-0 text-xs font-medium">{{ m.quantity }}</div>
                            </div>
                            <p v-if="erp.recentStockMovements.length === 0" class="py-2 text-sm text-muted-foreground">No stock movements yet.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="platform" class="space-y-3">
                <h2 class="text-sm font-medium text-muted-foreground">Platform</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
                            <Users class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ platform.stats.totalUsers }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Active Users</CardTitle>
                            <UserCheck class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ platform.stats.activeUsers }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Roles Defined</CardTitle>
                            <ShieldCheck class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ platform.stats.totalRoles }}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Users by Role</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="item in platform.usersByRole" :key="item.role" class="flex items-center gap-3">
                            <span class="w-40 shrink-0 truncate text-sm">{{ item.role }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{ width: `${platform.stats.totalUsers ? (item.count / platform.stats.totalUsers) * 100 : 0}%` }"
                                />
                            </div>
                            <span class="w-6 shrink-0 text-right text-sm text-muted-foreground">{{ item.count }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="!hasAnySection" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                Nothing to show here yet. Use the sidebar to get to the areas you have access to.
            </div>
        </div>
    </AppLayout>
</template>
