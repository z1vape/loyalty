@extends('layouts.app')

@section('title', 'Z1 VapeOS | Tambah Akun Baru')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('accounting.accounts.index') }}" class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high rounded-xl transition-all">
            <span class="material-symbols-outlined text-xl">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Tambah Akun Baru</h1>
            <p class="text-on-surface-variant text-body-lg">Tambahkan akun kustom ke dalam Bagan Akun (Chart of Accounts).</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
        <form method="POST" action="{{ route('accounting.accounts.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kode Akun <span class="text-error">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: 6106" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-1 focus:ring-secondary @error('code') border-error @enderror">
                    @error('code')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Klasifikasi Akun <span class="text-error">*</span></label>
                    <select name="type" id="type-select" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                        <option value="">Pilih Klasifikasi</option>
                        @foreach($types as $val => $lbl)
                            <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Nama Akun <span class="text-error">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Beban Promosi & Pemasaran" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary @error('name') border-error @enderror">
                @error('name')
                    <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kategori / Grup <span class="text-error">*</span></label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="Contoh: Beban Operasional" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary @error('category') border-error @enderror">
                    @error('category')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Saldo Normal <span class="text-error">*</span></label>
                    <select name="normal_balance" id="normal-balance-select" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                        <option value="debit" {{ old('normal_balance') === 'debit' ? 'selected' : '' }}>Debit (D)</option>
                        <option value="credit" {{ old('normal_balance') === 'credit' ? 'selected' : '' }}>Kredit (K)</option>
                    </select>
                    @error('normal_balance')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Deskripsi / Catatan Tambahan</label>
                <textarea name="description" rows="3" placeholder="Penjelasan peruntukan akun ini..." class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-outline-variant">
                <a href="{{ route('accounting.accounts.index') }}" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-bold rounded-xl hover:bg-surface-container-high transition-all no-underline">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer shadow-sm">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type-select');
        const normalSelect = document.getElementById('normal-balance-select');

        if (typeSelect && normalSelect) {
            typeSelect.addEventListener('change', function() {
                const t = this.value;
                if (t === 'asset' || t === 'expense') {
                    normalSelect.value = 'debit';
                } else if (t === 'liability' || t === 'equity' || t === 'revenue') {
                    normalSelect.value = 'credit';
                }
            });
        }
    });
</script>

@endsection
