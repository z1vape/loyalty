@extends('layouts.app')

@section('title', 'Z1 VapeOS | Pelunasan Hutang Supplier')

@section('content')

{{-- Header & Back Button --}}
<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('accounting.payables.index') }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Kembali ke Buku Hutang
            </a>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Pembayaran Tagihan Supplier</h1>
        <p class="text-on-surface-variant text-body-lg">Pelunasan atau pembayaran bertahap hutang pembelian stok barang.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Invoice Overview & Payment Form --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Bill Details Card --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-outline-variant">
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Nomor Faktur Pembelian</span>
                    <h3 class="text-lg font-extrabold font-mono text-primary mt-0.5">
                        {{ $stockMovement->reference_number ?: "IN-{$stockMovement->id}" }}
                    </h3>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $stockMovement->payment_status_badge }}">
                    {{ match($stockMovement->payment_status) {
                        'paid' => 'Lunas',
                        'partial' => 'Dicicil',
                        'pending' => 'Belum Bayar',
                        default => ucfirst($stockMovement->payment_status)
                    } }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 border-b border-outline-variant text-xs">
                <div>
                    <span class="text-on-surface-variant font-medium">Supplier:</span>
                    <p class="font-bold text-on-surface text-sm mt-0.5">{{ $stockMovement->supplier_name ?: 'Supplier Umum' }}</p>
                </div>
                <div>
                    <span class="text-on-surface-variant font-medium">Barang:</span>
                    <p class="font-bold text-on-surface mt-0.5">{{ $stockMovement->product?->name ?? 'Produk' }}</p>
                    <p class="text-[11px] text-on-surface-variant">Qty: {{ $stockMovement->quantity }} unit</p>
                </div>
                <div>
                    <span class="text-on-surface-variant font-medium">Tgl Masuk:</span>
                    <p class="font-bold text-on-surface mt-0.5">
                        {{ \Illuminate\Support\Carbon::parse($stockMovement->movement_date ?: $stockMovement->created_at)->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <span class="text-on-surface-variant font-medium">Jatuh Tempo:</span>
                    <p class="font-bold mt-0.5 {{ $stockMovement->due_date && \Illuminate\Support\Carbon::parse($stockMovement->due_date)->lt(\Illuminate\Support\Carbon::today()) && $stockMovement->remaining_debt > 0 ? 'text-error font-extrabold' : 'text-on-surface' }}">
                        {{ $stockMovement->due_date ? \Illuminate\Support\Carbon::parse($stockMovement->due_date)->format('d/m/Y') : '-' }}
                    </p>
                </div>
            </div>

            @php
                $totalBill = (float) ($stockMovement->total_price ?: ($stockMovement->quantity * ($stockMovement->purchase_price ?? 0)));
                $paid = (float) ($stockMovement->paid_amount ?? 0);
                $remaining = $stockMovement->remaining_debt;
            @endphp

            <div class="grid grid-cols-3 gap-4 pt-4 text-center">
                <div class="p-3 bg-surface-container-low rounded-xl">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase">Total Tagihan</span>
                    <p class="text-sm sm:text-base font-extrabold text-on-surface font-mono mt-1">
                        Rp {{ number_format($totalBill, 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase">Sudah Dibayar</span>
                    <p class="text-sm sm:text-base font-extrabold text-emerald-700 font-mono mt-1">
                        Rp {{ number_format($paid, 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 {{ $remaining > 0 ? 'bg-rose-500/10' : 'bg-surface-container-low' }} rounded-xl">
                    <span class="text-[10px] font-bold {{ $remaining > 0 ? 'text-rose-800' : 'text-on-surface-variant' }} uppercase">Sisa Hutang</span>
                    <p class="text-sm sm:text-base font-extrabold {{ $remaining > 0 ? 'text-rose-700' : 'text-on-surface' }} font-mono mt-1">
                        Rp {{ number_format($remaining, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Payment Form (only if still has debt) --}}
        @if($remaining > 0)
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <h3 class="text-base font-extrabold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_card</span>
                Formulir Pelunasan / Pembayaran Cicilan
            </h3>
            <p class="text-xs text-on-surface-variant mb-6">Pilih rekening kas/bank yang digunakan untuk membayar supplier ini.</p>

            <form method="POST" action="{{ route('accounting.payables.store', $stockMovement) }}">
                @csrf

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="payment_date" class="block text-xs font-bold text-on-surface mb-1.5">
                                Tanggal Bayar <span class="text-error">*</span>
                            </label>
                            <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('payment_date')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_account_id" class="block text-xs font-bold text-on-surface mb-1.5">
                                Dibayar Menggunakan Rekening <span class="text-error">*</span>
                            </label>
                            <select id="payment_account_id" name="payment_account_id" required
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Pilih Kas / Bank --</option>
                                @foreach($paymentAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_account_id')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="amount" class="block text-xs font-bold text-on-surface">
                                Jumlah Pembayaran (Rp) <span class="text-error">*</span>
                            </label>
                            <button type="button" onclick="setFullAmount()" class="text-xs font-bold text-primary hover:underline cursor-pointer">
                                Bayar Lunas Semua (Rp {{ number_format($remaining, 0, ',', '.') }})
                            </button>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-on-surface-variant">Rp</span>
                            <input type="number" id="amount" name="amount" min="1" max="{{ $remaining }}" step="any" value="{{ old('amount', $remaining) }}" required
                                class="w-full pl-11 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        @error('amount')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reference_number" class="block text-xs font-bold text-on-surface mb-1.5">
                                No. Bukti / Referensi Pembayaran
                            </label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="Contoh: TRF-BCA-98124"
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('reference_number')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-bold text-on-surface mb-1.5">
                                Catatan Tambahan
                            </label>
                            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Pelunasan sisa tagihan batch #1"
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('notes')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-outline-variant flex items-center justify-end gap-3">
                        <a href="{{ route('accounting.payables.index') }}" class="px-5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-bold text-on-surface hover:bg-surface-container-high transition-all no-underline">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Simpan Pembayaran & Kurangi Hutang
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @else
        <div class="p-6 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center gap-3">
            <span class="material-symbols-outlined text-emerald-700 text-3xl">verified</span>
            <div>
                <h4 class="font-extrabold text-emerald-800 text-sm">Faktur Ini Sudah Lunas</h4>
                <p class="text-xs text-emerald-700 mt-0.5">Seluruh kewajiban hutang untuk faktur pembelian ini telah selesai dibayarkan.</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Payment History Timeline --}}
    <div class="space-y-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <h3 class="text-sm font-extrabold text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-on-surface-variant">history</span>
                Riwayat Pembayaran Faktur Ini
            </h3>

            @if($stockMovement->supplierPayments->count() > 0)
                <div class="space-y-3">
                    @foreach($stockMovement->supplierPayments as $pay)
                    <div class="p-3.5 bg-surface-container-low/60 border border-outline-variant rounded-xl text-xs">
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <div>
                                <span class="font-mono font-bold text-primary">{{ $pay->reference_number ?: "PAY-{$pay->id}" }}</span>
                                <p class="text-[11px] text-on-surface-variant mt-0.5">
                                    {{ \Illuminate\Support\Carbon::parse($pay->payment_date)->format('d F Y') }}
                                </p>
                            </div>
                            <span class="font-extrabold font-mono text-emerald-700 text-sm">
                                Rp {{ number_format($pay->amount, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="text-[11px] text-on-surface-variant space-y-0.5 pt-1.5 border-t border-outline-variant/60 flex items-center justify-between">
                            <div>
                                <p>Melalui: <span class="font-semibold text-on-surface">{{ $pay->paymentAccount?->name ?? 'Kas/Bank' }}</span></p>
                                @if($pay->notes)
                                    <p class="italic text-on-surface-variant/80">"{{ $pay->notes }}"</p>
                                @endif
                            </div>

                            @can('Accounting Payable Manage')
                            <form method="POST" action="{{ route('accounting.payables.destroy-payment', $pay) }}" onsubmit="return confirm('Batalkan pembayaran ini? Hutang akan dikembalikan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-error hover:bg-error-container/20 rounded cursor-pointer" title="Hapus Pembayaran">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-2xl text-on-surface-variant/40 mb-1">hourglass_empty</span>
                    <p class="text-xs">Belum ada catatan pembayaran untuk faktur ini.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function setFullAmount() {
        document.getElementById('amount').value = {{ $remaining }};
    }
</script>

@endsection
