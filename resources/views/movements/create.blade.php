@extends('layouts.app')

@section('title', 'InvenTrack | Log Stock Movement')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-on-surface-variant hover:text-primary mb-3 text-sm font-semibold transition no-underline">
            <span class="material-symbols-outlined text-base">arrow_back</span> Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-2xl font-extrabold text-on-surface tracking-tight" id="page-title">
                {{ $selectedType === 'out' ? 'Log Stock Out Movement' : 'Log Stock In Movement' }}
            </h1>
            @if($selectedType === 'out')
                <span class="inline-flex items-center gap-1.5 bg-error-container text-on-error-container px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm font-bold">north_east</span>
                    Stock Out (Barang Keluar)
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm font-bold">south_west</span>
                    Stock In (Barang Masuk)
                </span>
            @endif
        </div>
        <p class="text-on-surface-variant text-sm mt-1">Catat transaksi mutasi stok barang. Sistem akan otomatis memperbarui stok fisik toko dan HPP rata-rata.</p>
    </div>

    <!-- Form Card -->
    <section class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 sm:p-8 shadow-xs">
        <form id="log-movement-form" action="{{ route('movements.store') }}" method="POST" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="type" id="type" value="{{ $selectedType }}">

            <!-- Product Dropdown Selection -->
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label for="product_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        PILIH PRODUK / ITEM <span class="text-error font-bold">*</span>
                    </label>
                    @if($selectedType === 'in')
                        <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            Bisa pilih banyak produk sekaligus
                        </span>
                    @endif
                </div>
                <select name="{{ $selectedType === 'in' ? 'product_ids[]' : 'product_id' }}" id="product_id" {{ $selectedType === 'in' ? 'multiple' : '' }} required 
                        class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:border-primary transition-all @error('product_id') border-error @enderror"
                        placeholder="Cari & pilih produk...">
                    @if($selectedType !== 'in')
                        <option value="" disabled {{ !$selectedProductId ? 'selected' : '' }}>-- Pilih Produk --</option>
                    @endif
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" 
                                {{ (old('product_id') == $product->id || $selectedProductId == $product->id) ? 'selected' : '' }}
                                data-stock="{{ $product->stock }}"
                                data-purchase="{{ $product->purchase_price }}"
                                data-selling="{{ $product->selling_price }}">
                            {{ $product->name }}{{ !empty($product->variant) ? ' - ' . $product->variant : '' }} ({{ $product->sku }}) • Beli: Rp {{ number_format($product->purchase_price, 0, ',', '.') }} • Jual: Rp {{ number_format($product->selling_price, 0, ',', '.') }} • Stok: {{ number_format($product->stock) }}
                        </option>
                    @endforeach
                </select>
                <div id="create_price_match_indicator" class="mt-2 hidden"></div>
                @error('product_id')
                    <span class="text-error text-xs font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                @if($selectedType === 'in')
                <!-- Quantity Input -->
                <div class="flex flex-col gap-1.5">
                    <label for="quantity" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        JUMLAH MASUK (QTY) <span class="text-error font-bold">*</span>
                    </label>
                    <div class="relative flex items-center">
                        <input type="number" min="1" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" 
                               class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('quantity') border-error @enderror" 
                               placeholder="Minimal 1" required/>
                        <span class="absolute right-3.5 text-xs font-bold text-on-surface-variant/60 pointer-events-none">pcs</span>
                    </div>
                    @error('quantity')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Purchase Price Input (Modal/HPP) -->
                <div class="flex flex-col gap-1.5">
                    <label for="purchase_price" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        HARGA BELI / MODAL PER UNIT <span class="text-error font-bold">*</span>
                    </label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-xs font-bold text-on-surface-variant/70 pointer-events-none">Rp</span>
                        <input type="number" min="0" step="0.01" name="purchase_price" id="purchase_price" value="{{ old('purchase_price') }}" 
                               class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('purchase_price') border-error @enderror" 
                               placeholder="Contoh: 85000"/>
                    </div>
                    @error('purchase_price')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Date Movement -->
                <div class="flex flex-col gap-1.5">
                    <label for="movement_date" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        TANGGAL TRANSAKSI <span class="text-error font-bold">*</span>
                    </label>
                    <input type="date" name="movement_date" id="movement_date" value="{{ old('movement_date', date('Y-m-d')) }}" 
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('movement_date') border-error @enderror" required/>
                    @error('movement_date')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Payment Method (Stock In) -->
                <div class="flex flex-col gap-1.5">
                    <label for="payment_method_in" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        METODE PEMBAYARAN <span class="text-error font-bold">*</span>
                    </label>
                    <select name="payment_method" id="payment_method_in" onchange="toggleInTempoDueDate(this.value)"
                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition cursor-pointer">
                        <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Tunai / Kas Toko (Langsung Lunas)</option>
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank (Langsung Lunas)</option>
                        <option value="tempo" {{ old('payment_method') === 'tempo' ? 'selected' : '' }}>Tempo / Hutang Supplier (Bayar Nanti / AP)</option>
                    </select>
                </div>

                <!-- Supplier Name (Khusus Stock In) -->
                <div id="supplier-col" class="flex flex-col gap-1.5 {{ old('payment_method') === 'tempo' ? 'sm:col-span-1' : 'sm:col-span-2' }} transition-all">
                    <div class="flex items-center justify-between">
                        <label for="supplier_name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-[#006c49]">storefront</span>
                            <span>NAMA SUPPLIER / DISTRIBUTOR</span>
                        </label>
                        <span class="text-[10px] font-semibold text-[#006c49] bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                            Pilih / Ketik Baru
                        </span>
                    </div>
                    <select name="supplier_name" id="supplier_name" class="w-full">
                        <option value="">-- Pilih atau Ketik Nama Supplier Baru --</option>
                        @if(isset($suppliers))
                            @foreach($suppliers as $sName)
                                <option value="{{ $sName }}" {{ old('supplier_name') === $sName ? 'selected' : '' }}>{{ $sName }}</option>
                            @endforeach
                        @endif
                        @if(old('supplier_name') && (!isset($suppliers) || !$suppliers->contains(old('supplier_name'))))
                            <option value="{{ old('supplier_name') }}" selected>{{ old('supplier_name') }}</option>
                        @endif
                    </select>
                    <p class="text-[11px] text-on-surface-variant/70">Pilih dari Master Data atau ketik baru (otomatis tersimpan ke Pengaturan Sistem).</p>
                    @error('supplier_name')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Due Date (Khusus Tempo) -->
                <div id="due-date-col" class="flex flex-col gap-1.5 {{ old('payment_method') === 'tempo' ? '' : 'hidden' }} transition-all">
                    <div class="flex items-center justify-between">
                        <label for="due_date" class="block text-xs font-bold text-amber-950 uppercase tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-amber-700">event_busy</span>
                            <span>TANGGAL JATUH TEMPO (DUE DATE) <span class="text-error font-bold">*</span></span>
                        </label>
                        <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">
                            Hutang AP
                        </span>
                    </div>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" 
                           class="w-full px-4 py-2.5 bg-white border border-amber-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 transition @error('due_date') border-error @enderror"/>
                    @error('due_date')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Tempo Info Banner -->
                <div id="tempo-ap-banner" class="{{ old('payment_method') === 'tempo' ? '' : 'hidden' }} sm:col-span-2 p-3.5 bg-amber-50/90 border border-amber-200 rounded-xl text-xs text-amber-950 flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-amber-700 text-lg shrink-0 mt-0.5">account_balance_wallet</span>
                    <div class="leading-relaxed">
                        Pengadaan bertempo ini akan otomatis dicatat sebagai <strong>Hutang Dagang (2101)</strong> saat di-approve. Anda dapat memantau dan melunasi tagihannya di menu <strong>Accounting &rarr; Hutang Supplier</strong>.
                    </div>
                </div>

                <!-- Moving Average Simulation Card -->
                <div id="hpp_preview_container" class="hidden sm:col-span-2 bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-950 transition-all">
                    <div class="font-bold flex items-center gap-1.5 mb-1 text-emerald-800">
                        <span class="material-symbols-outlined text-base font-bold">calculate</span>
                        Simulasi Perhitungan Moving Average HPP
                    </div>
                    <div id="hpp_preview_text" class="leading-relaxed text-emerald-900"></div>
                </div>
                @else
                <!-- Stock Out Fields -->
                <div class="flex flex-col gap-1.5">
                    <label for="quantity" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        JUMLAH KELUAR (QTY) <span class="text-error font-bold">*</span>
                    </label>
                    <div class="relative flex items-center">
                        <input type="number" min="1" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" 
                               class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('quantity') border-error @enderror" 
                               placeholder="Minimal 1" required/>
                        <span class="absolute right-3.5 text-xs font-bold text-on-surface-variant/60 pointer-events-none">pcs</span>
                    </div>
                    @error('quantity')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div id="payment-method-container" class="flex flex-col gap-1.5">
                    <label for="payment_method" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        METODE PEMBAYARAN <span class="text-error font-bold">*</span>
                    </label>
                    <select name="payment_method" id="payment_method"
                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition cursor-pointer">
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                        <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="debit" {{ old('payment_method') === 'debit' ? 'selected' : '' }}>Kartu Debit</option>
                        <option value="credit" {{ old('payment_method') === 'credit' ? 'selected' : '' }}>Kartu Kredit</option>
                        <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="movement_date" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        TANGGAL TRANSAKSI <span class="text-error font-bold">*</span>
                    </label>
                    <input type="date" name="movement_date" id="movement_date" value="{{ old('movement_date', date('Y-m-d')) }}" 
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('movement_date') border-error @enderror" required/>
                    @error('movement_date')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="customer_name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        NAMA PELANGGAN <span class="text-[10px] font-normal text-on-surface-variant/70 lowercase">(opsional)</span>
                    </label>
                    <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" 
                           class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition" 
                           placeholder="Nama pembeli / pelanggan..."/>
                </div>
                @endif

                <!-- Notes / Description -->
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="notes" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        CATATAN / KETERANGAN
                    </label>
                    <textarea name="notes" id="notes" rows="2" 
                              class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition @error('notes') border-error @enderror resize-y" 
                              placeholder="Referensi faktur, supplier, no. invoice, atau catatan mutasi...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-error text-xs font-medium">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant pt-5 mt-2">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-outline-variant text-on-surface font-bold rounded-xl text-sm hover:bg-surface-container-low transition-all no-underline">
                    Batal
                </a>
                <button type="submit" id="submit-btn" class="px-6 py-2.5 {{ $selectedType === 'out' ? 'bg-primary text-on-primary' : 'bg-emerald-600 text-white' }} font-bold rounded-xl text-sm hover:opacity-90 transition-all active:scale-95 shadow-sm inline-flex items-center gap-2 cursor-pointer border-0">
                    <span class="material-symbols-outlined text-base" id="submit-icon">{{ $selectedType === 'out' ? 'north_east' : 'south_west' }}</span>
                    <span id="submit-text">{{ $selectedType === 'out' ? 'Record Stock Out' : 'Simpan Stock In' }}</span>
                </button>
            </div>
        </form>
    </section>
