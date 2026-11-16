@extends('layouts.app')

@section('title', 'Z1 VapeOS | Pembayaran Pengadaan Supplier')

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
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Pembayaran Tagihan Pengadaan (Batch)</h1>
        <p class="text-on-surface-variant text-body-lg">Pelunasan sekaligus untuk seluruh produk dalam satu pengadaan stok supplier.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Batch Details & Payment Form --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Bill Details Card --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-outline-variant">
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Pengadaan Supplier</span>
                    <h3 class="text-lg font-extrabold text-on-surface mt-0.5 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600">folder</span>
                        <span>{{ $first->supplier_name ?: 'Supplier Umum' }}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-bold">
                            {{ $movements->count() }} Produk
                        </span>
                    </h3>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Tgl Pembelian</span>
                    <p class="font-bold text-sm text-on-surface mt-0.5">
                        {{ \Illuminate\Support\Carbon::parse($first->movement_date ?: $first->created_at)->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            @php
                $totalBatchBill = (float) $movements->sum(fn($m) => (float) ($m->total_price ?: ($m->quantity * ($m->purchase_price ?? 0))));
                $totalBatchPaid = (float) $movements->sum(fn($m) => (float) ($m->paid_amount ?? 0));
                $totalBatchRemaining = $totalRemainingDebt;
            @endphp

            <div class="grid grid-cols-3 gap-4 py-4 text-center border-b border-outline-variant">
                <div class="p-3 bg-surface-container-low rounded-xl">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase">Total Tagihan</span>
                    <p class="text-sm sm:text-base font-extrabold text-on-surface font-mono mt-1">
                        Rp {{ number_format($totalBatchBill, 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase">Sudah Dibayar</span>
                    <p class="text-sm sm:text-base font-extrabold text-emerald-700 font-mono mt-1">
                        Rp {{ number_format($totalBatchPaid, 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 {{ $totalBatchRemaining > 0 ? 'bg-rose-500/10' : 'bg-surface-container-low' }} rounded-xl">
                    <span class="text-[10px] font-bold {{ $totalBatchRemaining > 0 ? 'text-rose-800' : 'text-on-surface-variant' }} uppercase">Sisa Hutang</span>
                    <p class="text-sm sm:text-base font-extrabold {{ $totalBatchRemaining > 0 ? 'text-rose-700' : 'text-on-surface' }} font-mono mt-1">
                        Rp {{ number_format($totalBatchRemaining, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Table of items in this batch --}}
            <div class="pt-4">
                <h4 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-2.5">Daftar Produk dalam Pengadaan Ini:</h4>
                <div class="overflow-x-auto border border-outline-variant rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-surface-container-low text-on-surface-variant font-bold uppercase tracking-wider border-b border-outline-variant">
                            <tr>
                                <th class="px-3.5 py-2.5">No. Ref</th>
                                <th class="px-3.5 py-2.5">Produk</th>
                                <th class="px-3.5 py-2.5 text-center">Qty</th>
                                <th class="px-3.5 py-2.5 text-right">Total Tagihan</th>
                                <th class="px-3.5 py-2.5 text-right">Sisa Hutang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @foreach($movements as $m)
                            @php
                                $mBill = (float) ($m->total_price ?: ($m->quantity * ($m->purchase_price ?? 0)));
                                $mRem = $m->remaining_debt;
                            @endphp
                            <tr class="hover:bg-surface-container-low/40">
                                <td class="px-3.5 py-2 font-mono font-bold text-primary">{{ $m->reference_number ?: "IN-{$m->id}" }}</td>
                                <td class="px-3.5 py-2 font-semibold text-on-surface">{{ $m->product?->name ?? 'Produk' }}</td>
                                <td class="px-3.5 py-2 text-center">{{ $m->quantity }} pcs</td>
                                <td class="px-3.5 py-2 text-right font-mono">Rp {{ number_format($mBill, 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2 text-right font-mono font-bold {{ $mRem > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                    Rp {{ number_format($mRem, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment Form --}}
        @if($totalBatchRemaining > 0)
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <h3 class="text-base font-extrabold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_card</span>
                Formulir Pelunasan Pengadaan
            </h3>
            <p class="text-xs text-on-surface-variant mb-6">Pembayaran akan otomatis dialokasikan ke produk-produk dalam pengadaan ini.</p>

            <form method="POST" action="{{ route('accounting.payables.store-batch') }}">
                @csrf
                <input type="hidden" name="movement_ids" value="{{ $movements->pluck('id')->implode(',') }}">

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
                                Bayar Lunas Semua (Rp {{ number_format($totalBatchRemaining, 0, ',', '.') }})
                            </button>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-on-surface-variant">Rp</span>
                            <input type="number" id="amount" name="amount" min="1" max="{{ $totalBatchRemaining }}" step="any" value="{{ old('amount', $totalBatchRemaining) }}" required
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
                            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Pelunasan faktur pengadaan"
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('notes')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-outline-variant">
                        <a href="{{ route('accounting.payables.index') }}" class="px-5 py-2.5 border border-outline-variant rounded-xl text-xs sm:text-sm font-bold text-on-surface hover:bg-surface-container-low transition no-underline">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-bold shadow-xs transition cursor-pointer border-0 inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Konfirmasi Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- Right: Information & Instructions --}}
    <div class="space-y-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6 space-y-4">
            <h4 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">info</span>
                Aturan Pembayaran Sekaligus
            </h4>
            <div class="text-xs text-on-surface-variant space-y-3 leading-relaxed">
                <p>
                    Pembayaran ini akan secara otomatis dialokasikan secara berurutan untuk melunasi produk-produk dalam pengadaan ini hingga nominal terpenuhi.
                </p>
                <div class="p-3 bg-surface-container-low rounded-xl">
                    <p class="font-bold text-on-surface mb-1">Pencatatan Akuntansi:</p>
                    <ul class="list-disc list-inside space-y-1 text-[11px]">
                        <li><strong>Debit:</strong> Hutang Dagang (2101) berkurang</li>
                        <li><strong>Kredit:</strong> Kas / Bank yang dipilih berkurang</li>
                    </ul>
                </div>
                <p class="text-[11px] text-on-surface-variant/80">
                    Jurnal pembukuan akan otomatis terbentuk dan saldo hutang supplier langsung terupdate.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function setFullAmount() {
        const fullAmount = {{ $totalBatchRemaining }};
        const input = document.getElementById('amount');
        if (input) {
            input.value = fullAmount;
        }
    }
</script>
@endsection
