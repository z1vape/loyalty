@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buat Surat Jalan Mutasi')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Header & Action Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Buat Surat Jalan Mutasi Stok</h1>
            <p class="text-on-surface-variant text-body-lg">
                Pilih cabang pengirim, cabang tujuan, serta daftar barang yang akan dimutasi beserta kuantitasnya.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('stock-transfers.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke Mutasi</span>
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs space-y-1">
            @foreach($errors->all() as $err)
                <div class="flex items-center gap-2 font-semibold">
                    <span class="material-symbols-outlined text-[16px]">warning</span>
                    <span>{{ $err }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('stock-transfers.store') }}" class="flex flex-col gap-6">
        @csrf

        {{-- Shipping Information Card --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-primary">storefront</span>
                <span>Informasi Pengiriman Antar Cabang</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Cabang Pengirim (Asal) <span class="text-rose-600">*</span></label>
                    <select name="from_store_id" id="from_store_select" required
                            onchange="window.location.href='{{ route('stock-transfers.create') }}?from_store_id=' + this.value"
                            class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                        @foreach($accessibleStores as $st)
                            <option value="{{ $st->id }}" {{ (int) $selectedFromStoreId === (int) $st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Cabang Tujuan (Penerima) <span class="text-rose-600">*</span></label>
                    <select name="to_store_id" required
                            class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                        <option value="">-- Pilih Cabang Tujuan --</option>
                        @foreach($allStores as $st)
                            @if((int) $st->id !== (int) $selectedFromStoreId)
                                <option value="{{ $st->id }}" {{ old('to_store_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Surat Jalan <span class="text-rose-600">*</span></label>
                    <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required
                           class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-mono text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan Surat Jalan / Kurir</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Dikirim via kurir internal sore ini..."
                          class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Items Section Card --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">inventory_2</span>
                        <span>Daftar Barang yang Dimutasi</span>
                    </h2>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Nilai HPP diambil otomatis dari Harga Pokok (Moving Average) cabang pengirim.</p>
                </div>

                <button type="button" onclick="addTransferRow()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-secondary-container text-on-secondary-container text-xs font-bold hover:opacity-90 transition cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Baris Barang</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="transfer-items-table">
                    <thead>
                        <tr class="border-b border-outline-variant/60 text-[11px] font-extrabold text-on-surface-variant uppercase bg-surface-container-low">
                            <th class="py-2.5 px-3">Produk di Cabang Pengirim</th>
                            <th class="py-2.5 px-3 w-36">Kuantitas Kirim</th>
                            <th class="py-2.5 px-3 w-56">Catatan Item</th>
                            <th class="py-2.5 px-3 w-12 text-right">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="transfer-items-body" class="divide-y divide-outline-variant/40">
                        <tr class="transfer-item-row">
                            <td class="py-3 px-3">
                                <select name="items[0][product_id]" required class="w-full rounded-xl border border-outline-variant bg-surface-container-low text-xs sm:text-sm px-3 py-2 text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($storeProducts as $prod)
                                        <option value="{{ $prod->id }}" {{ $prod->stock <= 0 ? 'disabled' : '' }}>
                                            [{{ $prod->sku ?: 'NO-SKU' }}] {{ $prod->name }} {{ $prod->variant ? '('.$prod->variant.')' : '' }} — Stok: {{ $prod->stock }} | HPP: Rp {{ number_format($prod->purchase_price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" name="items[0][quantity]" value="1" min="1" required
                                       class="w-full rounded-xl border border-outline-variant bg-surface-container-low text-xs sm:text-sm font-mono font-bold px-3 py-2 text-center text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
                            </td>
                            <td class="py-3 px-3">
                                <input type="text" name="items[0][notes]" placeholder="Opsional"
                                       class="w-full rounded-xl border border-outline-variant bg-surface-container-low text-xs px-3 py-2 text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" onclick="removeTransferRow(this)" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-500/10 cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
            <button type="submit" name="send_immediately" value="0"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest text-on-surface text-xs sm:text-sm font-bold hover:bg-surface-container-low transition cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Sebagai Draft</span>
            </button>

            <button type="submit" name="send_immediately" value="1"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs sm:text-sm font-bold shadow-sm hover:opacity-95 transition cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                <span>Simpan & Kirim Sekarang (In-Transit)</span>
            </button>
        </div>
    </form>
</div>

<script>
    let rowIndex = 1;
    function addTransferRow() {
        const tbody = document.getElementById('transfer-items-body');
        const firstRow = tbody.querySelector('.transfer-item-row');
        if (!firstRow) return;

        const clone = firstRow.cloneNode(true);
        const select = clone.querySelector('select');
        const qtyInput = clone.querySelector('input[type="number"]');
        const noteInput = clone.querySelector('input[type="text"]');

        select.name = `items[${rowIndex}][product_id]`;
        select.value = '';
        qtyInput.name = `items[${rowIndex}][quantity]`;
        qtyInput.value = 1;
        noteInput.name = `items[${rowIndex}][notes]`;
        noteInput.value = '';

        tbody.appendChild(clone);
        rowIndex++;
    }

    function removeTransferRow(btn) {
        const tbody = document.getElementById('transfer-items-body');
        if (tbody.querySelectorAll('.transfer-item-row').length <= 1) {
            return;
        }
        btn.closest('.transfer-item-row').remove();
    }
</script>
@endsection
