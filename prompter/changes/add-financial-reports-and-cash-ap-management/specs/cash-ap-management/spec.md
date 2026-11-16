## ADDED Requirements

### Requirement: Fund Transfers Between Cash and Bank
The system SHALL provide internal fund transfer capability allowing authorized users to record deposits from store cash drawers into bank accounts or bank withdrawals to cash, generating balanced double-entry journals.

#### Scenario: Cash to bank deposit
- **WHEN** an authorized user records a transfer from Kas Toko to Bank Toko
- **THEN** the system SHALL create a transfer record and generate a balanced journal entry debiting Bank Toko and crediting Kas Toko.

### Requirement: Supplier Accounts Payable Management
The system SHALL track unpaid and tempo Stock In purchases from suppliers and allow recording repayments that automatically reduce debt and update cash or bank accounts.

#### Scenario: Track tempo restock purchases
- **WHEN** a Stock In movement is recorded with tempo or hutang payment method
- **THEN** the system SHALL list the movement under Supplier Accounts Payable with supplier name, amount, and due date.

#### Scenario: Record supplier debt repayment
- **WHEN** an authorized user logs a repayment against a supplier payable invoice
- **THEN** the system SHALL record the payment, update the invoice's remaining balance, and post a journal entry debiting Hutang Dagang and crediting the selected cash or bank account.

### Requirement: Accounting Period Closing
The system SHALL allow authorized administrators to close and lock accounting periods, preventing any subsequent transaction creation, modification, or deletion within the closed period.

#### Scenario: Lock accounting period
- **WHEN** an authorized administrator closes an accounting period for a specified date range
- **THEN** the system SHALL mark the period as closed and reject any transactional mutations occurring within that date range.
