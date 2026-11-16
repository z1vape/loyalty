## ADDED Requirements

### Requirement: Chart of Accounts Management
The system SHALL provide a multi-store Chart of Accounts (CoA) categorized into Assets, Liabilities, Equity, Revenue, and Expenses, allowing authorized users to view, create, edit, and categorize accounts for their active store.

#### Scenario: View default Chart of Accounts
- **WHEN** an authenticated user with `Accounting CoA List` permission visits the Chart of Accounts menu
- **THEN** the system SHALL display the structured list of accounts grouped by classification with code, name, category, and normal balance.

#### Scenario: Add custom account
- **WHEN** an authorized user submits valid details for a new account including unique code, name, type, and category
- **THEN** the system SHALL create the account scoped to the active store and display a success confirmation.

#### Scenario: System account protection
- **WHEN** any user attempts to delete or alter code of a default system-protected account (such as default Kas Toko or Persediaan)
- **THEN** the system SHALL reject the deletion and maintain data integrity.

### Requirement: Double-Entry Journal Records
The system SHALL record financial transactions using double-entry bookkeeping where each journal entry contains at least two items and the sum of debits strictly equals the sum of credits.

#### Scenario: Balanced journal entry validation
- **WHEN** a manual journal entry is submitted with matching total debit and credit amounts
- **THEN** the system SHALL record the journal entry, mark it as balanced, and link each item to its respective account.

#### Scenario: Reject unbalanced journal entry
- **WHEN** a manual journal entry is submitted where total debits do not equal total credits
- **THEN** the system SHALL reject the submission and return a clear validation error.

### Requirement: Automated Journaling for Operational Events
The system SHALL automatically generate balanced journal entries whenever operational transactions (Sales checkout, Stock In arrival, Store Expense, Store Income, and Stock Adjustments) occur.

#### Scenario: Auto-journal on POS Sale checkout
- **WHEN** a sale is completed with a total amount and associated cost of goods sold (COGS)
- **THEN** the system SHALL automatically create a journal entry debiting the designated cash/bank/settlement account, crediting Sales Revenue, debiting COGS, and crediting Inventory Asset.

#### Scenario: Auto-journal on Stock In approval
- **WHEN** a stock in movement is approved or recorded with an incoming cost valuation
- **THEN** the system SHALL automatically create a journal entry debiting Inventory Asset and crediting the corresponding payment/bank/payable account.

#### Scenario: Auto-journal on Store Expense
- **WHEN** an expense is recorded for a store
- **THEN** the system SHALL automatically create a journal entry debiting the corresponding expense account and crediting the selected cash/bank account.

#### Scenario: Auto-journal on Store Income
- **WHEN** an income record is saved for a store
- **THEN** the system SHALL automatically create a journal entry debiting the selected cash/bank account and crediting Other Income.

#### Scenario: Reversal on Sale cancellation
- **WHEN** a completed sale is cancelled
- **THEN** the system SHALL create a reversing journal entry or void the original entry to restore accurate account balances.

### Requirement: General Ledger Inspection
The system SHALL provide a General Ledger (Buku Besar) view displaying chronological transaction history, debit and credit movements, and rolling balances for any selected account within a specified date range.

#### Scenario: Filter General Ledger by account and date range
- **WHEN** an authorized user selects a specific account and date range in the General Ledger
- **THEN** the system SHALL calculate the opening balance, list all journal items with transaction reference links, and compute the running closing balance.
