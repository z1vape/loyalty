# Tasks: Add Financial Reports, Cash-Bank Transfers, Supplier Payables, and Accounting Lock Periods

## 1. Implementation
- [x] 1.1 Create database migration for `accounting_periods`, `fund_transfers`, `supplier_payments`, and payable fields on `stock_movements`
- [x] 1.2 Create Eloquent models `AccountingPeriod`, `FundTransfer`, and `SupplierPayment` with relationships
- [x] 1.3 Register Phase 3 & 4 permissions (`Accounting Report View`, `Accounting Transfer Manage`, `Accounting Payable Manage`, `Accounting Period Manage`) and assign to Super Admin and Manager
- [x] 1.4 Implement `FinancialReportController` with calculation algorithms for:
  - Laporan Laba Rugi (Profit & Loss / Income Statement)
  - Neraca (Balance Sheet with dynamic equity balancing)
  - Neraca Saldo (Trial Balance)
  - Laporan Arus Kas (Cash Flow Statement)
- [x] 1.5 Create responsive and print-ready financial statement Blade views:
  - `resources/views/accounting/reports/profit_and_loss.blade.php`
  - `resources/views/accounting/reports/balance_sheet.blade.php`
  - `resources/views/accounting/reports/trial_balance.blade.php`
  - `resources/views/accounting/reports/cash_flow.blade.php`
- [x] 1.6 Implement `FundTransferController` and views for cashier setoran kas to bank / cash withdrawals:
  - `resources/views/accounting/transfers/index.blade.php`
  - `resources/views/accounting/transfers/create.blade.php`
  - Integration with `AccountingService::recordFundTransferJournal()`
- [x] 1.7 Implement `SupplierPaymentController` and views for Supplier Accounts Payable tracking and repayments:
  - `resources/views/accounting/payables/index.blade.php`
  - `resources/views/accounting/payables/pay.blade.php`
  - Integration with `AccountingService::recordSupplierPaymentJournal()`
- [x] 1.8 Implement `AccountingPeriodController` and views for Period Closing (Tutup Buku) and locking guard:
  - `resources/views/accounting/periods/index.blade.php`
  - Period locking validation guard in `AccountingService::assertDateNotLocked()`
- [x] 1.9 Update sidebar navigation items in `resources/views/layouts/app.blade.php` and register routes in `routes/web.php`
- [x] 1.10 Write automated unit and feature tests validating report calculations, transfer balancing, payable settlements, and period locking

## Post-Implementation
- [x] Update AGENTS.md in the project root for new changes in this specs
