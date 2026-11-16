@extends('layouts.app')

@section('title', 'Z1 VapeOS | Edit Akun')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('accounting.accounts.index') }}" class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high rounded-xl transition-all">
            <span class="material-symbols-outlined text-xl">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Edit Akun</h1>
            <p class="text-on-surface-variant text-body-lg">Perbarui informasi akun pembukuan: {{ $account->code }} - {{ $account->name }}.</p>
        </div>
    </div>

    @if($account->is_system)
    <div class="p-3.5 mb-4 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-800 text-xs flex items-center gap-2.5">
        <span class="material-symbols-outlined text-lg shrink-0">lock</span>
        <span>Akun ini adalah <strong>Akun Sistem</strong>. Kode akun, klasifikasi, dan saldo normal dikunci untuk memastikan konsistensi otomatisasi penjurnalan.</span>
    </div>
    @endif

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
        <form method="POST" action="{{ route('accounting.accounts.update', $account->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kode Akun</label>
                    <input type="text" name="code" value="{{ old('code', $account->code) }}" {{ $account->is_system ? 'readonly' : 'required' }} class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-1 focus:ring-secondary {{ $account->is_system ? 'opacity-70 cursor-not-allowed' : '' }} @error('code') border-error @enderror">
                    @error('code')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Klasifikasi Akun</label>
                    @if($account->is_system)
                        <input type="text" readonly value="{{ $account->type_label }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium opacity-70 cursor-not-allowed">
                    @else
                        <select name="type" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                            @foreach($types as $val => $lbl)
                                <option value="{{ $val }}" {{ old('type', $account->type) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Nama Akun <span class="text-error">*</span></label>
                <input type="text" name="name" value="{{ old('name', $account->name) }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary @error('name') border-error @enderror">
                @error('name')
                    <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kategori / Grup <span class="text-error">*</span></label>
                    <input type="text" name="category" value="{{ old('category', $account->category) }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary @error('category') border-error @enderror">
                    @error('category')
                        <p class="text-[11px] text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Saldo Normal</label>
                    @if($account->is_system)
                        <input type="text" readonly value="{{ strtoupper($account->normal_balance) }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold opacity-70 cursor-not-allowed">
                    @else
                        <select name="normal_balance" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                            <option value="debit" {{ old('normal_balance', $account->normal_balance) === 'debit' ? 'selected' : '' }}>Debit (D)</option>
                            <option value="credit" {{ old('normal_balance', $account->normal_balance) === 'credit' ? 'selected' : '' }}>Kredit (K)</option>
                        </select>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Deskripsi / Catatan Tambahan</label>
                <textarea name="description" rows="3" class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">{{ old('description', $account->description) }}</textarea>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="rounded border-outline-variant text-primary focus:ring-secondary">
                    <span class="text-xs font-semibold text-on-surface">Akun Aktif (Muncul pada pilihan penjurnalan)</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-outline-variant">
                <a href="{{ route('accounting.accounts.index') }}" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-bold rounded-xl hover:bg-surface-container-high transition-all no-underline">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
