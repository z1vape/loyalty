# Design: Financial Reports, Cash-Bank Transfers, Supplier Payables, and Accounting Lock Periods

## Context
Phase 1 & 2 established double-entry bookkeeping across all operational transactions. Every transaction now writes balanced entries into `journal_entries` and `journal_items`. To complete the accounting suite, we implement the read-side financial statements and write-side treasury/audit controls.

## Goals / Non-Goals
### Goals
- Mathematical precision: P&L Net Income dynamically flows into Balance Sheet Current Period Earnings, ensuring Assets = Liabilities + Equity balance at any given snapshot date.
- Real-time aggregation directly from `journal_items` without complex external dependencies.
- Multi-store isolation for all financial statements, transfers, payables, and period locks.
- Secure treasury operations: Cash transfer records must always balance Debit Target Account vs Credit Source Account.
- Controlled supplier debt: Automatic tracking of deferred restock payments with audit trail.
- Immutable closed periods: Closed accounting periods reject creating, editing, or deleting transactions falling within their date boundary.

### Non-Goals
- Automated direct bank API polling / Open Banking sync (transfers are verified within the internal application).
- Complex foreign currency exchange rate conversions (all calculations operate in IDR).

## Decisions
1. **Dynamic Financial Statement Generation**:
   - Instead of storing static snapshot tables that can get out of sync, P&L, Balance Sheet, and Trial Balance calculate dynamically using SQL aggregations on `journal_items` joined with `accounts` and filtered by `journal_entries.entry_date` and `journal_entries.store_id`.
   - **Balance Sheet Current Period Earnings**: Calculated as Net Income (Revenue items - Expense items) from the beginning of the fiscal period up to the snapshot date, added directly under Equity.
2. **Fund Transfers**:
   - `fund_transfers` table records source account, target account, amount, and store ID.
   - Triggers `AccountingService::recordFundTransferJournal()` creating a balanced journal entry.
3. **Supplier Payables (Hutang Dagang)**:
   - When a Stock In movement has `payment_method = 'tempo'` or `'hutang'`, it is marked as `payment_status = 'unpaid'` with a specified `due_date` and `supplier_name`.
   - When repayments are logged in `supplier_payments`, `AccountingService` logs Debit Hutang Dagang (`2101`) and Credit Kas/Bank, updating the remaining debt balance.
4. **Accounting Periods Lock**:
   - `accounting_periods` stores `start_date`, `end_date`, and `status = 'closed'`.
   - Before any transactional write/update/delete in `SaleController`, `StockMovementController`, `ExpenseController`, `IncomeController`, or `JournalController`, `AccountingService::assertDateNotLocked($storeId, $date)` is checked.

## Risks / Trade-offs
- **Risk**: Performance of dynamic Balance Sheet calculations if millions of rows exist.
  - *Mitigation*: Indexed queries on `['store_id', 'entry_date']` and `['account_id']`. For typical retail multi-store operations with dozens of thousands of rows per year, database queries execute in under 30 milliseconds.
- **Risk**: Accidental locking of periods locking out cashiers.
  - *Mitigation*: Only Super Admin and Finance Managers have permission to close periods. Super Admin can reopen a closed period if adjustments are legitimately needed.

## Migration Plan
1. Run migrations for `accounting_periods`, `fund_transfers`, `supplier_payments`, and addition of payable columns to `stock_movements`.
2. Seed permissions and assign to Super Admin and Manager.
3. Deploy views and update routes.
