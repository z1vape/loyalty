# QA Test Plan — Keandalan dan Keamanan Inventory Stock

## 1. Objectives

- Verify authentication and permission gates for product, sale, and stock-movement workflows.
- Verify active-store isolation for products, sales, stock movements, and request-bound IDs.
- Verify Form Requests reject invalid input and cross-store references.
- Verify product SKU uniqueness is scoped to `store_id`.
- Verify sales and stock movements are transactional, use row locking, and never produce negative stock.
- Verify model relationships/fillable store ownership and preserve regression coverage across the Laravel application.

## 2. Implementation inspected before execution

The QA review covered the developer-listed implementation, specifically:

- Requests: `app/Http/Requests/StoreProductRequest.php`, `StoreSaleRequest.php`, `StoreStockMovementRequest.php`, and the existing `StoreStockAdjustmentRequest.php`.
- Security: `app/Rules/BelongsToCurrentStore.php`, `app/Policies/ProductPolicy.php`, `SalePolicy.php`, `StockMovementPolicy.php`, `app/Http/Middleware/CheckActiveStore.php`, `app/Traits/BelongsToStore.php`, `app/Providers/AppServiceProvider.php`, and `routes/web.php`.
- Controllers/services/models: `ProductController.php`, `SaleController.php`, `StockMovementController.php`, `app/Services/*`, `app/Models/{Product,Sale,SaleItem,StockMovement,Store,User}.php`.
- Database: the product/store migrations, `database/migrations/2026_10_09_000000_scope_product_sku_to_store.php`, factories, and seeders.
- Tests: all newly listed Feature/Unit tests plus existing `tests/Feature` and `tests/Unit` suites.
- UI/build surface: `resources/views/**`, `resources/js`, `resources/css`, `vite.config.js`, `package.json`.

## 3. Test scenarios

1. Guest requests to inventory, sales, and movement endpoints are redirected to login.
2. Authenticated users without the relevant permission receive denial rather than access.
3. Users can only read/update/delete records belonging to their active store.
4. A product, sale item, or movement ID from another store is rejected by policy, scoped query, or `BelongsToCurrentStore`.
5. Product creation rejects duplicate SKU within a store and permits the same SKU in another store.
6. Product and movement request validation rejects missing, malformed, negative, and out-of-range values.
7. A sale with sufficient stock atomically creates sale/items/movement rows and decrements stock.
8. A sale with insufficient stock fails and leaves no partial sale, item, movement, or stock mutation.
9. Stock-in succeeds transactionally; stock-out is rejected when it would make stock negative.
10. A failure in a multi-row operation rolls back all preceding writes.
11. Row locks prevent stale stock decisions in sale/movement transactions.
12. Existing inventory, reports, exports/imports, adjustment, authentication, and UI regression tests remain green.
13. PHP syntax checks pass for the changed request, policy, and rule classes.
14. Frontend production assets compile; browser-level Playwright execution is not configured in this Laravel workspace.

## 4. Automated test case matrix

| ID | Area | Expected assertion | Automated coverage |
|---|---|---|---|
| QA-01 | Authentication | Guest inventory access redirects to `/login` | `tests/Feature/AuthenticationTest.php` |
| QA-02 | Authorization | Guest stock/sale writes are denied | `AuthorizationTest.php`, `SaleTest.php`, `StockMovementTest.php` |
| QA-03 | Store isolation | API/store data does not bypass active-store scope | `StoreIsolationTest.php` plus existing feature suites |
| QA-04 | Product model | `store_id` is mass-assignable | `tests/Unit/Models/ProductTest.php` |
| QA-05 | Movement model | Product relation and `store_id` are present | `tests/Unit/Models/StockMovementTest.php` |
| QA-06 | Product request | Store-scoped SKU uniqueness and numeric/input validation | `StoreProductRequest.php` reviewed; request path exercised by Laravel suite |
| QA-07 | Sale request | Product IDs must belong to active store; quantities/prices validated | `StoreSaleRequest.php` reviewed; sale feature path |
| QA-08 | Movement request | Product IDs, type, quantity, date, and authorization validated | `StoreStockMovementRequest.php` reviewed; movement feature path |
| QA-09 | Policy security | Permission and same-store checks apply to product/sale/movement actions | Three policy classes reviewed; authorization feature paths |
| QA-10 | Transactional sale | Lock, stock check, create rows, and decrement are atomic | `SaleController.php` reviewed; existing sale tests |
| QA-11 | Transactional movement | Lock and negative-stock prevention are atomic | `StockMovementController.php` reviewed; existing movement tests |
| QA-12 | Regression | Full PHPUnit suite passes | `php artisan test` |
| QA-13 | Frontend | Production bundle exits 0 | `npm run build` |
| QA-14 | Syntax | Changed PHP security classes parse cleanly | `php -l` checks |

## 5. Coverage assertions

- **Authentication/authorization:** guest redirects, permission gates, controller gates, policies, and active-store membership are represented.
- **Isolation:** request-level foreign-key validation and model/controller store scoping are explicitly reviewed; cross-store IDs are a required assertion.
- **Integrity:** SKU uniqueness, non-negative stock, row locking, and transaction rollback are required assertions.
- **Regression:** all existing Feature and Unit tests are included by the Laravel runner; changed views/assets are represented by the build check.
- **Limitations:** no Playwright dependency/configuration exists, so browser workflows were not executable. The Laravel suite could not start because `vendor/autoload.php` is absent; npm build could not start because `vite`/`node_modules` is absent. These are environment blockers, not passing results.

