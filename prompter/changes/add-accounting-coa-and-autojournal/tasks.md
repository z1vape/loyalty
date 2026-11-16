# Tasks: Add Chart of Accounts and Auto-Journaling Engine

## 1. Implementation
- [x] 1.1 Create database migration for `accounts`, `journal_entries`, and `journal_items` tables with proper indexes and foreign key constraints
- [x] 1.2 Create Eloquent models `Account`, `JournalEntry`, and `JournalItem` with store relations and helper scopes
- [x] 1.3 Create standard Chart of Accounts seeder / factory service for multi-store retail deployment
- [x] 1.4 Implement `AccountingService` with automated double-entry logic for:
  - Sales checkout and cancellations (Cash/Bank, Revenue, COGS, Inventory)
  - Stock In approvals (Inventory Asset vs Cash/Bank/Payable)
  - Store Expenses (Expense vs Cash/Bank)
  - Store Incomes (Cash/Bank vs Other Income)
  - Stock Adjustments (Inventory Asset vs Inventory Shrinkage/Gain)
- [x] 1.5 Register accounting permissions (`Accounting CoA List`, `Accounting CoA Create`, `Accounting CoA Edit`, `Accounting CoA Delete`, `Accounting Journal List`, `Accounting Journal Create`, `Accounting Ledger View`) and assign to Super Admin and Manager roles
- [x] 1.6 Integrate `AccountingService` into existing controllers (`SaleController`, `StockMovementController`, `ExpenseController`, `IncomeController`, `StockAdjustmentController`, `PosApiController`)
- [x] 1.7 Implement Web controllers:
  - `AccountController` for Chart of Accounts management
  - `JournalController` for Journal Entries view, Manual Journal creation, and General Ledger (Buku Besar) view
- [x] 1.8 Create responsive Blade views styled with the application's Tailwind design tokens:
  - `resources/views/accounting/accounts/index.blade.php` (CoA tree & list)
  - `resources/views/accounting/accounts/create.blade.php` (New account form)
  - `resources/views/accounting/accounts/edit.blade.php` (Edit account form)
  - `resources/views/accounting/journals/index.blade.php` (Journal listing with debit/credit badges and drilldown)
  - `resources/views/accounting/journals/create.blade.php` (Manual journal entry form with dynamic rows)
  - `resources/views/accounting/journals/ledger.blade.php` (General Ledger per account with running balance)
- [x] 1.9 Add sidebar navigation items under "Akuntansi" in `resources/views/layouts/app.blade.php` and register routes in `routes/web.php`
- [x] 1.10 Write unit/feature tests validating debit-credit balancing, automated posting, and multi-store isolation

## Post-Implementation
- [x] Update AGENTS.md in the project root for new changes in this specs
