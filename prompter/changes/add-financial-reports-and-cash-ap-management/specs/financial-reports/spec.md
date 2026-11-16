## ADDED Requirements

### Requirement: Profit and Loss Statement
The system SHALL generate an Income Statement (Laporan Laba Rugi) for any selected store and date range, computing Gross Revenue, Sales Discounts, Net Revenue, Cost of Goods Sold (COGS/HPP), Gross Profit, Operating Expenses by category, and Net Profit.

#### Scenario: View Profit and Loss for date range
- **WHEN** an authorized user requests the Profit and Loss statement with start and end dates
- **THEN** the system SHALL calculate and display multi-step margins and net income with exact debit-credit derived totals.

#### Scenario: Export or print Profit and Loss
- **WHEN** a user activates the print or export action on the Profit and Loss report
- **THEN** the system SHALL render a clean, high-contrast, printer-friendly document.

### Requirement: Balance Sheet Statement
The system SHALL generate a Balance Sheet (Laporan Neraca) representing the financial position (Assets, Liabilities, and Equity) as of a specified date, ensuring that Total Assets strictly equals Total Liabilities plus Total Equity.

#### Scenario: Balance Sheet equilibrium
- **WHEN** an authorized user views the Balance Sheet as of any specified date
- **THEN** the system SHALL dynamically incorporate Current Period Earnings into Equity such that Total Assets equals Total Liabilities plus Total Equity.

### Requirement: Trial Balance Validation
The system SHALL generate a Trial Balance (Neraca Saldo) listing all active accounts with their cumulative debit and credit balances as of a specified date, proving that the ledger is balanced.

#### Scenario: Verify balanced ledger
- **WHEN** an authorized user inspects the Trial Balance as of a specified date
- **THEN** the system SHALL display each account's ending balance and demonstrate that the grand total of debits strictly equals the grand total of credits.
