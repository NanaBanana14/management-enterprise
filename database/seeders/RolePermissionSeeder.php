<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions grouped by module. Modules beyond Users/Roles/Audit Log are
     * seeded now so role assignment is complete, even though the HRIS/Finance/
     * ERP screens that enforce them land in later phases.
     */
    private const PERMISSIONS = [
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'create', 'update', 'delete'],
        'audit' => ['view'],

        'employee' => ['view', 'create', 'update', 'delete'],
        'department' => ['view', 'manage'],
        'position' => ['view', 'manage'],
        'attendance' => ['view', 'manage'],
        'leave' => ['view', 'create', 'approve'],
        'overtime' => ['view', 'create', 'approve'],
        'payroll' => ['view', 'process', 'approve'],
        'kpi' => ['view', 'manage'],
        'performance' => ['view', 'manage'],
        'recruitment' => ['view', 'manage'],
        'training' => ['view', 'manage'],

        'account' => ['view', 'manage'],
        'journal' => ['view', 'create', 'approve'],
        'cashbank' => ['view', 'manage'],
        'income' => ['view', 'manage'],
        'expense' => ['view', 'manage'],
        'invoice' => ['view', 'create', 'approve'],
        'payable' => ['view', 'manage'],
        'report' => ['view'],

        'product' => ['view', 'manage'],
        'warehouse' => ['view', 'manage'],
        'inventory' => ['view', 'adjust', 'transfer'],
        'supplier' => ['view', 'manage'],
        'customer' => ['view', 'manage'],
        'purchase' => ['view', 'create', 'approve'],
        'sales' => ['view', 'create', 'approve'],

        'opportunity' => ['view', 'manage'],

        'asset' => ['view', 'create', 'manage'],
    ];

    private const ROLE_PERMISSIONS = [
        'Super Admin' => ['*'],

        'HR Manager' => [
            'users.view',
            'employee.*', 'department.*', 'position.*', 'attendance.*',
            'leave.*', 'overtime.*', 'kpi.*', 'performance.*', 'recruitment.*', 'training.*',
            'payroll.view', 'payroll.process',
            'audit.view',
        ],

        'HR Staff' => [
            'employee.view', 'employee.create', 'employee.update',
            'department.view', 'position.view',
            'attendance.manage',
            'leave.view', 'leave.create',
            'overtime.view', 'overtime.create',
            'training.view',
        ],

        'Finance Manager' => [
            'account.*', 'journal.*', 'cashbank.*', 'income.*', 'expense.*',
            'invoice.*', 'payable.*', 'report.view',
            'payroll.view', 'payroll.approve',
            'opportunity.view',
            'asset.view', 'asset.manage',
        ],

        'Finance Staff' => [
            'account.view',
            'journal.view', 'journal.create',
            'cashbank.manage', 'income.manage', 'expense.manage',
            'invoice.view', 'invoice.create',
            'payable.view',
        ],

        'Warehouse Manager' => [
            'warehouse.*', 'inventory.*', 'product.view',
            'asset.view', 'asset.create',
        ],

        'Purchasing Staff' => [
            'purchase.view', 'purchase.create',
            'supplier.view', 'product.view', 'inventory.view',
        ],

        'Sales Staff' => [
            'sales.view', 'sales.create',
            'customer.view', 'product.view', 'inventory.view',
            'opportunity.*',
        ],

        'Employee' => [
            'attendance.view',
            'leave.view', 'leave.create',
            'overtime.view', 'overtime.create',
            'payroll.view',
            'training.view',
        ],
    ];

    public function run(): void
    {
        $names = [];

        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        foreach ($names as $name) {
            Permission::findOrCreate($name);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $patterns) {
            $role = Role::findOrCreate($roleName);

            if ($patterns === ['*']) {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $resolved = collect($patterns)->flatMap(function ($pattern) use ($names) {
                if (! str_ends_with($pattern, '.*')) {
                    return [$pattern];
                }

                $prefix = substr($pattern, 0, -1);

                return array_filter($names, fn ($name) => str_starts_with($name, $prefix));
            })->unique()->values()->all();

            $role->syncPermissions($resolved);
        }
    }
}
