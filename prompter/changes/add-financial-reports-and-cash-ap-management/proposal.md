# Change: Add Financial Reports, Cash-Bank Transfers, Supplier Payables, and Accounting Lock Periods

## Why
With Phase 1 & 2 completed (Chart of Accounts, Auto-Journaling Engine, and General Ledger live and verified), the system records complete double-entry transactions. Business owners now require formal executive financial statements (Profit & Loss / P&L, Balance Sheet / Neraca, Trial Balance, Cash Flow), operational treasury controls (Cash-to-Bank transfers), Supplier Accounts Payable tracking for deferred restock payments, and Period Closing (Tutup Buku) to lock historical books against unauthorized modifications.

## What Changes
- Add `FinancialReportController` and responsive financial statement views:
  - Laporan Laba Rugi (Income Statement / P&L) with multi-step calculations: Net Sales, Gross Profit, Operating Expenses by category, and Net Profit.
  - Neraca (Balance Sheet): Balancing Assets = Liabilities + Equity as of any selected date.
  - Neraca Saldo (Trial Balance): Pre-closing validation of active account debit and credit balances.
  - Laporan Arus Kas (Cash Flow Statement): Direct cash and bank movement classifications.
  - Print/Export-ready styling for all reports.
- Add Cash & Bank Transfers (`fund_transfers` table & controller):
  - Record cashier cash deposits to store bank accounts or cash withdrawals with automated balancing journal entries.
- Add Supplier Accounts Payable (Hutang Supplier) management:
  - Track deferred/tempo supplier invoices from Stock In movements.
  - Record full or partial debt repayments (`supplier_payments` table) with automated journal entries (Debit Hutang Dagang vs Credit Kas/Bank).
- Add Accounting Period Lock / Tutup Buku (`accounting_periods` table & controller):
  - Close monthly or annual accounting periods per store.
  - Protect historical financial records by rejecting transaction mutations in closed periods.
- Add accounting permission nodes: `Accounting Report View`, `Accounting Transfer Manage`, `Accounting Payable Manage`, `Accounting Period Manage`.
- Register new navigation links under the "Akuntansi" group in `resources/views/layouts/app.blade.php`.

## Impact
- Affected specs: `financial-reports`, `cash-ap-management`
- Affected code:
  - Database migrations for `fund_transfers`, `supplier_payments`, `accounting_periods`, and additions to `stock_movements`.
  - Models: `FundTransfer`, `SupplierPayment`, `AccountingPeriod`.
  - Services: Extensions to `AccountingService`.
  - Controllers: `FinancialReportController`, `FundTransferController`, `SupplierPaymentController`, `AccountingPeriodController`.
  - Views: `resources/views/accounting/reports/*`, `resources/views/accounting/transfers/*`, `resources/views/accounting/payables/*`, `resources/views/accounting/periods/*`.
  - Navigation & Routes: `resources/views/layouts/app.blade.php`, `routes/web.php`.
