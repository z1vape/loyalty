<?php

namespace App\Models;

use App\Models\StockMovement;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property float|null $initial_balance
 * @property \Illuminate\Support\Carbon|null $initial_balance_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $ending_balance
 */
class Store extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'auto_cash_to_bank',
        'latitude',
        'longitude',
        'radius_meters',
        'initial_balance',
        'initial_balance_date',
    ];

    protected $casts = [
        'auto_cash_to_bank' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'initial_balance' => 'float',
        'initial_balance_date' => 'date',
    ];

    /**
     * Determine if store has valid GPS geofence coordinates configured.
     */
    public function hasGeofence(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude) && (float)$this->latitude != 0 && (float)$this->longitude != 0;
    }

    /**
     * The users that belong to the store.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The products that belong to the store.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * The movement dates that belong to the store.
     */
    public function movementDates(): HasMany
    {
        return $this->hasMany(MovementDate::class);
    }

    /**
     * The sales that belong to the store.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * The settings that belong to the store.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * The expenses that belong to the store.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cctvCameras(): HasMany
    {
        return $this->hasMany(CctvCamera::class);
    }

    /**
     * The incomes that belong to the store.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * The accounts that belong to the store.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * The journal entries that belong to the store.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * The stock movements that belong to the store.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * The supplier payments that belong to the store.
     */
    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Calculate cumulative ending balance for the store.
     * Ending Balance = initial_balance + Total Sales + Total Incomes - Total Expenses - Direct Stock In - Supplier Payments.
     */
    public function getEndingBalanceAttribute(): float
    {
        $startDate = $this->initial_balance_date ? Carbon::parse($this->initial_balance_date)->toDateString() : '2000-01-01';

        $totalSales = (float) $this->sales()
            ->withoutGlobalScopes()
            ->whereDate('sale_date', '>=', $startDate)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->sum('total_amount');

        $storeId = $this->id;

        $totalIncomes = 0;
        if (Schema::hasTable('incomes')) {
            $totalIncomes = (float) Income::withoutGlobalScopes()
                ->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                    if ($storeId == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->where(function ($q) use ($startDate) {
                    $q->whereDate('income_date', '>=', $startDate)
                      ->orWhere(function ($sq) use ($startDate) {
                          $sq->whereNull('income_date')
                             ->whereDate('created_at', '>=', $startDate);
                      });
                })
                ->sum('amount');
        }

        $totalExpenses = (float) Expense::withoutGlobalScopes()
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
                if ($storeId == 1) {
                    $q->orWhereNull('store_id');
                }
            })
            ->where(function ($q) use ($startDate) {
                $q->whereDate('expense_date', '>=', $startDate)
                  ->orWhere(function ($sq) use ($startDate) {
                      $sq->whereNull('expense_date')
                         ->whereDate('created_at', '>=', $startDate);
                  });
            })
            ->sum('amount');

        // 1. Direct cash/transfer Stock In purchases (non-tempo)
        $totalDirectStockIn = 0;
        if (Schema::hasTable('stock_movements')) {
            $totalDirectStockIn = (float) StockMovement::withoutGlobalScopes()
                ->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                    if ($storeId == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->where('type', 'in')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'approved');
                })
                ->where(function ($q) {
                    $q->whereNull('payment_method')
                      ->orWhereNotIn(DB::raw('LOWER(payment_method)'), ['tempo', 'hutang', 'credit']);
                })
                ->where(DB::raw('DATE(COALESCE(movement_date, created_at))'), '>=', $startDate)
                ->sum(DB::raw('COALESCE(NULLIF(paid_amount, 0), total_price, quantity * purchase_price, 0)'));
        }

        // 2. Payments to suppliers for tempo purchases
        $totalSupplierPayments = 0;
        if (Schema::hasTable('supplier_payments')) {
            $totalSupplierPayments = (float) SupplierPayment::withoutGlobalScopes()
                ->where('store_id', $this->id)
                ->whereDate('payment_date', '>=', $startDate)
                ->sum('amount');
        }

        // 3. Payments for legacy debt installments (Hutang Warisan Bersama)
        $totalLegacyDebtPayments = 0;
        if (Schema::hasTable('legacy_debt_payments')) {
            $totalLegacyDebtPayments = (float) \App\Models\LegacyDebtPayment::withoutGlobalScopes()
                ->where('store_id', $this->id)
                ->whereDate('payment_date', '>=', $startDate)
                ->sum('amount');
        }

        // 4. Cumulative Bank & Cash Reconciliations (Penyesuaian Saldo Riil)
        $totalReconciliations = 0;
        if (Schema::hasTable('bank_reconciliations')) {
            $totalReconciliations = (float) \App\Models\BankReconciliation::withoutGlobalScopes()
                ->where('store_id', $this->id)
                ->whereDate('reconciliation_date', '>=', $startDate)
                ->sum('difference');
        }

        return (float) (($this->initial_balance ?? 0) + $totalSales + $totalIncomes - $totalExpenses - $totalDirectStockIn - $totalSupplierPayments - $totalLegacyDebtPayments + $totalReconciliations);
    }
}
