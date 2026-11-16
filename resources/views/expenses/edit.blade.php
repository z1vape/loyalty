@extends('layouts.app')

@section('title', 'InvenTrack | Edit Pengeluaran Toko')

@section('content')

<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Edit Pengeluaran Toko</h1>
            <p class="text-on-surface-variant text-body-lg">Perbarui rincian pengeluaran toko dan sinkronkan ke jurnal akuntansi.</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs rounded-xl transition-all no-underline flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali
        </a>
    </div>

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
        <form action="{{ route('expenses.update', $expense->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1.5">Tanggal Pengeluaran <span class="text-error">*</span></label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date ? $expense->expense_date->format('Y-m-d') : '') }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                    @error('expense_date')
                        <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sumber Dana / Kas Keluar --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1.5">Sumber Dana / Kas Keluar <span class="text-error">*</span></label>
                    <select name="payment_method" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                        <option value="cash" {{ old('payment_method', $expense->payment_method ?? 'cash') === 'cash' ? 'selected' : '' }}>💵 Kas Tunai Toko (Laci Kasir)</option>
                        <option value="transfer" {{ old('payment_method', $expense->payment_method ?? 'cash') === 'transfer' ? 'selected' : '' }}>🏦 Rekening Bank Toko (Transfer / EDC)</option>
                    </select>
                    @error('payment_method')
                        <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Kategori Akun COA --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Kategori Akun (COA Akuntansi) <span class="text-error">*</span></label>
                <select name="account_id" id="account_id" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                    <option value="" disabled>-- Pilih Pos Pengeluaran --</option>
                    
                    @if($operatingExpenses->isNotEmpty())
                    <optgroup label="📋 BEBAN OPERASIONAL TOKO (Masuk Laba Rugi)">
                        @foreach($operatingExpenses as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $expense->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($receivableAccounts->isNotEmpty())
                    <optgroup label="🤝 TALANGAN & PINJAMAN (Masuk Piutang - Tidak Potong Laba)">
                        @foreach($receivableAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $expense->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($payableAccounts->isNotEmpty())
                    <optgroup label="🤝 PELUNASAN HUTANG & TALANGAN (Hutang Supplier / Talangan)">
                        @foreach($payableAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $expense->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if(isset($equityAccounts) && $equityAccounts->isNotEmpty())
                    <optgroup label="👑 BAGI HASIL & PRIVE (Distribusi Keuntungan Pemilik / Investor)">
                        @foreach($equityAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $expense->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif
                </select>
                <p id="account_hint" class="text-xs text-on-surface-variant mt-1.5 italic"></p>
                @error('account_id')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nama / Keperluan Pengeluaran --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Nama / Keperluan Pengeluaran <span class="text-error">*</span></label>
                <input type="text" name="title" value="{{ old('title', $expense->title) }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                @error('title')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nominal Pengeluaran --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Jumlah Nominal (Rp) <span class="text-error">*</span></label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-on-surface-variant text-sm">Rp</span>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required class="w-full pl-10 pr-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold text-error font-mono focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                </div>
                @error('amount')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Catatan --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Catatan / Keterangan (Opsional)</label>
                <textarea name="notes" rows="3" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">{{ old('notes', $expense->notes) }}</textarea>
                @error('notes')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Form Actions --}}
            <div class="pt-4 border-t border-outline-variant/60 flex justify-end gap-3">
                <a href="{{ route('expenses.index') }}" class="px-5 py-2.5 bg-surface-container-high text-on-surface font-semibold text-sm rounded-xl hover:bg-surface-container-highest transition-all no-underline">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary font-bold text-sm rounded-xl hover:opacity-90 active:scale-95 transition-all shadow border-0 cursor-pointer flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('account_id');
        const hint = document.getElementById('account_hint');
        function updateHint() {
            const selected = select.options[select.selectedIndex];
            if (selected && selected.getAttribute('data-desc')) {
                hint.textContent = 'ℹ️ ' + selected.getAttribute('data-desc');
            } else {
                hint.textContent = '';
            }
        }
        select.addEventListener('change', updateHint);
        updateHint();
    });
</script>

@endsection
