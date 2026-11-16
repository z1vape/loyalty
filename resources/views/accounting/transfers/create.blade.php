@extends('layouts.app')

@section('title', 'Z1 VapeOS | Catat Transfer Kas & Bank')

@section('content')

{{-- Header & Back Button --}}
<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('accounting.transfers.index') }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Kembali ke Daftar Transfer
            </a>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Catat Transfer Kas & Bank</h1>
        <p class="text-on-surface-variant text-body-lg">Formulir mutasi kas internal (setoran penjualan ke bank, penarikan kas kecil, antar rekening).</p>
    </div>
</div>

{{-- Transfer Form --}}
<div class="max-w-3xl">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6 sm:p-8">
        <form method="POST" action="{{ route('accounting.transfers.store') }}" id="transfer-form">
            @csrf

            <div class="space-y-6">
                {{-- Date & Reference --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="transfer_date" class="block text-xs font-bold text-on-surface mb-1.5">
                            Tanggal Mutasi <span class="text-error">*</span>
                        </label>
                        <input type="date" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('transfer_date')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">
                            Nomor Referensi (Otomatis)
                        </label>
                        <input type="text" value="{{ $suggestedNumber }}" disabled
                            class="w-full px-3.5 py-2.5 bg-surface-container-high/50 border border-outline-variant rounded-xl text-xs sm:text-sm font-mono text-on-surface-variant font-bold cursor-not-allowed">
                        <p class="text-[11px] text-on-surface-variant mt-1">Digenerate otomatis oleh sistem saat disimpan.</p>
                    </div>
                </div>

                {{-- Visual Transfer Flow --}}
                <div class="p-4 bg-surface-container-low/60 border border-outline-variant rounded-xl flex flex-col md:flex-row items-center gap-4">
                    {{-- Source Account --}}
                    <div class="flex-1 w-full">
                        <label for="from_account_id" class="block text-xs font-bold text-rose-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">arrow_upward</span>
                            Dari Akun (Sumber Dana Keluar) <span class="text-error">*</span>
                        </label>
                        <select id="from_account_id" name="from_account_id" required onchange="updateAccountBalances()"
                            class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="">-- Pilih Akun Asal --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" data-balance="{{ $acc->current_balance }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <p id="from-balance-text" class="text-[11px] font-bold text-rose-600 mt-1.5"></p>
                        @error('from_account_id')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Arrow Icon --}}
                    <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-surface-container-high border border-outline-variant text-on-surface-variant">
                        <span class="material-symbols-outlined text-xl">trending_flat</span>
                    </div>

                    {{-- Destination Account --}}
                    <div class="flex-1 w-full">
                        <label for="to_account_id" class="block text-xs font-bold text-emerald-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">arrow_downward</span>
                            Ke Akun (Tujuan Dana Masuk) <span class="text-error">*</span>
                        </label>
                        <select id="to_account_id" name="to_account_id" required onchange="updateAccountBalances()"
                            class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Akun Tujuan --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" data-balance="{{ $acc->current_balance }}" {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <p id="to-balance-text" class="text-[11px] font-bold text-emerald-600 mt-1.5"></p>
                        @error('to_account_id')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <label for="amount" class="block text-xs font-bold text-on-surface mb-1.5">
                        Jumlah Transfer (Rp) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-on-surface-variant">Rp</span>
                        <input type="number" id="amount" name="amount" min="1" step="any" value="{{ old('amount') }}" required placeholder="Contoh: 1500000"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    @error('amount')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-xs font-bold text-on-surface mb-1.5">
                        Keterangan / Catatan Mutasi
                    </label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Contoh: Setoran kasir omset shift pagi ke Bank BCA Toko"
                        class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Info Note --}}
                <div class="p-4 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-start gap-3">
                    <span class="material-symbols-outlined text-sky-700 text-lg mt-0.5">info</span>
                    <p class="text-xs text-sky-900 leading-relaxed font-medium">
                        Sistem akuntansi berpasangan akan secara otomatis mendebit rekening tujuan dan mengkredit rekening asal sebesar nominal yang ditransfer. Tidak ada selisih dalam neraca.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant">
                    <a href="{{ route('accounting.transfers.index') }}" class="px-5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-bold text-on-surface hover:bg-surface-container-high transition-all no-underline">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">check</span>
                        Simpan Transfer & Buat Jurnal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function updateAccountBalances() {
        const fromSelect = document.getElementById('from_account_id');
        const toSelect = document.getElementById('to_account_id');
        const fromText = document.getElementById('from-balance-text');
        const toText = document.getElementById('to-balance-text');

        if (fromSelect.selectedIndex > 0) {
            const opt = fromSelect.options[fromSelect.selectedIndex];
            const bal = parseFloat(opt.getAttribute('data-balance') || 0);
            fromText.innerText = 'Saldo saat ini: Rp ' + bal.toLocaleString('id-ID');
        } else {
            fromText.innerText = '';
        }

        if (toSelect.selectedIndex > 0) {
            const opt = toSelect.options[toSelect.selectedIndex];
            const bal = parseFloat(opt.getAttribute('data-balance') || 0);
            toText.innerText = 'Saldo saat ini: Rp ' + bal.toLocaleString('id-ID');
        } else {
            toText.innerText = '';
        }
    }

    document.addEventListener('DOMContentLoaded', updateAccountBalances);
</script>

@endsection
