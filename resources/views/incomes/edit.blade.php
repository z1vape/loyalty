@extends('layouts.app')

@section('title', 'InvenTrack | Edit Pemasukan Toko')

@section('content')

<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Edit Pemasukan Toko</h1>
            <p class="text-on-surface-variant text-body-lg">Perbarui rincian catatan pemasukan toko dan sinkronkan ke jurnal akuntansi.</p>
        </div>
        <a href="{{ route('incomes.index') }}" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs rounded-xl transition-all no-underline flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali
        </a>
    </div>

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
        <form action="{{ route('incomes.update', $income) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1.5">Tanggal Pemasukan <span class="text-error">*</span></label>
                    <input type="date" name="income_date" value="{{ old('income_date', $income->income_date ? $income->income_date->format('Y-m-d') : '') }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                    @error('income_date')
                        <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rekening Penerima / Kas Masuk --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1.5">Kas Penerima / Setor Ke <span class="text-error">*</span></label>
                    <select name="payment_method" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                        <option value="cash" {{ old('payment_method', $income->payment_method ?? 'cash') === 'cash' ? 'selected' : '' }}>💵 Kas Tunai Toko (Laci Kasir)</option>
                        <option value="transfer" {{ old('payment_method', $income->payment_method ?? 'cash') === 'transfer' ? 'selected' : '' }}>🏦 Rekening Bank Toko (BCA / Mandiri / Transfer)</option>
                    </select>
                    @error('payment_method')
                        <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Kategori Akun COA --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Kategori Pemasukan (COA Akuntansi) <span class="text-error">*</span></label>
                <select name="account_id" id="account_id" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                    <option value="" disabled>-- Pilih Pos Pemasukan --</option>
                    
                    @if($revenueAccounts->isNotEmpty())
                    <optgroup label="💰 PENDAPATAN TOKO (Menambah Laba)">
                        @foreach($revenueAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $income->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($receivableAccounts->isNotEmpty())
                    <optgroup label="↩️ PENGEMBALIAN TALANGAN & KASBON (Pelunasan Piutang)">
                        @foreach($receivableAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $income->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
                                [{{ $acc->code }}] {{ $acc->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($capitalAccounts->isNotEmpty())
                    <optgroup label="💼 MODAL & DANA TALANGAN DITERIMA (Ekuitas / Hutang)">
                        @foreach($capitalAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('account_id', $income->account_id) == $acc->id ? 'selected' : '' }} data-desc="{{ $acc->description }}">
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

            {{-- Nama / Sumber Pemasukan --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Nama / Sumber Pemasukan <span class="text-error">*</span></label>
                <input type="text" name="title" value="{{ old('title', $income->title) }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                @error('title')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nominal Pemasukan --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Jumlah Nominal (Rp) <span class="text-error">*</span></label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-on-surface-variant text-sm">Rp</span>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $income->amount) }}" required class="w-full pl-10 pr-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold text-[#006c49] font-mono focus:outline-none focus:ring-2 focus:ring-[#006c49]">
                </div>
                @error('amount')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Catatan --}}
            <div>
                <label class="block text-sm font-semibold text-on-surface mb-1.5">Catatan / Keterangan (Opsional)</label>
                <textarea name="notes" rows="3" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#006c49]">{{ old('notes', $income->notes) }}</textarea>
                @error('notes')
                    <p class="text-xs text-error mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Form Actions --}}
            <div class="pt-4 border-t border-outline-variant/60 flex justify-end gap-3">
                <a href="{{ route('incomes.index') }}" class="px-5 py-2.5 bg-surface-container-high text-on-surface font-semibold text-sm rounded-xl hover:bg-surface-container-highest transition-all no-underline">
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
