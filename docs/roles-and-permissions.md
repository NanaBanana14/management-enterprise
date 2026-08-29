# Roles & Permissions

[← Back to README](../README.md)

Authorization is implemented with [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission). There are **no custom Policy classes** in the app (`app/Policies` does not exist), so every authorization check is either:

1. **Route-level**, via the `permission:` middleware alias (registered in `bootstrap/app.php`) applied per-route or per-resource-action in `routes/*.php`, or
2. **Controller-level**, via an explicit `abort_unless($request->user()->can('...'), 403)` call for actions that share a route with a view-only action (e.g. `store`/`update` inside a controller whose `index` only requires `.view`).

Frontend UI visibility mirrors this: `HandleInertiaRequests` shares `auth.permissions` (a flat array of permission names) on every request, and Vue pages/components read `page.props.auth.permissions.includes('...')` to conditionally render buttons/menu items. **This is a UX convenience only, not a security boundary.** The server-side `permission:` middleware and `abort_unless` checks are what actually enforce access; hiding a button does not, by itself, block the route.

All permissions and role assignments below are read directly from `database/seeders/RolePermissionSeeder.php`. Nothing here is inferred or aspirational.

## Contents

- [Permission catalogue](#permission-catalogue)
- [Roles](#roles)
- [Full role × permission matrix](#full-role--permission-matrix)
- [Role action flows](#role-action-flows)

---

## Permission catalogue

Permissions follow a `module.action` naming convention. 29 modules, 63 total permissions:

| Module | Actions | Used for |
|---|---|---|
| `users` | view, create, update, delete | Admin → User management |
| `roles` | view, create, update, delete | Admin → Roles & Permissions |
| `audit` | view | Admin → Audit Log |
| `employee` | view, create, update, delete | HRIS → Employees |
| `department` | view, manage | HRIS → Departments |
| `position` | view, manage | HRIS → Positions |
| `attendance` | view, manage | HRIS → Attendance |
| `leave` | view, create, approve | HRIS → Leave |
| `overtime` | view, create, approve | HRIS → Overtime |
| `payroll` | view, process, approve | HRIS → Payroll |
| `kpi` | view, manage | HRIS → KPIs |
| `performance` | view, manage | HRIS → Performance |
| `recruitment` | view, manage | HRIS → Recruitment |
| `training` | view, manage | HRIS → Training |
| `account` | view, manage | Finance → Chart of Accounts |
| `journal` | view, create, approve* | Finance → Journal Entries |
| `cashbank` | view, manage | Finance → Cash & Bank (transfers) |
| `income` | view, manage | Finance → Cash & Bank (record income) |
| `expense` | view, manage | Finance → Cash & Bank (record expense) |
| `invoice` | view, create, approve | Finance → Invoices (AR); `approve` gates "mark paid" |
| `payable` | view, manage | Finance → Payables (AP); `manage` covers create *and* mark-paid |
| `report` | view | Finance → Reports, and gates the Finance section of the Dashboard |
| `product` | view, manage | ERP → Products |
| `warehouse` | view, manage | ERP → Warehouses |
| `inventory` | view, adjust, transfer | ERP → Inventory |
| `supplier` | view, manage | ERP → Suppliers |
| `customer` | view, manage | ERP → Customers |
| `purchase` | view, create, approve | ERP → Purchase Orders; `approve` gates "receive" |
| `sales` | view, create, approve | ERP → Sales Orders; `approve` gates "fulfill" |

\* `journal.approve` is seeded as a permission but no route currently checks it. Journal entries post immediately on `journal.create` (there is no draft/approval workflow for manual journal entries). It exists for forward compatibility; see [Future Development](../README.md#28-future-development).

## Roles

9 roles are seeded, each mapped to one demo user (see [Installation → Demo accounts](../README.md#19-installation)):

| Role | Demo account | Intended persona |
|---|---|---|
| **Super Admin** | `admin@nexa.test` | Full access to every permission (`syncPermissions(Permission::all())`) |
| **HR Manager** | `hr.manager@nexa.test` | Full HRIS module control, read-only Users, payroll processing (not approval), audit log |
| **HR Staff** | `hr.staff@nexa.test` | Day-to-day HR data entry: employees (view/create/update, no delete), attendance management, leave/overtime request review |
| **Finance Manager** | `finance.manager@nexa.test` | Full Finance module control, reports, plus payroll viewing/approval |
| **Finance Staff** | `finance.staff@nexa.test` | Finance data entry: view accounts, create journal entries, manage cash/bank/income/expense, create invoices/view payables |
| **Warehouse Manager** | `warehouse.manager@nexa.test` | Full warehouse & inventory control, read-only Products |
| **Purchasing Staff** | `purchasing.staff@nexa.test` | Create purchase orders, view suppliers/products/inventory |
| **Sales Staff** | `sales.staff@nexa.test` | Create sales orders, view customers/products/inventory |
| **Employee** | `employee@nexa.test` | Employee self-service only: own attendance, own leave/overtime requests, own payroll, training catalogue |

Every demo account uses the password `password` (see `DatabaseSeeder::DEMO_USERS`).

## Full role × permission matrix

Generated from `RolePermissionSeeder::ROLE_PERMISSIONS`. `*` patterns (e.g. `employee.*`) are expanded to every permission under that module that exists in the catalogue above.

| Role | Module | Permissions granted |
|---|---|---|
| **Super Admin** | *(all)* | Every permission in the catalogue |
| **HR Manager** | users | `view` |
| | employee, department, position, attendance, leave, overtime, kpi, performance, recruitment, training | *(all actions)* |
| | payroll | `view`, `process` |
| | audit | `view` |
| **HR Staff** | employee | `view`, `create`, `update` |
| | department, position | `view` |
| | attendance | `manage` |
| | leave | `view`, `create` |
| | overtime | `view`, `create` |
| | training | `view` |
| **Finance Manager** | account, journal, cashbank, income, expense, invoice, payable | *(all actions)* |
| | report | `view` |
| | payroll | `view`, `approve` |
| **Finance Staff** | account | `view` |
| | journal | `view`, `create` |
| | cashbank, income, expense | `manage` |
| | invoice | `view`, `create` |
| | payable | `view` |
| **Warehouse Manager** | warehouse, inventory | *(all actions)* |
| | product | `view` |
| **Purchasing Staff** | purchase | `view`, `create` |
| | supplier, product, inventory | `view` |
| **Sales Staff** | sales | `view`, `create` |
| | customer, product, inventory | `view` |
| **Employee** | attendance, payroll, training | `view` |
| | leave, overtime | `view`, `create` |

Notable gaps (by design, not omission):

- **No role except Super Admin, HR Manager, and Finance Manager can approve/reject leave or overtime, approve payroll, or approve invoices/purchase/sales orders that require the `.approve` suffix.** Approval authority is concentrated at the manager level.
- **Warehouse Manager, Purchasing Staff, and Sales Staff cannot see Finance data** (no `account.*`, `invoice.*`, `payable.*`), even though their actions (receiving a PO, fulfilling an SO) post Finance transactions behind the scenes. They see the operational side only.
- **HR Staff cannot delete employees** (`employee.delete` is HR Manager/Super Admin only) and **cannot approve leave/overtime** despite being able to view and create requests.

## Role action flows

### Super Admin

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: HRIS + Finance + ERP + Platform sections all visible]
    B --> C[Any module]
    C --> D[View / Create / Update / Delete / Approve, unrestricted]
```

### HR Manager

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: HRIS + Platform sections]
    B --> C[Employees]
    C --> C1[Create / View / Update / Delete employee]
    B --> D[Departments & Positions]
    D --> D1[Manage org structure]
    B --> E[Attendance]
    E --> E1[View company-wide log]
    B --> F[Leave / Overtime]
    F --> F1[Review pending requests]
    F --> F2[Approve or Reject]
    B --> G[Payroll]
    G --> G1[Generate payslips for a period]
    G --> G2["Process (cannot approve payslips or close period, Finance Manager only)"]
    B --> H[KPIs / Performance / Recruitment / Training]
    H --> H1[Full management access]
    B --> I[Audit Log]
    I --> I1[View, read-only]
```

### HR Staff

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: HRIS section]
    B --> C[Employees]
    C --> C1[Create / View / Update]
    C -.-> C2[Cannot Delete]
    B --> D[Attendance]
    D --> D1[Manage records]
    B --> E[Leave / Overtime]
    E --> E1[View + Create requests]
    E -.-> E2[Cannot Approve / Reject]
    B --> F[Training]
    F --> F1[View catalogue]
```

### Finance Manager

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: Finance section]
    B --> C[Chart of Accounts]
    C --> C1[View + Manage accounts]
    B --> D[Journal Entries]
    D --> D1[Create manual journal entries]
    B --> E[Cash and Bank]
    E --> E1[Record income / expense / transfer]
    B --> F[Invoices AR]
    F --> F1[Create invoice]
    F --> F2[Mark paid]
    B --> G[Payables AP]
    G --> G1[Create payable]
    G --> G2[Mark paid]
    B --> H[Reports]
    H --> H1[View P&L and Balance Sheet]
    B --> I[Payroll]
    I --> I1["Approve payslips + close period (posts payroll journal entry)"]
```

### Finance Staff

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: Finance section]
    B --> C[Chart of Accounts]
    C --> C1[View only]
    B --> D[Journal Entries]
    D --> D1[View + Create]
    B --> E[Cash and Bank]
    E --> E1[Record income / expense / transfer]
    B --> F[Invoices AR]
    F --> F1[Create invoice]
    F -.-> F2[Cannot mark paid]
    B --> G[Payables AP]
    G --> G1[View only]
    G -.-> G2[Cannot create or mark paid]
```

### Warehouse Manager

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: ERP section]
    B --> C[Warehouses]
    C --> C1[Create / Update warehouses]
    B --> D[Inventory]
    D --> D1[Adjust stock in/out]
    D --> D2[Transfer stock between warehouses]
    B --> E[Products]
    E --> E1[View only]
```

### Purchasing Staff

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: ERP section]
    B --> C[Purchase Orders]
    C --> C1[Create draft purchase order]
    C -.-> C2[Cannot receive, approval required]
    B --> D[Suppliers / Products / Inventory]
    D --> D1[View only]
```

### Sales Staff

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: ERP section]
    B --> C[Sales Orders]
    C --> C1[Create draft sales order]
    C -.-> C2[Cannot fulfill, approval required]
    B --> D[Customers / Products / Inventory]
    D --> D1[View only]
```

### Employee (self-service)

```mermaid
flowchart TD
    A[Login] --> B[Dashboard: Platform section only, no HRIS/Finance/ERP cards]
    B --> C[Attendance]
    C --> C1[Check in / Check out, own record only]
    B --> D[Leave]
    D --> D1[View own balance + requests]
    D --> D2[Submit new request]
    D --> D3[Cancel own pending request]
    D -.-> D4[Cannot approve]
    B --> E[Overtime]
    E --> E1[Submit + view own requests]
    B --> F[Payroll]
    F --> F1[View own payslips only]
    B --> G[Training]
    G --> G1[View catalogue + enroll]
```

Self-service scoping (an Employee only ever sees *their own* data, never a colleague's) is enforced in the controllers by resolving the current employee from `Auth::user()->employee` and scoping every query to `employee_id = $employee->id`. It's a query-level restriction inside the controller, not a separate permission per employee.
