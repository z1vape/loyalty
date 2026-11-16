@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buat Purchase Order Baru')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">

    {{-- Breadcrumb / Back --}}
    <div>
        <a href="{{ route('purchasing.purchase-orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Kembali ke Daftar Purchase Order</span>
        </a>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-black text-on-surface">Buat Purchase Order (PO Supplier)</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">Terbitkan surat pesanan resmi pengadaan barang ke supplier / distributor.</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('purchasing.purchase-orders.store') }}" method="POST" id="poForm" class="space-y-6">
        @csrf

        {{-- Section 0: Pemilihan Purchase Request yang Sudah Disetujui (Approved) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">assignment_turned_in</span>
                </div>
                <div>
                    <label for="purchase_request_id" class="font-bold text-xs text-on-surface block">Pilih Nomor Order Barang (Purchase Request Disetujui):</label>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Memilih PR yang telah disetujui akan otomatis memilih cabang penerima & mengisi rincian produk yang dipesan.</p>
                </div>
            </div>
            <div class="w-full md:w-72 shrink-0">
                <select 
                    name="purchase_request_id" 
                    id="purchase_request_id" 
                    onchange="onSelectApprovedPr(this.value)" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-on-surface focus:outline-none focus:border-primary shadow-2xs cursor-pointer"
                >
                    <option value="">-- PO Bebas (Tanpa PR) --</option>
                    @foreach($availablePrs as $apr)
                        @php
                            $aprPcs = $apr->items->sum('quantity_requested');
                        @endphp
                        <option 
                            value="{{ $apr->id }}" 
                            {{ (string)($sourcePr?->id ?? '') === (string)$apr->id ? 'selected' : '' }}
                        >
                            {{ $apr->pr_number }} ({{ $aprPcs }} pcs)
                        </option>
                    @endforeach
                </select>
                @if($availablePrs->isEmpty())
                    <p class="text-[11px] text-amber-700 font-medium mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">info</span>
                        <span>Tidak ada Purchase Request berstatus Approved untuk cabang ini.</span>
                    </p>
                @endif
            </div>
        </div>

        {{-- Section 1: Data Pengadaan & Supplier --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="border-b border-outline-variant/60 pb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">store</span>
                <h3 class="font-bold text-sm text-on-surface">Informasi Supplier & Cabang Tujuan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Cabang Toko (Otomatis terpilih & disabled saat PR dipilih) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-on-surface">Cabang Penerima Barang: <span class="text-rose-500">*</span></label>
                        <span id="store_locked_badge" class="hidden text-[10px] font-bold text-emerald-700 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                            Terkunci (Sesuai Cabang PR)
                        </span>
                    </div>
                    <!-- Hidden input to ensure store_id is always submitted even when select is disabled -->
                    <input type="hidden" name="store_id" id="hidden_store_id" value="{{ $selectedStoreId }}">
                    <select id="store_id_display" onchange="onStoreChange(this.value)" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ (string)($selectedStoreId ?? '') === (string)$st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Supplier Name (Dari Master Data / Pengaturan Sistem) -->
                <div>
                    <label for="supplier_name" class="block text-xs font-bold text-on-surface mb-1">Nama Supplier / Distributor: <span class="text-rose-500">*</span></label>
                    <select 
                        name="supplier_name" 
                        id="supplier_name"
                        required 
                        class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                    >
                        <option value="">-- Pilih Nama Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup }}" {{ old('supplier_name') === $sup ? 'selected' : '' }}>{{ $sup }}</option>
                        @endforeach
                        @if(old('supplier_name') && !$suppliers->contains(old('supplier_name')))
                            <option value="{{ old('supplier_name') }}" selected>{{ old('supplier_name') }}</option>
                        @endif
                    </select>
                </div>

                <!-- Tanggal PO -->
                <div>
                    <label for="po_date" class="block text-xs font-bold text-on-surface mb-1">Tanggal Surat PO: <span class="text-rose-500">*</span></label>
                    <input type="date" name="po_date" id="po_date" required value="{{ date('Y-m-d') }}" onchange="recalculateEstimatedPaymentDate()" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label for="payment_method" class="block text-xs font-bold text-on-surface mb-1">Syarat / Metode Pembayaran: <span class="text-rose-500">*</span></label>
                    <select name="payment_method" id="payment_method" onchange="onPaymentMethodChange(this.value)" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary cursor-pointer">
                        <option value="tempo" selected>Tempo / Kredit (Hutang Dagang)</option>
                        <option value="transfer">Transfer Bank (Lunas)</option>
                        <option value="cash">Tunai / Cash (Lunas)</option>
                    </select>
                </div>

                <!-- Tempo Hari -->
                <div>
                    <label for="payment_term_days" class="block text-xs font-bold text-on-surface mb-1">Term Tempo (Hari):</label>
                    <input type="number" name="payment_term_days" id="payment_term_days" min="0" value="14" oninput="recalculateEstimatedPaymentDate()" placeholder="Contoh: 14 atau 30 hari" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Estimasi Bayar -->
                <div>
                    <label for="expected_date" class="block text-xs font-bold text-on-surface mb-1">Estimasi Bayar (Jatuh Tempo):</label>
                    <input type="date" name="expected_date" id="expected_date" value="{{ date('Y-m-d', strtotime('+14 days')) }}" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan Tambahan / Instruksi Pengiriman:</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Contoh: Pengiriman via ekspedisi langganan, sertakan invoice fisik..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary resize-none">{{ $sourcePr ? 'Diteruskan dari Order Barang #' . $sourcePr->pr_number . ($sourcePr->notes ? ' - ' . $sourcePr->notes : '') : '' }}</textarea>
            </div>
        </div>

        {{-- Section 2: Daftar Item PO (Combobox Catalog / Manual Input) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="border-b border-outline-variant/60 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                    <div>
                        <h3 class="font-bold text-sm text-on-surface">Rincian Barang yang Dipesan ke Supplier</h3>
                        <p class="text-[11px] text-on-surface-variant">Pilih produk dari katalog toko atau ketik langsung nama barang manual pada combobox.</p>
                    </div>
                </div>

                <button 
                    type="button" 
                    onclick="addItemRow()" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-xl text-xs transition-colors cursor-pointer w-fit"
                >
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Tambah Baris Barang</span>
                </button>
            </div>

            <div class="overflow-x-visible">
                <table class="w-full text-left text-xs border-collapse" id="itemsTable">
                    <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="py-2.5 px-3 w-8 text-center">#</th>
                            <th class="py-2.5 px-3 min-w-[300px]">Produk / Nama Barang (Combobox)</th>
                            <th class="py-2.5 px-3 w-28 text-center">Kuantitas</th>
                            <th class="py-2.5 px-3 w-40 text-right">Harga Beli Satuan (Rp)</th>
                            <th class="py-2.5 px-3 w-40 text-right">Subtotal (Rp)</th>
                            <th class="py-2.5 px-3 min-w-[160px]">Catatan Item</th>
                            <th class="py-2.5 px-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60" id="itemsTbody">
                        {{-- Rows will be injected by JavaScript --}}
                    </tbody>
                    <tfoot class="border-t-2 border-outline-variant/80 font-bold bg-surface-container-low/60">
                        <tr>
                            <td colspan="4" class="py-3 px-3 text-right uppercase tracking-wider text-[11px] text-on-surface">Total Estimasi Nilai PO:</td>
                            <td class="py-3 px-3 text-right font-mono text-base font-black text-emerald-800" id="grandTotalLabel">Rp 0</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('purchasing.purchase-orders.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-[#00553a] text-on-primary text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                Terbitkan Purchase Order (PO) &rarr;
            </button>
        </div>
    </form>

</div>

<script>
    // Master data passed from server
    const allStoreProducts = @json($allStoreProducts ?? []);
    const availablePrsData = @json($availablePrs ?? []);
    let initialSourcePr = @json($sourcePr ?? null);

    let rowCounter = 0;

    function getStoreProducts() {
        const storeId = document.getElementById('hidden_store_id').value;
        return allStoreProducts.filter(p => !storeId || String(p.store_id) === String(storeId));
    }

    function createRowHtml(index, item = null) {
        const productId = (item && item.product_id) ? item.product_id : (item && item.product && item.product.id ? item.product.id : '');
        const prItemId = (item && item.id) ? item.id : (item && item.purchase_request_item_id ? item.purchase_request_item_id : '');
        const itemName = (item && item.product && item.product.name) ? item.product.name : (item && item.custom_item_name ? item.custom_item_name : '');
        const qty = item ? (item.quantity_requested || item.quantity_ordered || 1) : 1;
        const price = item ? (item.item_price || item.unit_price || item.estimated_price || (item.product ? (item.product.purchase_price || item.product.price || 0) : 0)) : 0;
        const notes = (item && item.notes) ? item.notes : '';
        const subtotal = qty * price;

        return `
            <tr class="item-row">
                <td class="py-2.5 px-3 text-center text-on-surface-variant font-bold row-index"></td>
                <td class="py-2.5 px-3 relative">
                    <div class="relative product-combobox-wrapper">
                        <input type="hidden" name="items[${index}][product_id]" class="input-product-id" value="${productId}">
                        <input type="hidden" name="items[${index}][purchase_request_item_id]" class="input-pr-item-id" value="${prItemId}">
                        <div class="relative">
                            <input 
                                type="text" 
                                name="items[${index}][custom_item_name]" 
                                class="w-full pl-3 pr-8 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary input-item-search" 
                                placeholder="Ketik cari produk atau nama manual..." 
                                value="${escapeHtml(itemName)}"
                                autocomplete="off"
                                onfocus="openComboboxDropdown(this)"
                                oninput="onComboboxInput(this)"
                                onkeydown="onComboboxKeydown(event, this)"
                            >
                            <span class="material-symbols-outlined text-base text-on-surface-variant absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">unfold_more</span>
                        </div>
                        <div class="product-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 max-h-56 overflow-y-auto bg-surface-container-lowest border border-outline-variant rounded-xl shadow-2xl z-50 divide-y divide-outline-variant/30">
                        </div>
                    </div>
                </td>
                <td class="py-2.5 px-3">
                    <input 
                        type="number" 
                        name="items[${index}][quantity_ordered]" 
                        required 
                        min="1" 
                        value="${qty}" 
                        oninput="calculateTotals()"
                        class="w-full text-center px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-bold input-qty"
                    >
                </td>
                <td class="py-2.5 px-3">
                    <input 
                        type="number" 
                        name="items[${index}][unit_price]" 
                        required 
                        min="0" 
                        value="${price}" 
                        oninput="calculateTotals()"
                        class="w-full text-right px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-mono font-bold input-price"
                    >
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-800 row-subtotal">
                    Rp ${subtotal.toLocaleString('id-ID')}
                </td>
                <td class="py-2.5 px-3">
                    <input 
                        type="text" 
                        name="items[${index}][notes]" 
                        value="${escapeHtml(notes)}" 
                        placeholder="Catatan..." 
                        class="w-full px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-[11px]"
                    >
                </td>
                <td class="py-2.5 px-3 text-center">
                    <button type="button" onclick="removeItemRow(this)" class="p-1 text-on-surface-variant hover:text-rose-600 cursor-pointer">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </td>
            </tr>
        `;
    }

    function addItemRow(itemData = null) {
        const tbody = document.getElementById('itemsTbody');
        const tempDiv = document.createElement('tbody');
        tempDiv.innerHTML = createRowHtml(rowCounter++, itemData);
        const tr = tempDiv.firstElementChild;
        tbody.appendChild(tr);
        updateRowIndices();
        calculateTotals();
        return tr;
    }

    function removeItemRow(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            alert('Minimal harus ada 1 item barang dalam Purchase Order.');
            return;
        }
        btn.closest('tr').remove();
        updateRowIndices();
        calculateTotals();
    }

    function updateRowIndices() {
        document.querySelectorAll('.item-row').forEach((row, i) => {
            row.querySelector('.row-index').textContent = i + 1;
        });
    }

    function calculateTotals() {
        let grandTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
            const price = parseFloat(row.querySelector('.input-price').value) || 0;
            const subtotal = qty * price;
            grandTotal += subtotal;
            row.querySelector('.row-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
        });
        document.getElementById('grandTotalLabel').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    // Combobox functions
    function openComboboxDropdown(input) {
        closeAllComboboxes();
        const wrapper = input.closest('.product-combobox-wrapper');
        const menu = wrapper.querySelector('.product-dropdown-menu');
        renderComboboxOptions(input, menu);
        menu.classList.remove('hidden');
    }

    function onComboboxInput(input) {
        const wrapper = input.closest('.product-combobox-wrapper');
        const menu = wrapper.querySelector('.product-dropdown-menu');
        const hiddenId = wrapper.querySelector('.input-product-id');
        // When typing manually, invalidate product_id until an exact catalog item is clicked
        hiddenId.value = '';
        renderComboboxOptions(input, menu);
        menu.classList.remove('hidden');
    }

    function onComboboxKeydown(event, input) {
        if (event.key === 'Escape') {
            closeAllComboboxes();
        }
    }

    function renderComboboxOptions(input, menu) {
        const storeProducts = getStoreProducts();
        const query = (input.value || '').trim().toLowerCase();
        
        let filtered = storeProducts;
        if (query) {
            filtered = storeProducts.filter(p => 
                (p.name && p.name.toLowerCase().includes(query)) || 
                (p.sku && p.sku.toLowerCase().includes(query))
            );
        }

        let html = '';
        if (filtered.length > 0) {
            html += filtered.slice(0, 40).map(p => {
                const price = p.purchase_price || p.price || 0;
                return `
                    <div 
                        class="p-2.5 hover:bg-surface-container cursor-pointer transition-colors flex items-center justify-between gap-2"
                        onmousedown="selectCatalogProduct(this, ${p.id}, '${escapeAttr(p.name)}', ${price})"
                    >
                        <div class="min-w-0">
                            <span class="font-bold text-xs text-on-surface block truncate">${escapeHtml(p.name)}</span>
                            <span class="text-[10px] text-on-surface-variant block">SKU: ${escapeHtml(p.sku || '-')} &bull; Stok: ${p.stock || 0} pcs</span>
                        </div>
                        <span class="font-mono font-bold text-xs text-primary shrink-0">
                            Rp ${price.toLocaleString('id-ID')}
                        </span>
                    </div>
                `;
            }).join('');
        }

        if (query.length > 0) {
            html += `
                <div 
                    class="p-2.5 hover:bg-amber-500/10 cursor-pointer transition-colors border-t border-outline-variant/60 flex items-center gap-2 text-amber-800"
                    onmousedown="selectManualProduct(this, '${escapeAttr(input.value)}')"
                >
                    <span class="material-symbols-outlined text-base text-amber-600">edit_note</span>
                    <span class="text-xs font-semibold">Gunakan nama manual: "<strong>${escapeHtml(input.value)}</strong>" (Non-Katalog)</span>
                </div>
            `;
        } else if (filtered.length === 0) {
            html += `
                <div class="p-3 text-center text-xs text-on-surface-variant">
                    Tidak ada produk katalog di cabang ini. Ketik nama untuk input barang manual.
                </div>
            `;
        }

        menu.innerHTML = html;
    }

    function selectCatalogProduct(elem, id, name, price) {
        const wrapper = elem.closest('.product-combobox-wrapper');
        const row = elem.closest('tr');
        wrapper.querySelector('.input-item-search').value = name;
        wrapper.querySelector('.input-product-id').value = id;
        row.querySelector('.input-price').value = price;
        wrapper.querySelector('.product-dropdown-menu').classList.add('hidden');
        calculateTotals();
    }

    function selectManualProduct(elem, customName) {
        const wrapper = elem.closest('.product-combobox-wrapper');
        wrapper.querySelector('.input-item-search').value = customName;
        wrapper.querySelector('.input-product-id').value = '';
        wrapper.querySelector('.product-dropdown-menu').classList.add('hidden');
        calculateTotals();
    }

    function closeAllComboboxes() {
        document.querySelectorAll('.product-dropdown-menu').forEach(m => m.classList.add('hidden'));
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.product-combobox-wrapper')) {
            closeAllComboboxes();
        }
    });

    // PR Selection handler
    function onSelectApprovedPr(prId) {
        const tbody = document.getElementById('itemsTbody');
        tbody.innerHTML = '';
        rowCounter = 0;

        const storeSelectDisplay = document.getElementById('store_id_display');
        const hiddenStoreId = document.getElementById('hidden_store_id');
        const lockedBadge = document.getElementById('store_locked_badge');

        if (!prId) {
            // PO Bebas: Cabang dapat dipilih bebas
            storeSelectDisplay.disabled = false;
            storeSelectDisplay.classList.remove('opacity-70', 'bg-surface-container', 'cursor-not-allowed');
            if (lockedBadge) lockedBadge.classList.add('hidden');
            hiddenStoreId.value = storeSelectDisplay.value;

            // 1 baris kosong
            addItemRow();
            return;
        }

        const pr = availablePrsData.find(p => String(p.id) === String(prId));
        if (!pr) return;

        // Otomatis pilih cabang penerima barang sesuai PR & kunci (disabled)
        if (pr.store_id) {
            storeSelectDisplay.value = pr.store_id;
            hiddenStoreId.value = pr.store_id;
            storeSelectDisplay.disabled = true;
            storeSelectDisplay.classList.add('opacity-70', 'bg-surface-container', 'cursor-not-allowed');
            if (lockedBadge) lockedBadge.classList.remove('hidden');
        }

        // Otomatis isi catatan jika kosong
        const notesField = document.getElementById('notes');
        if (!notesField.value.trim() || notesField.value.startsWith('Diteruskan dari Order Barang')) {
            notesField.value = `Diteruskan dari Order Barang #${pr.pr_number}` + (pr.notes ? ` - ${pr.notes}` : '');
        }

        // Otomatis isi tabel rincian barang dari PR
        if (pr.items && pr.items.length > 0) {
            pr.items.forEach(item => {
                addItemRow(item);
            });
        } else {
            addItemRow();
        }
    }

    function onStoreChange(storeId) {
        document.getElementById('hidden_store_id').value = storeId;
        closeAllComboboxes();
        const prSelect = document.getElementById('purchase_request_id');
        if (prSelect && !prSelect.value) {
            window.location.href = "{{ route('purchasing.purchase-orders.create') }}?store_id=" + storeId;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function escapeAttr(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function recalculateEstimatedPaymentDate() {
        const poDateInput = document.getElementById('po_date');
        const termDaysInput = document.getElementById('payment_term_days');
        const expectedDateInput = document.getElementById('expected_date');
        const paymentMethod = document.getElementById('payment_method')?.value;

        if (!poDateInput || !expectedDateInput) return;

        if (paymentMethod !== 'tempo') {
            expectedDateInput.value = '';
            return;
        }

        const termDays = parseInt(termDaysInput?.value || 0, 10);
        if (poDateInput.value && termDays > 0) {
            const baseDate = new Date(poDateInput.value);
            if (!isNaN(baseDate.getTime())) {
                baseDate.setDate(baseDate.getDate() + termDays);
                const yyyy = baseDate.getFullYear();
                const mm = String(baseDate.getMonth() + 1).padStart(2, '0');
                const dd = String(baseDate.getDate()).padStart(2, '0');
                expectedDateInput.value = `${yyyy}-${mm}-${dd}`;
            }
        } else if (poDateInput.value) {
            expectedDateInput.value = poDateInput.value;
        }
    }

    function onPaymentMethodChange(method) {
        const termDaysInput = document.getElementById('payment_term_days');
        const expectedDateInput = document.getElementById('expected_date');

        if (method === 'tempo') {
            if (termDaysInput && (!termDaysInput.value || parseInt(termDaysInput.value, 10) <= 0)) {
                termDaysInput.value = 14;
            }
            if (termDaysInput) termDaysInput.disabled = false;
            recalculateEstimatedPaymentDate();
        } else {
            if (termDaysInput) {
                termDaysInput.value = 0;
            }
            if (expectedDateInput) {
                expectedDateInput.value = '';
            }
        }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', () => {
        recalculateEstimatedPaymentDate();
        const prSelect = document.getElementById('purchase_request_id');
        if (prSelect && prSelect.value) {
            onSelectApprovedPr(prSelect.value);
        } else if (initialSourcePr) {
            onSelectApprovedPr(initialSourcePr.id);
        } else {
            addItemRow();
        }
    });
</script>
@endsection