## 6. Verification checklist

- [x] Read implementation files and newly added tests before execution.
- [x] Confirmed test runner configuration in `composer.json`.
- [x] Created/maintained this dedicated QA document.
- [x] PHP syntax checks executed successfully for changed security classes.
- [x] `php artisan test` executed from the target workspace.
- [x] `npm run build` executed from the target workspace.
- [x] Exit codes and failure output inspected.
- [x] Results recorded below.
- [ ] PHPUnit suite passed (blocked by missing Composer dependencies).
- [ ] Frontend build passed (blocked by missing npm dependencies).
- [ ] Playwright workflow executed (not configured).

## 7. Test execution results

### PHP syntax

**PASS — exit code 0**

Command:

```text
php -l app/Http/Requests/StoreProductRequest.php && php -l app/Http/Requests/StoreSaleRequest.php && php -l app/Http/Requests/StoreStockMovementRequest.php && php -l app/Policies/ProductPolicy.php && php -l app/Policies/SalePolicy.php && php -l app/Policies/StockMovementPolicy.php && php -l app/Rules/BelongsToCurrentStore.php
```

All seven files reported `No syntax errors detected`.

### Laravel automated tests

**BLOCKED/FAIL — exit code 255**

Command: `php artisan test`

Observed error: `Failed opening required .../inventory_stock/vendor/autoload.php`; Composer dependencies are not installed, so PHPUnit could not run.

### Frontend build

**BLOCKED/FAIL — exit code 127**

Command: `npm run build`

Observed error: `sh: vite: command not found`; frontend dependencies are not installed.

### QA conclusion

The available syntax checks passed, but the required automated application and build suites did not execute successfully. This QA run is therefore a failure pending dependency installation and rerun.

## 8. QA execution addendum for stok audit

The implementation-specific audit matrix was reviewed against `InventoryService`, `StockCalculationService`, `StockAdjustment`, `StockAdjustmentController`, `StoreStockAdjustmentRequest`, both stock-adjustment migrations, all three adjustment Blade views, and `tests/Feature/StockAdjustmentAuditTest.php`. It covers positive/negative adjustments, non-negative enforcement, before/after snapshots, permissions, cross-store isolation, immutable history, reversal records, filters, and report consistency.

Commands executed from this workspace:

```text
php artisan test --filter=StockAdjustmentAuditTest
php artisan test
npm run build
for f in app/Services/InventoryService.php app/Services/StockCalculationService.php app/Models/StockAdjustment.php app/Http/Controllers/StockAdjustmentController.php app/Http/Requests/StoreStockAdjustmentRequest.php database/migrations/2026_10_08_120000_create_stock_adjustments_table.php database/migrations/2026_10_08_120001_add_reversal_records_to_stock_adjustments.php tests/Feature/StockAdjustmentAuditTest.php; do php -l "$f" || exit 1; done
```

Observed results:

- `php artisan test --filter=StockAdjustmentAuditTest`: **not executed; command was denied by environment approval**.
- `php artisan test`: **exit 255**; Laravel could not load `vendor/autoload.php` because Composer dependencies are absent.
- `npm run build`: **exit 127**; `vite: command not found` because npm dependencies are absent.
- PHP syntax loop: **exit 0**; all 8 inspected implementation/migration/test files reported `No syntax errors detected`.

Final status: **FAIL**. The required automated application tests and frontend build could not run successfully in this workspace; syntax validation alone is insufficient for a QA pass.

## 9. Current implementation audit findings

The implementation review found security and coverage gaps that should be fixed before rerun:

- `ProductController::index()` and its summary queries use unscoped `Product` queries; `SaleController::index()` and `StockMovementController::index()` likewise do not visibly constrain the active store. Related eager-loaded users/products and cashiers are also broad queries.
- `SaleController::store()` and `StockMovementController::store()` still call `$request->validate()` with legacy `exists:products,id` rules after receiving store-aware Form Requests. The controller validation can bypass the intended `BelongsToCurrentStore` rule, and the sale transaction creates records without explicitly setting `store_id`.
- The new feature tests mostly cover guest redirects. `StoreIsolationTest` only asserts a successful API response and does not create two stores or assert that foreign records are hidden/rejected; there is no automated test for insufficient stock, rollback, duplicate SKU, same SKU across stores, or atomic success.
- `ProductController` retains global `unique:products,sku` validation in `store()`/`update()`, conflicting with the store-scoped `StoreProductRequest` and migration intent.
- Policy registration/route-level authorization and the active-store scope need end-to-end tests for authenticated users; syntax-only checks cannot establish those guarantees.

These findings are recorded as QA defects, not waived assumptions.

## 10. Latest execution evidence

Executed from the required `inventory_stock` workspace:

```text
php artisan test
exit code: 255
Failed opening required .../inventory_stock/vendor/autoload.php

php -l <17 changed implementation/test PHP files>
exit code: 0
All files reported "No syntax errors detected".

npm run build
exit code: 127
sh: vite: command not found
```

The Laravel suite was not runnable because Composer dependencies are absent, and the frontend build was not runnable because npm dependencies are absent. No Playwright configuration or dependency is present. Overall QA status remains **FAIL**.
