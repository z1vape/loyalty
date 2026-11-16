# Change: Add Chart of Accounts and Auto-Journaling Engine

## Why
Currently, the retail inventory and POS system tracks operational transactions (Sales, Stock In/Out, Store Incomes, Store Expenses, and Stock Adjustments) without formal double-entry bookkeeping. Introducing a Chart of Accounts (CoA) and an automated journaling engine bridges inventory movements with real-time financial accounting, giving business owners true visibility into balance sheets, cost of goods sold (COGS/HPP), and gross/net margins without burdening cashiers or warehouse staff with manual bookkeeping.

## What Changes
- Add database schema and multi-store model for Chart of Accounts (`accounts`).
- Add database schema and models for double-entry records (`journal_entries` and `journal_items`).
- Provide standard default Chart of Accounts seeder for retail vape/inventory stores (Assets, Liabilities, Equity, Revenue, COGS, Expenses).
- Implement `AccountingService` to automatically generate balanced debit-credit journal entries on:
  - POS / Sales transactions (Cash/Bank, Revenue, COGS, and Inventory reduction).
  - Stock In movements (Inventory increase vs Cash/Bank/Payable).
  - Store Expenses (Expense account vs Cash/Bank).
  - Store Incomes (Cash/Bank vs Other Income).
  - Stock Adjustments (Inventory adjustment vs Inventory shrinkage/loss).
- Add permission nodes for accounting access control assigned to Super Admin & Manager roles.
- Add UI for Chart of Accounts (CoA) list, account creation, and editing.
- Add UI for Journal Entries view (system-generated and manual entry) and General Ledger (Buku Besar) inspection.
- Add sidebar menu group "Akuntansi" under the main navigation in `resources/views/layouts/app.blade.php`.

## Impact
- Affected specs: `accounting`
- Affected code:
  - Database migrations and seeders
  - `app/Models/Account.php`, `app/Models/JournalEntry.php`, `app/Models/JournalItem.php`
  - `app/Services/AccountingService.php`
  - `app/Http/Controllers/AccountController.php`, `app/Http/Controllers/JournalController.php`
  - Integrations in `SaleController.php`, `StockMovementController.php`, `ExpenseController.php`, `IncomeController.php`, `StockAdjustmentController.php`
  - `resources/views/layouts/app.blade.php` and `resources/views/accounting/*`
  - `routes/web.php`
