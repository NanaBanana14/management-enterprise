# Routes & API

[← Back to README](../README.md)

MENTER is a **server-rendered Inertia.js application**, so there is no separate REST/JSON API. Every route below returns either an Inertia page response (HTML + a JSON page-props payload on navigation) or a redirect back to one. `routes/web.php` is the entry point; it `require`s seven domain route files, in this order: `settings.php`, `auth.php`, `hris.php`, `finance.php`, `crm.php`, `erp.php`, `assets.php`.

All routes below run behind the `web` middleware group plus `auth` + `verified` (except the routes under `auth.php`, which are the login/registration flow itself). Every route additionally listed with a `permission:` requirement is enforced by `Spatie\Permission\Middleware\PermissionMiddleware`, which throws a 403 rather than silently hiding anything if the current user lacks it.

`laravel/sanctum` is installed (`personal_access_tokens` table exists, `User` uses `HasApiTokens`), but **no `routes/api.php` file exists and no controller issues a token**. Sanctum is present for future API/mobile-client support only.

## Contents

- [Platform](#platform)
- [Admin](#admin)
- [HRIS](#hris)
- [Finance](#finance)
- [CRM](#crm)
- [ERP](#erp)
- [Assets](#assets)

---

## Platform

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/` | `home` | *(none, redirects to `dashboard` or `login`)* |
| GET | `/dashboard` | `dashboard` | *(none, any authenticated user; content is permission-gated per section)* |

## Admin

`prefix: /admin`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin/users` | `admin.users.index` | `users.view` |
| GET | `/admin/users/create` | `admin.users.create` | `users.create` |
| POST | `/admin/users` | `admin.users.store` | `users.create` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `users.update` |
| PUT | `/admin/users/{user}` | `admin.users.update` | `users.update` |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `users.delete` |
| GET | `/admin/roles` | `admin.roles.index` | `roles.view` |
| GET | `/admin/roles/create` | `admin.roles.create` | `roles.create` |
| POST | `/admin/roles` | `admin.roles.store` | `roles.create` |
| GET | `/admin/roles/{role}/edit` | `admin.roles.edit` | `roles.update` |
| PUT | `/admin/roles/{role}` | `admin.roles.update` | `roles.update` |
| DELETE | `/admin/roles/{role}` | `admin.roles.destroy` | `roles.delete` |
| GET | `/admin/audit-logs` | `admin.audit-logs.index` | `audit.view` |

## HRIS

`prefix: /hris`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/hris/departments` | `hris.departments.index` | `department.view` |
| GET/POST/PUT/DELETE | `/hris/departments/...` | `hris.departments.*` | `department.manage` |
| GET | `/hris/positions` | `hris.positions.index` | `position.view` |
| GET/POST/PUT/DELETE | `/hris/positions/...` | `hris.positions.*` | `position.manage` |
| GET | `/hris/employees`, `/hris/employees/{employee}` | `hris.employees.index`/`.show` | `employee.view` |
| GET/POST | `/hris/employees/create` | `hris.employees.create`/`.store` | `employee.create` |
| GET/PUT | `/hris/employees/{employee}/edit` | `hris.employees.edit`/`.update` | `employee.update` |
| DELETE | `/hris/employees/{employee}` | `hris.employees.destroy` | `employee.delete` |
| GET | `/hris/attendance` | `hris.attendance.index` | `attendance.view` |
| POST | `/hris/attendance/check-in` | `hris.attendance.check-in` | `attendance.view` (self-service; scoped to caller's own employee record) |
| POST | `/hris/attendance/check-out` | `hris.attendance.check-out` | `attendance.view` |
| GET | `/hris/leave` | `hris.leave.index` | `leave.view` |
| POST | `/hris/leave` | `hris.leave.store` | `leave.create` |
| POST | `/hris/leave/{leaveRequest}/approve` | `hris.leave.approve` | `leave.approve` |
| POST | `/hris/leave/{leaveRequest}/reject` | `hris.leave.reject` | `leave.approve` |
| POST | `/hris/leave/{leaveRequest}/cancel` | `hris.leave.cancel` | `leave.view` (controller checks ownership) |
| GET | `/hris/overtime` | `hris.overtime.index` | `overtime.view` |
| POST | `/hris/overtime` | `hris.overtime.store` | `overtime.create` |
| POST | `/hris/overtime/{overtimeRequest}/approve` | `hris.overtime.approve` | `overtime.approve` |
| POST | `/hris/overtime/{overtimeRequest}/reject` | `hris.overtime.reject` | `overtime.approve` |
| POST | `/hris/overtime/{overtimeRequest}/cancel` | `hris.overtime.cancel` | `overtime.view` |
| GET | `/hris/payroll` | `hris.payroll.mine` | `payroll.view` (own payslips) |
| GET | `/hris/payroll/payslips/{payslip}` | `hris.payroll.payslips.show` | `payroll.view` |
| POST | `/hris/payroll/payslips/{payslip}/items` | `hris.payroll.payslips.items.store` | `payroll.view` |
| DELETE | `/hris/payroll/payslips/{payslip}/items/{item}` | `hris.payroll.payslips.items.destroy` | `payroll.view` |
| POST | `/hris/payroll/payslips/{payslip}/approve` | `hris.payroll.payslips.approve` | `payroll.view` (controller checks `payroll.approve`) |
| GET | `/hris/payroll/periods` | `hris.payroll.periods.index` | `payroll.view` |
| POST | `/hris/payroll/periods` | `hris.payroll.periods.store` | `payroll.view` |
| GET | `/hris/payroll/periods/{period}` | `hris.payroll.periods.show` | `payroll.view` |
| POST | `/hris/payroll/periods/{period}/generate` | `hris.payroll.periods.generate` | `payroll.view` (controller checks `payroll.process`) |
| GET | `/hris/kpis` | `hris.kpis.index` | `kpi.view` |
| POST | `/hris/kpis/categories`, `/hris/kpis` | `hris.kpis.categories.store`, `hris.kpis.store` | `kpi.view` (controller checks `kpi.manage`) |
| DELETE | `/hris/kpis/{kpi}` | `hris.kpis.destroy` | `kpi.view` (controller checks `kpi.manage`) |
| GET | `/hris/performance` | `hris.performance.mine` | `performance.view` |
| GET | `/hris/performance/reviews/{performanceReview}` | `hris.performance.reviews.show` | `performance.view` |
| POST | `/hris/performance/reviews/{performanceReview}/items/{item}` | `hris.performance.reviews.items.score` | `performance.view` |
| POST | `/hris/performance/reviews/{performanceReview}/submit` | `hris.performance.reviews.submit` | `performance.view` |
| GET | `/hris/performance/periods` | `hris.performance.periods.index` | `performance.view` |
| POST | `/hris/performance/periods` | `hris.performance.periods.store` | `performance.view` (controller checks `performance.manage`) |
| GET | `/hris/performance/periods/{period}` | `hris.performance.periods.show` | `performance.view` |
| POST | `/hris/performance/periods/{period}/reviews` | `hris.performance.periods.reviews.store` | `performance.view` |
| GET | `/hris/recruitment/vacancies` | `hris.recruitment.vacancies.index` | `recruitment.view` |
| POST | `/hris/recruitment/vacancies` | `hris.recruitment.vacancies.store` | `recruitment.view` (controller checks `recruitment.manage`) |
| GET | `/hris/recruitment/vacancies/{vacancy}` | `hris.recruitment.vacancies.show` | `recruitment.view` |
| POST | `/hris/recruitment/vacancies/{vacancy}/applicants` | `hris.recruitment.applicants.store` | `recruitment.view` |
| GET | `/hris/recruitment/applicants/{applicant}` | `hris.recruitment.applicants.show` | `recruitment.view` |
| POST | `/hris/recruitment/applicants/{applicant}/stage` | `hris.recruitment.applicants.stage` | `recruitment.view` |
| POST | `/hris/recruitment/applicants/{applicant}/notes` | `hris.recruitment.applicants.notes.store` | `recruitment.view` |
| POST | `/hris/recruitment/applicants/{applicant}/training` | `hris.recruitment.applicants.training.store` | `recruitment.view` (controller checks `recruitment.manage`); assigns a `recruitment`-audience screening program to the applicant |
| POST | `/hris/recruitment/applicants/{applicant}/training/{result}` | `hris.recruitment.applicants.training.update` | `recruitment.view` (controller checks `recruitment.manage`); records pass/fail, which gates `moveStage(..., Hired)` |
| GET | `/hris/training` | `hris.training.index` | `training.view` |
| POST | `/hris/training/categories`, `/hris/training/programs` | `hris.training.categories.store`, `.programs.store` | `training.view` (controller checks `training.manage`) |
| GET | `/hris/training/programs/{program}` | `hris.training.programs.show` | `training.view`; the course-player page (materials list + content viewer) |
| POST | `/hris/training/programs/{program}/materials` | `hris.training.programs.materials.store` | `training.view` (controller checks `training.manage`) |
| PUT | `/hris/training/programs/{program}/materials/{material}` | `hris.training.programs.materials.update` | `training.view` (controller checks `training.manage`) |
| DELETE | `/hris/training/programs/{program}/materials/{material}` | `hris.training.programs.materials.destroy` | `training.view` (controller checks `training.manage`) |
| POST | `/hris/training/programs/{program}/enroll` | `hris.training.programs.enroll` | `training.view` (self-service) |
| POST | `/hris/training/programs/{program}/enrollments/{enrollment}` | `hris.training.programs.enrollments.update` | `training.view` (controller checks `training.manage`) |

## Finance

`prefix: /finance`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/finance/accounts` | `finance.accounts.index` | `account.view` |
| POST | `/finance/accounts` | `finance.accounts.store` | `account.manage` |
| PUT | `/finance/accounts/{account}` | `finance.accounts.update` | `account.manage` |
| GET | `/finance/journal` | `finance.journal.index` | `journal.view` |
| GET | `/finance/journal/create` | `finance.journal.create` | `journal.create` |
| POST | `/finance/journal` | `finance.journal.store` | `journal.create` |
| GET | `/finance/journal/{journalEntry}` | `finance.journal.show` | `journal.view` |
| GET | `/finance/cashbank` | `finance.cashbank.index` | `cashbank.view` |
| POST | `/finance/cashbank/income` | `finance.cashbank.income` | `income.manage` |
| POST | `/finance/cashbank/expense` | `finance.cashbank.expense` | `expense.manage` |
| POST | `/finance/cashbank/transfer` | `finance.cashbank.transfer` | `cashbank.manage` |
| GET | `/finance/invoices` | `finance.invoices.index` | `invoice.view` |
| POST | `/finance/invoices` | `finance.invoices.store` | `invoice.create` |
| POST | `/finance/invoices/{invoice}/mark-paid` | `finance.invoices.markPaid` | `invoice.approve` |
| GET | `/finance/payables` | `finance.payables.index` | `payable.view` |
| POST | `/finance/payables` | `finance.payables.store` | `payable.manage` |
| POST | `/finance/payables/{payable}/mark-paid` | `finance.payables.markPaid` | `payable.manage` |
| GET | `/finance/reports` | `finance.reports.index` | `report.view` |

## CRM

`prefix: /crm`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/crm/opportunities` | `crm.opportunities.index` | `opportunity.view` |
| POST | `/crm/opportunities` | `crm.opportunities.store` | `opportunity.manage` |
| GET | `/crm/opportunities/{opportunity}` | `crm.opportunities.show` | `opportunity.view` |
| POST | `/crm/opportunities/{opportunity}/stage` | `crm.opportunities.stage` | `opportunity.manage` |
| POST | `/crm/opportunities/{opportunity}/win` | `crm.opportunities.win` | `opportunity.manage`; converts the opportunity's lines into a Sales Order |
| POST | `/crm/opportunities/{opportunity}/notes` | `crm.opportunities.notes.store` | `opportunity.manage` |

## ERP

`prefix: /erp`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/erp/products` | `erp.products.index` | `product.view` |
| POST | `/erp/products` | `erp.products.store` | `product.manage` |
| PUT | `/erp/products/{product}` | `erp.products.update` | `product.manage` |
| GET | `/erp/warehouses` | `erp.warehouses.index` | `warehouse.view` |
| POST | `/erp/warehouses` | `erp.warehouses.store` | `warehouse.manage` |
| PUT | `/erp/warehouses/{warehouse}` | `erp.warehouses.update` | `warehouse.manage` |
| GET | `/erp/inventory` | `erp.inventory.index` | `inventory.view` |
| POST | `/erp/inventory/adjust` | `erp.inventory.adjust` | `inventory.adjust` |
| POST | `/erp/inventory/transfer` | `erp.inventory.transfer` | `inventory.transfer` |
| GET | `/erp/suppliers` | `erp.suppliers.index` | `supplier.view` |
| POST | `/erp/suppliers` | `erp.suppliers.store` | `supplier.manage` |
| PUT | `/erp/suppliers/{supplier}` | `erp.suppliers.update` | `supplier.manage` |
| GET | `/erp/customers` | `erp.customers.index` | `customer.view` |
| POST | `/erp/customers` | `erp.customers.store` | `customer.manage` |
| PUT | `/erp/customers/{customer}` | `erp.customers.update` | `customer.manage` |
| GET | `/erp/purchase-orders` | `erp.purchase-orders.index` | `purchase.view` |
| POST | `/erp/purchase-orders` | `erp.purchase-orders.store` | `purchase.create` |
| POST | `/erp/purchase-orders/{purchaseOrder}/receive` | `erp.purchase-orders.receive` | `purchase.approve` |
| GET | `/erp/sales-orders` | `erp.sales-orders.index` | `sales.view` |
| POST | `/erp/sales-orders` | `erp.sales-orders.store` | `sales.create` |
| POST | `/erp/sales-orders/{salesOrder}/fulfill` | `erp.sales-orders.fulfill` | `sales.approve` |

## Assets

`prefix: /assets`

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/assets` | `assets.index` | `asset.view` |
| GET | `/assets/{asset}` | `assets.show` | `asset.view` |
| POST | `/assets` | `assets.store` | `asset.create`; registers a new fixed asset |
| POST | `/assets/{asset}/reassign` | `assets.reassign` | `asset.create`; moves custody between a warehouse and an employee |
| POST | `/assets/depreciation-runs` | `assets.depreciation.run` | `asset.manage`; posts one combined depreciation journal entry for a given month |
| POST | `/assets/{asset}/dispose` | `assets.dispose` | `asset.manage`; posts a gain/loss journal entry and retires the asset |
