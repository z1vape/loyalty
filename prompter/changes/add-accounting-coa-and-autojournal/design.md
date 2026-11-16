# Design: Chart of Accounts and Auto-Journaling Engine

## Context
The application is a multi-store Laravel retail inventory and point-of-sale system. Products currently calculate moving average cost (`purchase_price`), sales track multiple payment methods (cash, transfer, qris, debit, credit, split), and store-level incomes and expenses are tracked independently. To support formal financial reporting (P&L, Balance Sheet, General Ledger) without breaking existing workflows, we introduce a non-disruptive, event-driven double-entry accounting layer.

## Goals / Non-Goals
### Goals
- Multi-store isolation for Chart of Accounts and Journal Entries.
- Strict double-entry integrity (total debit must strictly equal total credit for every journal entry).
- Automated journal generation from existing operational transactions without requiring cashier intervention.
- Configurable default system accounts (Kas Toko, Bank, Persediaan Barang Dagang, Pendapatan Penjualan, HPP, Beban Operasional, Beban Selisih Stok).
- Full auditability: ability to drill down from any journal entry to its underlying source record (Sale, Movement, Expense, etc.).

### Non-Goals
- Full multi-currency support (system strictly operates in IDR / Rupiah).
- Advanced tax calculation (e.g., PPN/PPH withholding) in Phase 1 (reserved for later enhancements).
- Customer accounts receivable aging / credit term limits in Phase 1 (treated as cash/settled upon checkout for now).

## Decisions
1. **Double-Entry Schema**:
   - `accounts`: defines account code, name, type (asset, liability, equity, revenue, expense), category, normal balance, and store association.
   - `journal_entries`: header storing entry date, unique reference number, description, store ID, and polymorphic source reference (`source_type`, `source_id`).
   - `journal_items`: detail rows with foreign key to account and debit/credit amounts in `decimal(15, 2)`.
2. **Auto-Journaling Strategy**:
   - Implemented via a central `AccountingService`. When a sale, stock movement, expense, or adjustment is committed within a DB transaction, `AccountingService` creates the corresponding balanced journal entry.
   - For sales cancellations or movement deletions, `AccountingService` automatically creates compensating reversal journal entries or cleans up pending entries, preserving the audit trail.
3. **Store Scoping**:
   - All accounting models use the existing `BelongsToStore` trait or explicit `store_id` foreign keys, ensuring complete tenant isolation when switching stores.
4. **Resilience & Fallback**:
   - If default accounts for a store do not yet exist when an operational transaction fires, the system auto-provisions the store's default standard chart of accounts on-the-fly.

## Risks / Trade-offs
- **Risk**: Performance overhead during POS checkout if journal generation runs synchronously.
  - *Mitigation*: Journal item creation is minimal (typically 2 to 4 rows per sale) and runs inside the existing checkout database transaction without external network calls.
- **Risk**: User accidentally deletes a foundational account (e.g. Kas Toko or Persediaan).
  - *Mitigation*: Mark core foundational accounts as `is_system = true`, preventing deletion or modification of critical system codes.

## Migration Plan
1. Run migrations for `accounts`, `journal_entries`, and `journal_items`.
2. Seed default Chart of Accounts for all existing stores.
3. Provide an optional console/route command to backfill journal entries for historical sales and expenses if requested by the store owner.
4. Rollback strategy: Dropping the newly created accounting tables leaves operational inventory and POS tables completely intact.

## Open Questions
- None for Phase 1.
