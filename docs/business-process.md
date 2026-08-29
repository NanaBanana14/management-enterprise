# Business Process & Integration

[← Back to README](../README.md)

This document covers the cross-cutting flows: how HRIS, Finance, and ERP hand data to each other, and the step-by-step business processes that matter most (payroll to journal, sales fulfillment to invoice, purchase receipt to payable, leave approval). Every diagram and claim here traces back to a specific Service class method, with no speculative flows.

## Contents

- [Integration: HRIS ↔ Finance ↔ ERP](#integration-hris--finance--erp)
- [Integration table](#integration-table)
- [Sequence: Sales Order fulfillment → AR Invoice](#sequence-sales-order-fulfillment--ar-invoice)
- [Sequence: Purchase Order receipt → AP Payable](#sequence-purchase-order-receipt--ap-payable)
- [Sequence: Payroll period close → Journal Entry](#sequence-payroll-period-close--journal-entry)
- [Sequence: Leave request → approval](#sequence-leave-request--approval)
- [Audit logging](#audit-logging)
- [Failure handling & retries](#failure-handling--retries)

---

## Integration: HRIS ↔ Finance ↔ ERP

```mermaid
flowchart LR
    HRIS[HRIS]
    ERP[ERP]
    FIN[Finance]

    HRIS -->|"Payroll period close → 1 journal entry (Dr Salary Expense, Cr Cash/Bank)"| FIN
    ERP -->|"Sales Order fulfilled → 1 AR invoice + its journal entry"| FIN
    ERP -->|"Purchase Order received → 1 AP payable + its journal entry"| FIN
    ERP -.->|"Customers / Suppliers master data (shared tables, not copied)"| FIN
```

This is the **complete** set of automated, code-level integrations that exist today. There is **no** live data flow in the other directions: Finance does not push balances back into ERP or HRIS, and HRIS/ERP do not read Finance data directly (the Dashboard reads all three domains independently for display, which is not the same as one domain integrating with another).

All three integrations share the same mechanism: a **synchronous method call inside a database transaction**, not an event, queued job, or HTTP API call. There is no message queue, webhook, or async worker involved anywhere in this system (`app/Jobs`, `app/Events`, and `app/Listeners` do not exist in this codebase).

## Integration table

| Integration | Source | Destination | Data | Trigger | Mechanism |
|---|---|---|---|---|---|
| Payroll → Journal | HRIS (`PayrollPeriod` + `Payslip`) | Finance (`JournalEntry`) | Sum of `net_salary` across every payslip in the period, posted as one Dr Salary Expense / Cr Cash-or-Bank entry | `PayrollService::closePeriod()`, requires every payslip in the period to already be `approved` | Direct method call: `PayrollService` constructor-injects `JournalService` and calls `->create(...)` inside the same DB transaction that closes the period |
| Sales Order → Invoice (AR) | ERP (`SalesOrder`) | Finance (`Invoice` + `JournalEntry`) | Order total (`Σ quantity × unit_price`) becomes the invoice amount; invoice due 30 days out | `SalesOrderService::fulfill()`, after stock is deducted from the warehouse | Direct method call: `SalesOrderService` constructor-injects `InvoiceService`; `sales_orders.invoice_id` stores the resulting invoice's ID |
| Purchase Order → Payable (AP) | ERP (`PurchaseOrder`) | Finance (`Payable` + `JournalEntry`) | Order total becomes the payable amount; payable due 30 days out | `PurchaseOrderService::receive()`, after stock is added to the warehouse | Direct method call: `PurchaseOrderService` constructor-injects `PayableService`; `purchase_orders.payable_id` stores the resulting payable's ID |
| Customer/Supplier master data | ERP (Sales Orders, Purchase Orders screens) | Finance (Invoices, Payables screens) | The `customers` and `suppliers` tables themselves: a single shared table each, not a sync | Whenever either domain reads/writes a customer or supplier | Shared database table (no data movement at all; both domains query the same rows) |
| Cash & Bank balance | Finance (`Account` where `is_cash_bank = true`) | Dashboard (all domains) | Live balance, computed on read | Every Dashboard page load | `Account::balance()` sums `journal_entry_lines` on demand, not a stored/cached value, so there is nothing to "sync" |

**Source of truth**: for revenue/expense/receivable/payable accounting, `journal_entries` + `journal_entry_lines` are always authoritative. Every balance shown anywhere in the app (Chart of Accounts, Cash & Bank, Reports, Dashboard) is computed live from these two tables via `Account::balance()`, never stored redundantly.

**Failure handling**: because every integration point runs inside the *same* `DB::transaction()` closure as the ERP/HRIS action that triggers it, a failure in the Finance half (e.g. a missing AR/AP account code, or `InventoryService` throwing "insufficient stock") rolls back the **entire** operation. A Sales Order is never left "fulfilled" with a missing invoice, and a Payroll period is never left "closed" without its journal entry. There is no partial-success or eventual-consistency state to reconcile, and consequently no retry mechanism exists or is needed: the user simply resubmits the action after fixing the underlying validation error.

---

## Sequence: Sales Order fulfillment → AR Invoice

```mermaid
sequenceDiagram
    actor Sales as Sales Staff
    actor Approver as Warehouse/Sales approver
    participant SO as SalesOrderService
    participant Inv as InventoryService
    participant IS as InvoiceService
    participant JS as JournalService
    participant DB as Database

    Sales->>SO: create(customer, warehouse, lines)
    SO->>DB: insert sales_orders (status=draft) + lines
    Approver->>SO: fulfill(order)
    activate SO
    SO->>Inv: adjust(product, warehouse, "out", qty), per line
    Inv-->>SO: throws if insufficient stock (rolls back everything)
    SO->>IS: create(customer, revenueAccount, total, ...)
    IS->>JS: create(date, ref, [Dr AR 1300, Cr Revenue])
    JS->>DB: insert journal_entries + journal_entry_lines
    IS->>DB: insert invoices (status=unpaid)
    SO->>DB: update sales_orders (status=fulfilled, invoice_id=...)
    deactivate SO
    SO-->>Approver: SalesOrder with linked Invoice
```

Route: `POST /erp/sales-orders/{salesOrder}/fulfill`, gated by `sales.approve`.

## Sequence: Purchase Order receipt → AP Payable

```mermaid
sequenceDiagram
    actor Purchasing as Purchasing Staff
    actor Approver as Purchasing approver
    participant PO as PurchaseOrderService
    participant Inv as InventoryService
    participant PS as PayableService
    participant JS as JournalService
    participant DB as Database

    Purchasing->>PO: create(supplier, warehouse, lines)
    PO->>DB: insert purchase_orders (status=draft) + lines
    Approver->>PO: receive(order)
    activate PO
    PO->>Inv: adjust(product, warehouse, "in", qty), per line
    PO->>PS: create(supplier, expenseAccount, total, ...)
    PS->>JS: create(date, ref, [Dr Expense, Cr AP 2100])
    JS->>DB: insert journal_entries + journal_entry_lines
    PS->>DB: insert payables (status=unpaid)
    PO->>DB: update purchase_orders (status=received, payable_id=...)
    deactivate PO
    PO-->>Approver: PurchaseOrder with linked Payable
```

Route: `POST /erp/purchase-orders/{purchaseOrder}/receive`, gated by `purchase.approve`.

## Sequence: Payroll period close → Journal Entry

```mermaid
sequenceDiagram
    actor HR as HR Manager
    actor Finance as Finance Manager
    participant PP as PayrollPeriodController
    participant PS as PayrollService
    participant JS as JournalService
    participant DB as Database

    HR->>PP: generate(period), one draft payslip per active employee
    Finance->>PS: approve(payslip), per payslip, requires payroll.approve
    Note over Finance,PS: repeats until every payslip in the period is approved
    Finance->>PS: closePeriod(period)
    activate PS
    PS->>DB: verify no payslip is still "draft" (else abort)
    PS->>DB: sum(net_salary) across all payslips in the period
    PS->>JS: create(date, ref, [Dr Salary Expense, Cr Cash/Bank])
    JS->>DB: insert journal_entries + journal_entry_lines
    PS->>DB: update payroll_periods (status=closed, journal_entry_id=...)
    PS->>DB: update payslips (status=paid)
    deactivate PS
```

Route: `POST /hris/payroll/periods/{period}/generate` (generation) and the payslip approve/period close actions under `routes/hris.php`, gated by `payroll.process` / `payroll.approve`.

## Sequence: Leave request → approval

A representative **HRIS-only** business process (no Finance/ERP crossover; payroll does *not* currently deduct for unpaid leave taken):

```mermaid
sequenceDiagram
    actor Employee
    actor Manager as HR Manager
    participant LS as LeaveService
    participant DB as Database

    Employee->>LS: request(employee, leaveType, start, end, reason)
    LS->>DB: check for overlapping pending/approved requests
    LS->>DB: check leave balance (paid types only)
    LS->>DB: insert leave_requests (status=pending)
    Manager->>LS: approve(request)
    activate LS
    LS->>DB: re-check balance (lockForUpdate, race-safe)
    LS->>DB: increment leave_balances.used_days
    LS->>DB: update leave_requests (status=approved, approved_by, approved_at)
    deactivate LS
    LS-->>Employee: status visible on own Leave page
```

Unpaid leave types (`is_paid = false`) skip the balance check entirely. There is no cap on unpaid leave in this system.

---

## Audit logging

Not a queued/background process. It runs **synchronously, in-process**, as part of the same request that saves the model:

```text
Trigger:  Eloquent `created` / `updated` / `deleted` event fires on any model using the Auditable trait
Process:  Auditable::bootAuditable() listener runs, diffs old vs. new attributes (updates only),
          strips password/remember_token/updated_at
Data:     One row written to `audit_logs`: user_id (from Auth::id()), action, auditable_type,
          auditable_id, old_values (json), new_values (json), ip_address, user_agent
Output:   Visible at /admin/audit-logs (gated by `audit.view`)
Failure:  None specific. If the audit insert fails, it fails as part of the same request/transaction
          as the original save (no separate retry path, because there is no separate process)
```

See [Database → Soft deletes & audit trail](database.md#soft-deletes--audit-trail) for exactly which models are covered.

## Failure handling & retries

Because there are no queues, jobs, or external service calls anywhere in this codebase, "failure handling" in MENTER is entirely about **transactional integrity within a single request**:

- Every multi-step write (approve, close period, fulfill, receive, adjust stock, post a journal entry) is wrapped in `DB::transaction()`.
- Business-rule violations (insufficient stock, unbalanced journal, insufficient leave balance, wrong status transition) throw a Laravel `ValidationException`, which Inertia surfaces back to the originating form as a field error. The transaction rolls back automatically, so the database never reflects a half-applied action.
- There is no automatic retry, no dead-letter queue, and no eventual consistency anywhere. Every action is either fully applied or not applied at all, resolved within the HTTP request/response cycle.
