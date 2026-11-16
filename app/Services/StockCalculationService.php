<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class StockCalculationService
{
    /** Reversed originals remain in the ledger alongside their compensating event. */
    public function ledger(Product $product, ?string $from = null, ?string $to = null): Collection
    {
        $rows = collect();
        StockMovement::withoutGlobalScopes()->where('product_id', $product->id)->where('store_id', $product->store_id)
            ->where('status', 'approved')->with('user')
            ->when($from, fn ($q) => $q->where('movement_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('movement_date', '<=', $to))
            ->orderBy('movement_date')->orderBy('created_at')->orderBy('id')->get()
            ->each(fn ($m) => $rows->push([
                'date' => $m->movement_date,
                'change' => $m->type === 'in' ? (int) $m->quantity : -(int) $m->quantity,
                'source' => $m,
                'sort' => $m->movement_date . ' ' . $m->created_at->format('H:i:s.u') . ':0:' . str_pad((string) $m->id, 20, '0', STR_PAD_LEFT),
            ]));
        StockAdjustment::withoutGlobalScopes()->where('product_id', $product->id)->where('store_id', $product->store_id)->with('user')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at')->orderBy('id')->get()
            ->each(fn ($a) => $rows->push([
                'date' => $a->created_at->toDateString(), 'change' => (int) $a->quantity_change, 'source' => $a,
                'sort' => $a->created_at->format('Y-m-d H:i:s.u') . ':1:' . str_pad((string) $a->id, 20, '0', STR_PAD_LEFT),
            ]));
        return $rows->sortBy('sort')->values();
    }

    /** Net movement, not the master stock or an inferred opening inventory. */
    public function balance(Product $product, ?string $from = null, ?string $to = null): int
    {
        return (int) $this->ledger($product, $from, $to)->sum('change');
    }

    public function stockCard(Product $product, ?string $from = null, ?string $to = null, ?string $type = null): array
    {
        $opening = $from ? (float) $this->ledger($product)->filter(fn ($row) => $row['date'] < $from)->sum('change') : 0.0;
        $running = $opening;
        $totalIn = $totalOut = $valueIn = $valueOut = 0.0;
        $rows = [];
        foreach ($this->ledger($product, $from, $to) as $event) {
            $running += $event['change'];
            $direction = $event['change'] > 0 ? 'in' : 'out';
            if ($type && $type !== 'all' && $direction !== $type) {
                continue;
            }
            $source = $event['source'];
            $isAdjustment = $source instanceof StockAdjustment;
            // A read-only presentation model preserves the stock-card/export contract.
            $movement = $isAdjustment ? new StockMovement([
                'movement_date' => $event['date'], 'type' => $direction,
                'quantity' => abs($event['change']), 'reference_number' => 'ADJ-' . $source->id,
                'notes' => StockAdjustment::REASONS[$source->reason] . ($source->notes ? ': ' . $source->notes : ''),
            ]) : $source;
            if ($isAdjustment) {
                $movement->setRelation('user', $source->user);
            }
            $in = $direction === 'in' ? (float) abs($event['change']) : 0.0;
            $out = $direction === 'out' ? (float) abs($event['change']) : 0.0;
            $price = $isAdjustment ? 0.0 : (float) ($direction === 'in'
                ? ($movement->purchase_price ?: $product->purchase_price)
                : ($movement->selling_price ?: $product->selling_price));
            $value = $isAdjustment ? 0.0 : (float) ($movement->total_price ?: (($in + $out) * $price));
            $totalIn += $in;
            $totalOut += $out;
            $valueIn += $direction === 'in' ? $value : 0.0;
            $valueOut += $direction === 'out' ? $value : 0.0;
            $ref = $movement->reference_number ?? '';
            $notes = strtolower($movement->notes ?? '');
            $isSale = str_starts_with($ref, 'SALE') || str_starts_with($ref, 'POS') || str_contains($notes, 'sale') || str_contains($notes, 'pos');
            $isTransfer = str_starts_with($ref, 'TRF-');

            $sourceLabel = $isAdjustment
                ? 'Penyesuaian Stok'
                : ($isTransfer
                    ? 'Mutasi Antar Cabang'
                    : ($direction === 'in'
                        ? ($movement->supplier_name ? 'Restock (' . $movement->supplier_name . ')' : 'Stok Masuk')
                        : ($isSale ? 'Penjualan (POS)' : 'Stok Keluar')));

            $rows[] = [
                'movement' => $movement,
                'qty_in' => $in,
                'qty_out' => $out,
                'running_balance' => $running,
                'balance' => $running,
                'unit_price' => $price,
                'total_price' => $value,
                'source_label' => $sourceLabel,
            ];
        }
        return ['openingBalance' => $opening, 'endingBalance' => $running,
            'totalIn' => $totalIn, 'totalOut' => $totalOut, 'totalValueIn' => $valueIn,
            'totalValueOut' => $valueOut, 'rows' => $rows, 'totalMovements' => count($rows)];
    }
}