</div>

@push('scripts')
<style>
    /* Olsera-like multiple select chips */
    .ts-control {
        max-height: 125px !important;
        overflow-y: auto !important;
    }
    .ts-control .item {
        background-color: #f0fdf4 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        border-radius: 0.5rem !important;
        padding: 0.25rem 0.55rem !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        margin: 0.15rem 0.25rem 0.15rem 0 !important;
    }
    .ts-control .item .remove {
        color: #047857 !important;
        font-weight: 800 !important;
        text-decoration: none !important;
        border-left: 1px solid #6ee7b7 !important;
        padding-left: 0.35rem !important;
        margin-left: 0.25rem !important;
    }
    .ts-control .item .remove:hover {
        color: #dc2626 !important;
        background: transparent !important;
    }
</style>
<script>
    const isStockIn = '{{ $selectedType }}' === 'in';
    let createTomSelect = null;

    function formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num);
    }

    function toggleInTempoDueDate(val) {
        const supplierCol = document.getElementById('supplier-col');
        const dueDateCol = document.getElementById('due-date-col');
        const tempoBanner = document.getElementById('tempo-ap-banner');
        const input = document.getElementById('due_date');

        const isTempo = (val === 'tempo' || val === 'hutang');

        if (dueDateCol) {
            if (isTempo) {
                dueDateCol.classList.remove('hidden');
                if (input && !input.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 30);
                    input.value = d.toISOString().split('T')[0];
                }
            } else {
                dueDateCol.classList.add('hidden');
            }
        }

        if (supplierCol) {
            if (isTempo) {
                supplierCol.classList.remove('sm:col-span-2');
                supplierCol.classList.add('sm:col-span-1');
            } else {
                supplierCol.classList.remove('sm:col-span-1');
                supplierCol.classList.add('sm:col-span-2');
            }
        }

        if (tempoBanner) {
            if (isTempo) {
                tempoBanner.classList.remove('hidden');
            } else {
                tempoBanner.classList.add('hidden');
            }
        }
    }

    function updateMovementUI(type) {
        const title = document.getElementById('page-title');
        const submitBtn = document.getElementById('submit-btn');
        const submitText = document.getElementById('submit-text');
        const submitIcon = document.getElementById('submit-icon');

        if (type === 'out') {
            if (title) title.textContent = 'Log Stock Out Movement';
            if (submitText) submitText.textContent = 'Record Stock Out';
            if (submitIcon) submitIcon.textContent = 'north_east';
            if (submitBtn) submitBtn.className = 'px-6 py-2 bg-primary text-on-primary font-bold rounded-lg hover:opacity-90 transition-all active:scale-95 shadow-sm inline-flex items-center gap-2';
        } else {
            if (title) title.textContent = 'Log Stock In Movement';
            if (submitText) submitText.textContent = 'Record Stock In';
            if (submitIcon) submitIcon.textContent = 'south_west';
            if (submitBtn) submitBtn.className = 'px-6 py-2 bg-secondary-container text-on-secondary-container font-bold rounded-lg hover:opacity-90 transition-all active:scale-95 shadow-sm inline-flex items-center gap-2';
        }

        const pmContainer = document.getElementById('payment-method-container');
        if (pmContainer && !isStockIn) {
            if (type === 'out') {
                pmContainer.classList.remove('hidden');
            } else {
                pmContainer.classList.add('hidden');
            }
        }
    }

    // Trigger on page load in case of redirects or default parameters
    document.addEventListener('DOMContentLoaded', () => {
        updateMovementUI(document.getElementById('type').value);

        if (isStockIn) {
            const inPm = document.getElementById('payment_method_in');
            if (inPm) {
                toggleInTempoDueDate(inPm.value);
            }
        }
        
        const form = document.getElementById('log-movement-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const qty = parseFloat(document.getElementById('quantity').value);
                if (isNaN(qty) || qty <= 0) {
                    e.preventDefault();
                    alert('Quantity tidak bisa 0! Silakan masukkan jumlah yang valid (minimal 1).');
                    return false;
                }
            });
        }

        function updateHppSimulation() {
            const previewContainer = document.getElementById('hpp_preview_container');
            const previewText = document.getElementById('hpp_preview_text');
            if (!previewContainer || !previewText || !isStockIn) return;

            const select = document.getElementById('product_id');
            const selectedVals = createTomSelect ? createTomSelect.getValue() : [];
            const valArray = Array.isArray(selectedVals) ? selectedVals : (selectedVals ? [selectedVals] : []);

            if (valArray.length === 0) {
                previewContainer.classList.add('hidden');
                return;
            }

            const incomingQty = parseFloat(document.getElementById('quantity')?.value || 0);
            const purchaseInput = document.getElementById('purchase_price');
            const incomingPrice = purchaseInput ? parseFloat(purchaseInput.value || 0) : 0;

            if (incomingQty <= 0 || incomingPrice <= 0) {
                previewContainer.classList.add('hidden');
                return;
            }

            if (valArray.length === 1) {
                const selectedOption = select.querySelector(`option[value="${valArray[0]}"]`);
                const currentStock = parseFloat(selectedOption?.dataset?.stock || 0);
                const currentCost = parseFloat(selectedOption?.dataset?.purchase || 0);

                let newCost = incomingPrice;
                const totalNewStock = (currentStock > 0 ? currentStock : 0) + incomingQty;

                if (currentStock > 0) {
                    const oldVal = currentStock * currentCost;
                    const newVal = incomingQty * incomingPrice;
                    newCost = (oldVal + newVal) / totalNewStock;
                }

                previewText.innerHTML = `
                    <div class="space-y-1">
                        <div>• Stok saat ini: <strong>${formatRupiah(currentStock)} pcs</strong> @ Rp ${formatRupiah(currentCost)} (Total: Rp ${formatRupiah(currentStock * currentCost)})</div>
                        <div>• Pembelian baru: <strong>${formatRupiah(incomingQty)} pcs</strong> @ Rp ${formatRupiah(incomingPrice)} (Total: Rp ${formatRupiah(incomingQty * incomingPrice)})</div>
                        <div class="pt-1.5 border-t border-emerald-300 font-semibold text-emerald-950">
                            ➔ Estimasi HPP Rata-rata Baru: <span class="text-sm font-bold text-emerald-700">Rp ${formatRupiah(newCost)} / pcs</span> (Total Stok Menjadi: ${formatRupiah(totalNewStock)} pcs)
                        </div>
                    </div>
                `;
            } else {
                const totalCount = valArray.length;
                const totalUnits = totalCount * incomingQty;
                const grandTotalVal = totalUnits * incomingPrice;

                previewText.innerHTML = `
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-1.5 font-bold text-emerald-950 text-xs">
                            <span class="material-symbols-outlined text-base text-emerald-700">fact_check</span>
                            <span>Mode Multi-Produk (${totalCount} produk dipilih):</span>
                        </div>
                        <div>• Masing-masing produk masuk: <strong>+${formatRupiah(incomingQty)} pcs</strong> @ Rp ${formatRupiah(incomingPrice)}</div>
                        <div class="pt-1.5 border-t border-emerald-300 font-bold text-emerald-900 flex justify-between items-center text-xs">
                            <span>Total Akumulasi: <strong>${formatRupiah(totalUnits)} unit</strong></span>
                            <span class="text-sm font-extrabold text-emerald-800">Rp ${formatRupiah(grandTotalVal)}</span>
                        </div>
                    </div>
                `;
            }
            previewContainer.classList.remove('hidden');
        }

        const purchaseInput = document.getElementById('purchase_price');
        if (purchaseInput) {
            purchaseInput.addEventListener('input', updateHppSimulation);
        }
        const qtyInput = document.getElementById('quantity');
        if (qtyInput) {
            qtyInput.addEventListener('input', updateHppSimulation);
        }

        createTomSelect = new TomSelect('#product_id', {
            plugins: ['remove_button'],
            create: false,
            maxItems: isStockIn ? null : 1,
            placeholder: isStockIn ? 'Cari & pilih produk (bisa multiple)...' : '-- Choose an Item --',
            sortField: {
                field: "text",
                direction: "asc"
            },
            render: {
                item: function(data, escape) {
                    const title = (data.text || '').split(' • ')[0] || data.text;
                    return '<div>' + escape(title) + '</div>';
                },
                option: function(data, escape) {
                    const parts = (data.text || '').split(' • ');
                    const name = parts[0] || data.text;
                    const details = parts.slice(1).join(' • ');
                    return `
                        <div class="py-1 px-1">
                            <div class="font-medium text-slate-900">${escape(name)}</div>
                            ${details ? `<div class="text-[11px] text-slate-500 mt-0.5">${escape(details)}</div>` : ''}
                        </div>
                    `;
                }
            },
            onChange: function(values) {
                const valArray = Array.isArray(values) ? values : (values ? [values] : []);
                const originalSelect = document.getElementById('product_id');
                const indicator = document.getElementById('create_price_match_indicator');

                if (valArray.length === 0) {
                    if (indicator) indicator.classList.add('hidden');
                } else if (valArray.length === 1) {
                    const selectedOption = originalSelect.querySelector(`option[value="${valArray[0]}"]`);
                    if (selectedOption) {
                        const buy = parseFloat(selectedOption.dataset.purchase || 0);
                        const sell = parseFloat(selectedOption.dataset.selling || 0);
                        const pInput = document.getElementById('purchase_price');
                        if (pInput && !pInput.value) {
                            pInput.value = buy || '';
                        }
                        if (indicator) {
                            indicator.className = 'mt-2 text-xs p-2.5 rounded-lg border bg-surface-container-low border-outline-variant flex items-center gap-1.5 text-on-surface-variant';
                            indicator.innerHTML = `<span class="material-symbols-outlined text-sm text-primary">info</span> <span>Harga Master: <strong>Beli Rp ${formatRupiah(buy)}</strong> | <strong>Jual Rp ${formatRupiah(sell)}</strong></span>`;
                            indicator.classList.remove('hidden');
                        }
                    }
                } else {
                    let allSameBuy = true;
                    let allSameSell = true;
                    const firstOpt = originalSelect.querySelector(`option[value="${valArray[0]}"]`);
                    const firstBuy = parseFloat(firstOpt?.dataset?.purchase || 0);
                    const firstSell = parseFloat(firstOpt?.dataset?.selling || 0);

                    valArray.forEach(id => {
                        const opt = originalSelect.querySelector(`option[value="${id}"]`);
                        if (opt) {
                            if (parseFloat(opt.dataset.purchase || 0) !== firstBuy) allSameBuy = false;
                            if (parseFloat(opt.dataset.selling || 0) !== firstSell) allSameSell = false;
                        }
                    });

                    if (indicator) {
                        if (allSameBuy && allSameSell) {
                            indicator.className = 'mt-2 text-xs p-2.5 rounded-lg border bg-emerald-50 border-emerald-200 text-emerald-800 flex items-center gap-1.5';
                            indicator.innerHTML = `<span class="material-symbols-outlined text-sm text-emerald-600">verified</span> <span><strong>Harga Sama:</strong> Semua ${valArray.length} produk terpilih memiliki Harga Beli (Rp ${formatRupiah(firstBuy)}) & Jual (Rp ${formatRupiah(firstSell)}) yang seragam.</span>`;
                        } else if (allSameBuy) {
                            indicator.className = 'mt-2 text-xs p-2.5 rounded-lg border bg-emerald-50 border-emerald-200 text-emerald-800 flex items-center gap-1.5';
                            indicator.innerHTML = `<span class="material-symbols-outlined text-sm text-emerald-600">verified</span> <span><strong>Harga Beli Sama:</strong> Semua ${valArray.length} produk terpilih memiliki Harga Beli master Rp ${formatRupiah(firstBuy)}.</span>`;
                        } else {
                            indicator.className = 'mt-2 text-xs p-2.5 rounded-lg border bg-amber-50 border-amber-200 text-amber-800 flex items-start gap-1.5';
                            indicator.innerHTML = `<span class="material-symbols-outlined text-sm text-amber-600 mt-0.5">warning</span> <div><strong>Perhatian:</strong> Beberapa produk yang dipilih memiliki harga beli master yang berbeda. Nominal Harga Beli yang Anda input di form akan diterapkan untuk seluruh produk ini.</div>`;
                        }
                        indicator.classList.remove('hidden');
                    }
                }
                updateHppSimulation();
            }
        });

        if (document.getElementById('supplier_name')) {
            new TomSelect('#supplier_name', {
                create: true,
                createOnBlur: true,
                persist: false,
                allowEmptyOption: true,
                maxOptions: 500,
                placeholder: 'Pilih atau ketik supplier baru...',
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results py-2.5 px-3 text-xs text-on-surface-variant font-medium">Tekan Enter untuk menambah <strong>"' + escape(data.input) + '"</strong> ke Pengaturan Sistem</div>';
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
