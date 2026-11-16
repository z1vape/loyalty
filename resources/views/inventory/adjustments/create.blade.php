@extends('layouts.app')

@php
    $effectiveDate = old('adjustment_date', $selectedDate ?? request('date', date('Y-m-d')));
    $carbonDate = \Carbon\Carbon::parse($effectiveDate);
    $dateLabel = $carbonDate->translatedFormat('d F Y');
    $dayName = $carbonDate->translatedFormat('l');
    $categories = $categories ?? collect();
@endphp

@section('title', "Stok Opname ({$dateLabel}) | InvenTrack")

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-16">

    {{-- Flash & Error Alerts --}}
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold flex items-start gap-3 shadow-xs">
            <span class="material-symbols-outlined text-lg shrink-0 mt-0.5 text-rose-600">error</span>
            <div class="space-y-1">
                <p class="font-bold">Terdapat kendala saat menyimpan data opname:</p>
                <ul class="list-disc list-inside space-y-0.5 font-normal">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ── 1. HEADER & INFO TRANSAKSI ── --}}
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">
        {{-- Top Row: Judul & Tombol Aksi Kanan Atas --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#006c49] to-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                    <span class="material-symbols-outlined text-2xl">checklist</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                            Stok Opname Fisik
                        </span>
                        <span class="text-slate-300 font-bold">•</span>
                        <span class="text-xs text-slate-500 font-semibold">Olsera Style</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-0.5">Stok Opname</h1>
                    <p class="text-xs text-slate-500">Sesuaikan stok fisik riil toko dengan pencatatan sistem secara cepat dan akurat.</p>
                </div>
            </div>

            {{-- Tombol Aksi Kanan Atas: Batalkan & Simpan / Posting Sekarang --}}
            <div class="flex items-center gap-2.5 self-start sm:self-auto">
                <a href="{{ route('stock-adjustments.date', ['date' => $effectiveDate]) }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm rounded-xl transition shadow-2xs no-underline cursor-pointer flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">close</span>
                    <span>Batalkan</span>
                </a>
                <button 
                    type="button" 
                    id="btn_submit_top"
                    onclick="submitStockOpname()"
                    class="px-5 py-2.5 bg-[#006c49] hover:bg-[#00553a] text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-md shadow-emerald-900/15 cursor-pointer flex items-center gap-2 active:scale-95 border-0"
                >
                    <span class="material-symbols-outlined text-base">publish</span>
                    <span id="btn_submit_top_text">Simpan / Posting Sekarang</span>
                </button>
            </div>
        </div>

        {{-- Info Transaksi: Tanggal Opname, Catatan, dan Info Cabang --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start text-xs">
            {{-- Tanggal Stok Opname --}}
            <div class="md:col-span-4 space-y-1.5">
                <label for="opname_date_input" class="block text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                    Tanggal Stok Opname <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-lg">calendar_today</span>
                    </div>
                    <input 
                        type="date" 
                        id="opname_date_input"
                        name="adjustment_date_display" 
                        value="{{ $effectiveDate }}"
                        class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49] transition cursor-pointer"
                        onchange="updateEffectiveDate(this.value)"
                    >
                </div>
                <p class="text-[11px] text-slate-500 font-medium">
                    Sesi: <span class="font-bold text-slate-700" id="date_label_display">{{ $dayName }}, {{ $dateLabel }}</span>
                </p>
            </div>

            {{-- Catatan / Keterangan Transaksi --}}
            <div class="md:col-span-5 space-y-1.5">
                <label for="opname_notes_input" class="block text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">
                    Catatan / Keterangan Opname
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="opname_notes_input"
                        placeholder="Contoh: Stok opname berkala, audit fisik harian..." 
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49] transition placeholder:text-slate-400"
                    >
                </div>
                <p class="text-[11px] text-slate-400">Catatan ini akan disematkan pada setiap log penyesuaian.</p>
            </div>

            {{-- Info Petugas & Toko --}}
            <div class="md:col-span-3 bg-slate-50 border border-slate-200/80 rounded-xl p-3 space-y-1">
                <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Petugas Opname</span>
                <div class="font-bold text-slate-800 truncate flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-emerald-600">account_circle</span>
                    <span>{{ auth()->user()->name }}</span>
                </div>
                <div class="text-[11px] text-slate-500 truncate flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px] text-slate-400">store</span>
                    <span>{{ session('active_store_name', 'Cabang Aktif') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. DAFTAR PRODUK TERPILIH (TABEL UTAMA) ── --}}
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
        {{-- Toolbar Atas Tabel: Tombol + Tambah Produk & Status --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                    <span>Daftar Produk Terpilih</span>
                </h2>
                <span id="selected_count_badge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    0 Produk
                </span>
            </div>

            <div class="flex items-center gap-2.5">
                {{-- Search Filter di dalam Tabel Produk Terpilih --}}
                <div class="relative hidden sm:block">
                    <input 
                        type="text" 
                        id="table_filter_input" 
                        placeholder="Cari di tabel..." 
                        oninput="filterOpnameTable(this.value)"
                        class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#006c49] transition w-44"
                    >
                    <span class="material-symbols-outlined text-sm text-slate-400 absolute left-2.5 top-2 pointer-events-none">search</span>
                </div>

                {{-- Tombol Pemicu: + Tambah Produk --}}
                <button 
                    type="button" 
                    onclick="openProductModal()"
                    class="px-4 py-2 bg-[#006c49] hover:bg-[#00553a] text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer active:scale-95 border-0"
                >
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span>+ Tambah Produk</span>
                </button>
            </div>
        </div>

        {{-- Tabel Daftar Produk Terpilih --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs" id="opnameTable">
                    <thead class="bg-slate-50 text-slate-700 font-extrabold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">No.</th>
                            <th class="py-3 px-4 min-w-[240px]">Produk</th>
                            <th class="py-3 px-3 font-mono w-28">SKU</th>
                            <th class="py-3 px-3 text-center w-24">Stok Sistem</th>
                            <th class="py-3 px-3 text-center w-32">Stok Aktual <span class="text-rose-500">*</span></th>
                            <th class="py-3 px-3 text-center w-28">Selisih</th>
                            <th class="py-3 px-4 text-right w-36">Selisih Harga</th>
                            <th class="py-3 px-3 w-14 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="opname_table_body" class="divide-y divide-slate-100 font-medium">
                        {{-- Diisi secara dinamis oleh JavaScript --}}
                    </tbody>
                </table>
            </div>

            {{-- Empty State (Tabel Kosong Saat Baru Dibuat) --}}
            <div id="opname_empty_state" class="py-16 px-4 text-center flex flex-col items-center justify-center bg-slate-50/50">
                <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-3 text-slate-400 shadow-xs">
                    <span class="material-symbols-outlined text-3xl text-emerald-600/70">playlist_add</span>
                </div>
                <h3 class="text-sm font-extrabold text-slate-800">Tabel Produk Masih Kosong</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm">
                    Silakan klik tombol <strong class="text-[#006c49] font-bold">+ Tambah Produk</strong> di atas untuk memilih barang dari katalog yang ingin di-opname.
                </p>
                <button 
                    type="button" 
                    onclick="openProductModal()"
                    class="mt-4 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-[#006c49] font-bold text-xs rounded-xl border border-emerald-200/80 transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
                >
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Buka Katalog Produk</span>
                </button>
            </div>

            {{-- Ringkasan / Summary Footer di Bawah Tabel --}}
            <div id="opname_summary_footer" class="hidden px-5 py-3.5 bg-slate-50/90 border-t border-slate-200 flex flex-wrap items-center justify-between text-xs gap-3">
                <div class="text-slate-600 font-semibold flex items-center gap-2">
                    <span>Total Produk: <strong id="sum_total_products" class="text-slate-900 font-bold">0</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>Stok Sistem: <strong id="sum_total_system" class="text-slate-800 font-mono font-bold">0</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>Stok Fisik: <strong id="sum_total_actual" class="text-slate-800 font-mono font-bold">0</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-slate-600 font-semibold">
                        Selisih Fisik: <strong id="sum_total_diff" class="font-mono font-extrabold text-slate-800">0 unit</strong>
                    </span>
                    <span class="border-l border-slate-200 pl-3 text-slate-600 font-semibold">
                        Total Selisih Nilai: <strong id="sum_total_price_diff" class="font-mono font-black text-sm text-slate-900">Rp 0</strong>
                    </span>
                </div>
            </div>
        </div>

        {{-- Bottom Actions --}}
        <div class="pt-3 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
            <button 
                type="button" 
                id="btn_clear_all" 
                onclick="clearAllSelected()" 
                class="hidden text-rose-600 hover:text-rose-700 text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-transparent border-0"
            >
                <span class="material-symbols-outlined text-base">delete_sweep</span>
                <span>Kosongkan Seluruh Tabel</span>
            </button>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end ml-auto">
                <a href="{{ route('stock-adjustments.date', ['date' => $effectiveDate]) }}" class="w-full sm:w-auto px-5 py-2.5 text-center text-xs sm:text-sm font-bold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition no-underline">
                    Batalkan
                </a>
                <button 
                    type="button" 
                    id="btn_submit_bottom"
                    onclick="submitStockOpname()"
                    class="w-full sm:w-auto px-6 py-2.5 bg-[#006c49] hover:bg-[#00553a] text-white text-xs sm:text-sm font-bold rounded-xl active:scale-95 transition-all shadow-md shadow-emerald-900/15 cursor-pointer flex items-center justify-center gap-2 border-0"
                >
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span id="btn_submit_bottom_text">Simpan / Posting Sekarang</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Form Rahasia Pengiriman Batch ke Backend --}}
    <form method="POST" action="{{ route('stock-adjustments.store') }}" id="realStockOpnameForm" class="hidden">
        @csrf
        <input type="hidden" name="adjustment_date" id="form_adjustment_date" value="{{ $effectiveDate }}">
        <input type="hidden" name="redirect_to_date" value="1">
        <div id="form_items_container"></div>
    </form>

</div>

{{-- ── 3. MODAL POP-UP 'TAMBAH PRODUK' (LARGE MODAL SESUAI SPESIFIKASI OLSERA) ── --}}
<div id="productSelectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-xs hidden">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl max-w-6xl w-full flex flex-col h-[92vh] max-h-[92vh] overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        {{-- 3.1 Header Modal: Judul di kiri, Tombol Batal & Tambah (X) di kanan --}}
        <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-200 flex items-center justify-between bg-white shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006c49] flex items-center justify-center font-bold border border-emerald-100">
                    <span class="material-symbols-outlined text-xl">add_shopping_cart</span>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Tambah Produk</h3>
                    <p class="text-xs text-slate-500">Pilih barang untuk opname dan tentukan stok fisik aktual.</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button 
                    type="button" 
                    onclick="closeProductModal()"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm rounded-xl transition cursor-pointer border-0"
                >
                    Batal
                </button>
                <button 
                    type="button" 
                    id="btn_modal_add"
                    onclick="applyModalSelection()"
                    class="px-5 py-2 bg-[#006c49] hover:bg-[#00553a] text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer border-0 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-base">add</span>
                    <span id="btn_modal_add_text">Tambah (0)</span>
                </button>
            </div>
        </div>

        {{-- 3.2 Baris Filter & Pencarian --}}
        <div class="p-3.5 sm:px-6 sm:py-3.5 bg-slate-50 border-b border-slate-200/90 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
            {{-- Input Waktu/Jam (Default Jam Saat Ini) --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 border border-slate-200 rounded-xl shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-slate-400 text-base">schedule</span>
                <input 
                    type="time" 
                    id="modalTimeInput" 
                    value="{{ date('H:i') }}"
                    class="text-xs font-bold text-slate-800 bg-transparent border-0 focus:outline-none cursor-pointer"
                    title="Waktu pencatatan stok opname"
                >
            </div>

            {{-- Dropdown Filter Kategori --}}
            <div class="shrink-0 sm:w-48">
                <select 
                    id="modalCategoryFilter" 
                    onchange="onModalFilterChange()"
                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49] transition shadow-2xs cursor-pointer"
                >
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Pencarian Instan --}}
            <div class="relative flex-1">
                <span class="material-symbols-outlined text-slate-400 absolute left-3 top-2.5 text-base">search</span>
                <input 
                    type="text" 
                    id="modalSearchInput" 
                    placeholder="Cari Produk / SKU / Barcode..." 
                    oninput="onModalFilterChange()"
                    class="w-full pl-9 pr-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49] transition shadow-2xs placeholder:text-slate-400"
                >
            </div>
        </div>

        {{-- 3.3 Banner Informasi: Maksimal 500 produk terpilih --}}
        <div class="px-4 sm:px-6 pt-3 shrink-0">
            <div class="px-4 py-2.5 bg-blue-50/90 border border-blue-200/90 rounded-xl flex items-center gap-2.5 text-xs text-blue-900 shadow-2xs">
                <span class="material-symbols-outlined text-blue-600 text-lg shrink-0">info</span>
                <div class="flex-1 font-medium leading-relaxed">
                    Maksimal 500 produk terpilih tiap penambahan. Anda dapat langsung menginput <strong class="font-bold">Stok Aktual</strong> di dalam modal ini atau mengisinya setelah dimasukkan ke tabel utama.
                </div>
            </div>
        </div>

        {{-- 3.4 Tabel Daftar Produk di Dalam Modal --}}
        <div class="overflow-y-auto flex-1 px-4 sm:px-6 py-3 min-h-[280px]">
            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-extrabold border-b border-slate-200 sticky top-0 z-10 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">
                                <input 
                                    type="checkbox" 
                                    id="modalHeaderSelectAll" 
                                    onchange="toggleModalSelectAllOnPage(this.checked)"
                                    class="w-4 h-4 rounded border-slate-300 text-[#006c49] focus:ring-[#006c49]/30 accent-[#006c49] cursor-pointer"
                                    title="Pilih semua di halaman ini"
                                >
                            </th>
                            <th class="py-3 px-4 min-w-[250px]">Product</th>
                            <th class="py-3 px-3 font-mono w-28">SKU</th>
                            <th class="py-3 px-3 w-32">Kategori</th>
                            <th class="py-3 px-3 text-center w-28">Stok Sistem</th>
                            <th class="py-3 px-4 text-center w-36">Stok Aktual</th>
                        </tr>
                    </thead>
                    <tbody id="modalProductTableBody" class="divide-y divide-slate-100 font-medium">
                        {{-- Diisi secara dinamis melalui JavaScript renderModalPage() --}}
                    </tbody>
                </table>

                {{-- Empty Search State di dalam Modal --}}
                <div id="modal_empty_state" class="py-12 px-4 text-center hidden">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">search_off</span>
                    <p class="text-xs font-bold text-slate-700">Tidak ada produk yang cocok dengan pencarian / filter</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Coba gunakan kata kunci SKU atau nama produk lainnya.</p>
                </div>
            </div>
        </div>

        {{-- 3.5 Footer Modal (Pagination) --}}
        <div class="p-3.5 sm:px-6 border-t border-slate-200 bg-slate-50/90 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs shrink-0">
            {{-- Pilihan Jumlah Baris per Halaman --}}
            <div class="flex items-center gap-2.5 text-slate-600 font-medium">
                <span>Tampilkan:</span>
                <select 
                    id="modalPageSizeSelect" 
                    onchange="changeModalPageSize(this.value)"
                    class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-[#006c49] shadow-2xs cursor-pointer"
                >
                    <option value="50" selected>50 / page</option>
                    <option value="100">100 / page</option>
                    <option value="200">200 / page</option>
                    <option value="500">500 / page</option>
                </select>
                <span class="text-slate-300">•</span>
                <span id="modalPaginationInfo" class="font-semibold text-slate-700">
                    Showing 0 – 0 of 0 products
                </span>
            </div>

            {{-- Tombol Navigasi Halaman --}}
            <div class="flex items-center gap-1.5" id="modalPaginationButtons">
                {{-- Di-generate secara dinamis oleh JavaScript --}}
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── MASTER CATALOG DATA (Dikonversi dari data server Blade) ──
    const masterCatalog = [
        @foreach($products as $prod)
        {
            id: {{ $prod->id }},
            name: {!! json_encode($prod->name) !!},
            variant: {!! json_encode($prod->variant ?? '') !!},
            sku: {!! json_encode($prod->sku ?? '-') !!},
            category: {!! json_encode($prod->category ?? '-') !!},
            photo_url: {!! json_encode($prod->photo_url) !!},
            stock: {{ (int) $prod->stock }},
            price: {{ (float) ($prod->selling_price ?? 0) }},
            actual_stock: null, // Input fisik di modal
            checked: false
        },
        @endforeach
    ];

    // State Penyimpanan Produk Terpilih di Halaman Utama (Tabel Utama)
    let selectedOpnameItems = [];

    // State Pagination & Filter Modal
    let modalFilteredProducts = [];
    let modalCurrentPage = 1;
    let modalPageSize = 50;

    document.addEventListener('DOMContentLoaded', () => {
        // Inisialisasi daftar produk terfilter di modal
        modalFilteredProducts = [...masterCatalog];

        // Esc key untuk menutup modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });
    });

    // ── Update Tanggal Efektif ──
    function updateEffectiveDate(newDate) {
        if (!newDate) return;
        document.getElementById('form_adjustment_date').value = newDate;
        
        const url = new URL(window.location);
        url.searchParams.set('date', newDate);
        window.history.replaceState({}, '', url);

        try {
            const parts = newDate.split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('date_label_display').textContent = d.toLocaleDateString('id-ID', options);
            }
        } catch(e) {}
    }

    // ── MODAL CONTROL FUNCTIONS ──
    function openProductModal() {
        const modal = document.getElementById('productSelectionModal');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Reset status checked berdasarkan apa yang sudah ada di tabel utama
        const existingMap = new Map();
        selectedOpnameItems.forEach(i => existingMap.set(i.product_id, i.actual_stock));

        masterCatalog.forEach(prod => {
            if (existingMap.has(prod.id)) {
                prod.checked = true;
                prod.actual_stock = existingMap.get(prod.id);
            } else {
                prod.checked = false;
                prod.actual_stock = null;
            }
        });

        // Reset filter
        document.getElementById('modalSearchInput').value = '';
        document.getElementById('modalCategoryFilter').value = '';
        modalCurrentPage = 1;

        onModalFilterChange();

        setTimeout(() => {
            const searchInput = document.getElementById('modalSearchInput');
            if (searchInput) searchInput.focus();
        }, 80);
    }

    function closeProductModal() {
        const modal = document.getElementById('productSelectionModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // ── MODAL FILTER & PAGINATION LOGIC ──
    function onModalFilterChange() {
        const query = (document.getElementById('modalSearchInput').value || '').toLowerCase().trim();
        const category = document.getElementById('modalCategoryFilter').value;

        modalFilteredProducts = masterCatalog.filter(prod => {
            const matchName = (prod.name || '').toLowerCase().includes(query);
            const matchSku = (prod.sku || '').toLowerCase().includes(query);
            const matchVariant = (prod.variant || '').toLowerCase().includes(query);
            const matchText = !query || matchName || matchSku || matchVariant;

            const matchCategory = !category || prod.category === category;

            return matchText && matchCategory;
        });

        modalCurrentPage = 1;
        renderModalPage();
        updateModalHeaderAddButton();
    }

    function changeModalPageSize(newSize) {
        modalPageSize = parseInt(newSize, 10) || 50;
        modalCurrentPage = 1;
        renderModalPage();
    }

    function goToModalPage(pageNum) {
        const totalPages = Math.ceil(modalFilteredProducts.length / modalPageSize) || 1;
        if (pageNum < 1 || pageNum > totalPages) return;
        modalCurrentPage = pageNum;
        renderModalPage();
    }

    function renderModalPage() {
        const tbody = document.getElementById('modalProductTableBody');
        const emptyState = document.getElementById('modal_empty_state');
        const paginationInfo = document.getElementById('modalPaginationInfo');
        const paginationBtns = document.getElementById('modalPaginationButtons');
        const headerSelectAll = document.getElementById('modalHeaderSelectAll');

        tbody.innerHTML = '';

        const totalItems = modalFilteredProducts.length;

        if (totalItems === 0) {
            emptyState.classList.remove('hidden');
            paginationInfo.textContent = 'Showing 0 – 0 of 0 products';
            paginationBtns.innerHTML = '';
            headerSelectAll.checked = false;
            return;
        }

        emptyState.classList.add('hidden');

        const totalPages = Math.ceil(totalItems / modalPageSize) || 1;
        if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;

        const startIndex = (modalCurrentPage - 1) * modalPageSize;
        const endIndex = Math.min(startIndex + modalPageSize, totalItems);
        const pageItems = modalFilteredProducts.slice(startIndex, endIndex);

        // Update info teks
        paginationInfo.textContent = `Showing ${startIndex + 1} – ${endIndex} of ${totalItems} products`;

        // Render baris produk
        let allOnPageChecked = true;

        pageItems.forEach(prod => {
            if (!prod.checked) allOnPageChecked = false;

            const tr = document.createElement('tr');
            tr.className = `modal-row hover:bg-emerald-50/40 transition-colors select-none cursor-pointer ${prod.checked ? 'bg-emerald-50/25' : ''}`;
            tr.onclick = (e) => {
                if (e.target.tagName.toLowerCase() === 'input') return;
                const newChecked = !prod.checked;
                toggleModalProductChecked(prod.id, newChecked);
                const cb = tr.querySelector('.modal-item-checkbox');
                if (cb) cb.checked = newChecked;
                if (newChecked) tr.classList.add('bg-emerald-50/25');
                else tr.classList.remove('bg-emerald-50/25');
            };

            // Gambar / Thumbnail
            const imageHtml = prod.photo_url 
                ? `<img src="${escapeHtml(prod.photo_url)}" alt="${escapeHtml(prod.name)}" class="w-9 h-9 rounded-lg object-cover border border-slate-200 shrink-0">` 
                : `<div class="w-9 h-9 rounded-lg bg-emerald-50 text-[#006c49] flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-100"><span class="material-symbols-outlined text-lg">inventory_2</span></div>`;

            const variantBadge = prod.variant 
                ? `<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 ml-1">${escapeHtml(prod.variant)}</span>` 
                : '';

            const categoryBadge = prod.category && prod.category !== '-' 
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">${escapeHtml(prod.category)}</span>` 
                : `<span class="text-slate-400 font-mono">-</span>`;

            tr.innerHTML = `
                <td class="py-3 px-3 text-center" onclick="event.stopPropagation()">
                    <input 
                        type="checkbox" 
                        class="modal-item-checkbox w-4 h-4 rounded border-slate-300 text-[#006c49] focus:ring-[#006c49]/30 accent-[#006c49] cursor-pointer"
                        data-id="${prod.id}"
                        ${prod.checked ? 'checked' : ''}
                        onchange="toggleModalProductChecked(${prod.id}, this.checked); if(this.checked){ tr.classList.add('bg-emerald-50/25'); } else { tr.classList.remove('bg-emerald-50/25'); }"
                    >
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                        ${imageHtml}
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                                ${escapeHtml(prod.name)}${variantBadge}
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                Harga Jual: Rp ${prod.price.toLocaleString('id-ID')}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-slate-600 whitespace-nowrap">
                    ${escapeHtml(prod.sku)}
                </td>
                <td class="py-3 px-3">
                    ${categoryBadge}
                </td>
                <td class="py-3 px-3 text-center font-mono font-bold text-slate-700 bg-slate-50/50">
                    ${prod.stock} unit
                </td>
                <td class="py-2.5 px-4 text-center">
                    <input 
                        type="number" 
                        placeholder="${prod.stock}" 
                        value="${prod.actual_stock !== null ? prod.actual_stock : ''}"
                        oninput="onModalActualStockInput(${prod.id}, this.value)"
                        class="w-24 px-2.5 py-1.5 text-center font-mono font-black text-xs sm:text-sm text-slate-900 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition"
                        min="0"
                    >
                </td>
            `;

            tbody.appendChild(tr);
        });

        headerSelectAll.checked = (pageItems.length > 0) && allOnPageChecked;

        // Render Pagination Buttons
        renderModalPaginationButtons(totalPages);
    }

    function renderModalPaginationButtons(totalPages) {
        const container = document.getElementById('modalPaginationButtons');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        // Prev Button
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.disabled = (modalCurrentPage === 1);
        prevBtn.className = `px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-xs transition cursor-pointer ${modalCurrentPage === 1 ? 'opacity-40 cursor-not-allowed bg-slate-50 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700'}`;
        prevBtn.innerHTML = '← Prev';
        prevBtn.onclick = () => goToModalPage(modalCurrentPage - 1);
        container.appendChild(prevBtn);

        // Page Number Buttons (smart display)
        let pagesToShow = [];
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= modalCurrentPage - 2 && p <= modalCurrentPage + 2)) {
                pagesToShow.push(p);
            }
        }

        let lastP = 0;
        pagesToShow.forEach(p => {
            if (lastP && p - lastP > 1) {
                const dots = document.createElement('span');
                dots.className = 'px-1 text-slate-400 font-bold';
                dots.textContent = '...';
                container.appendChild(dots);
            }

            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = `w-8 h-8 rounded-lg font-bold text-xs transition cursor-pointer border ${p === modalCurrentPage ? 'bg-[#006c49] text-white border-[#006c49] shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200'}`;
            pageBtn.textContent = p;
            pageBtn.onclick = () => goToModalPage(p);
            container.appendChild(pageBtn);

            lastP = p;
        });

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.disabled = (modalCurrentPage === totalPages);
        nextBtn.className = `px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-xs transition cursor-pointer ${modalCurrentPage === totalPages ? 'opacity-40 cursor-not-allowed bg-slate-50 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700'}`;
        nextBtn.innerHTML = 'Next →';
        nextBtn.onclick = () => goToModalPage(modalCurrentPage + 1);
        container.appendChild(nextBtn);
    }

    // ── MODAL CHECKBOX & ACTUAL STOCK HANDLERS ──
    function toggleModalProductChecked(productId, isChecked) {
        const prod = masterCatalog.find(p => p.id === productId);
        if (!prod) return;

        prod.checked = isChecked;
        updateModalHeaderAddButton();

        // Update status checkbox select-all di header halaman modal
        const headerSelectAll = document.getElementById('modalHeaderSelectAll');
        if (headerSelectAll) {
            const startIndex = (modalCurrentPage - 1) * modalPageSize;
            const endIndex = Math.min(startIndex + modalPageSize, modalFilteredProducts.length);
            const pageItems = modalFilteredProducts.slice(startIndex, endIndex);
            headerSelectAll.checked = (pageItems.length > 0) && pageItems.every(p => p.checked);
        }
    }

    function toggleModalSelectAllOnPage(isChecked) {
        const startIndex = (modalCurrentPage - 1) * modalPageSize;
        const endIndex = Math.min(startIndex + modalPageSize, modalFilteredProducts.length);
        const pageItems = modalFilteredProducts.slice(startIndex, endIndex);

        pageItems.forEach(prod => {
            prod.checked = isChecked;
        });

        // Sync tampilan checkbox dan background baris di page saat ini
        document.querySelectorAll('.modal-item-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
        document.querySelectorAll('.modal-row').forEach(row => {
            if (isChecked) row.classList.add('bg-emerald-50/25');
            else row.classList.remove('bg-emerald-50/25');
        });

        updateModalHeaderAddButton();
    }

    function onModalActualStockInput(productId, value) {
        const prod = masterCatalog.find(p => p.id === productId);
        if (!prod) return;

        if (value === '' || isNaN(parseInt(value, 10))) {
            prod.actual_stock = null;
        } else {
            prod.actual_stock = parseInt(value, 10);
            // Otomatis centang produk jika diisi stok fisiknya
            if (!prod.checked) {
                prod.checked = true;
                const cb = document.querySelector(`.modal-item-checkbox[data-id="${productId}"]`);
                if (cb) cb.checked = true;
            }
        }

        updateModalHeaderAddButton();
    }

    function updateModalHeaderAddButton() {
        const checkedCount = masterCatalog.filter(p => p.checked).length;
        const addBtn = document.getElementById('btn_modal_add');
        const addBtnText = document.getElementById('btn_modal_add_text');

        if (addBtnText) {
            addBtnText.textContent = `Tambah (${checkedCount})`;
        }

        if (addBtn) {
            addBtn.disabled = (checkedCount === 0);
        }
    }

    // ── APPLY SELECTION DARI MODAL KE TABEL UTAMA ──
    function applyModalSelection() {
        const checkedProducts = masterCatalog.filter(p => p.checked);

        if (checkedProducts.length === 0) {
            alert('Silakan centang setidaknya satu produk terlebih dahulu.');
            return;
        }

        if (checkedProducts.length > 500) {
            alert('Maksimal 500 produk terpilih tiap penambahan. Saat ini terpilih: ' + checkedProducts.length);
            return;
        }

        checkedProducts.forEach(prod => {
            const existingIdx = selectedOpnameItems.findIndex(i => i.product_id === prod.id);

            // Nilai fisik: jika sudah diisi di modal, gunakan nilai tersebut. Jika belum, default sama dengan stok sistem
            const actVal = prod.actual_stock !== null ? prod.actual_stock : prod.stock;
            const diffVal = actVal - prod.stock;
            const priceDiffVal = diffVal * prod.price;

            if (existingIdx >= 0) {
                // Update nilai item yang sudah ada
                selectedOpnameItems[existingIdx].actual_stock = actVal;
                selectedOpnameItems[existingIdx].diff = diffVal;
                selectedOpnameItems[existingIdx].price_diff = priceDiffVal;
            } else {
                // Tambahkan sebagai baris baru
                selectedOpnameItems.push({
                    product_id: prod.id,
                    name: prod.name,
                    variant: prod.variant,
                    sku: prod.sku,
                    category: prod.category,
                    photo_url: prod.photo_url,
                    system_stock: prod.stock,
                    selling_price: prod.price,
                    actual_stock: actVal,
                    diff: diffVal,
                    price_diff: priceDiffVal,
                    reason: 'physical_count',
                    notes: ''
                });
            }
        });

        closeProductModal();
        renderOpnameTable();

        // Fokus ke input fisik pertama di tabel utama
        setTimeout(() => {
            const firstInput = document.querySelector('.actual-stock-input');
            if (firstInput) firstInput.select();
        }, 120);
    }

    // ── TABEL UTAMA FUNCTIONS ──
    function removeOpnameItem(index) {
        const item = selectedOpnameItems[index];
        if (item) {
            // Uncheck di master catalog
            const prod = masterCatalog.find(p => p.id === item.product_id);
            if (prod) prod.checked = false;
        }

        selectedOpnameItems.splice(index, 1);
        renderOpnameTable();
    }

    function clearAllSelected() {
        if (confirm('Apakah Anda yakin ingin mengosongkan seluruh produk dari tabel opname ini?')) {
            selectedOpnameItems = [];
            masterCatalog.forEach(p => p.checked = false);
            renderOpnameTable();
        }
    }

    function onActualStockChange(index, value) {
        const item = selectedOpnameItems[index];
        if (!item) return;

        if (value === '' || isNaN(parseInt(value, 10))) {
            item.actual_stock = null;
            item.diff = 0;
            item.price_diff = 0;
        } else {
            const actVal = parseInt(value, 10);
            item.actual_stock = actVal;
            item.diff = actVal - item.system_stock;
            item.price_diff = item.diff * item.selling_price;

            // Sync balik ke master catalog
            const prod = masterCatalog.find(p => p.id === item.product_id);
            if (prod) prod.actual_stock = actVal;
        }

        updateRowCalculations(index);
        updateSummaryCalculations();
    }

    function updateRowCalculations(index) {
        const item = selectedOpnameItems[index];
        const row = document.getElementById(`opname_row_${index}`);
        if (!row || !item) return;

        const diffEl = row.querySelector('.diff-badge');
        const priceDiffEl = row.querySelector('.price-diff-label');

        const diff = item.diff;
        if (item.actual_stock === null) {
            diffEl.innerHTML = `<span class="text-slate-400 font-bold">-</span>`;
            priceDiffEl.innerHTML = `<span class="text-slate-400">Rp 0</span>`;
            return;
        }

        if (diff > 0) {
            diffEl.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                    +${diff}
                </span>
            `;
            priceDiffEl.innerHTML = `<span class="font-extrabold text-emerald-600">+Rp ${Math.abs(item.price_diff).toLocaleString('id-ID')}</span>`;
        } else if (diff < 0) {
            diffEl.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                    ${diff}
                </span>
            `;
            priceDiffEl.innerHTML = `<span class="font-extrabold text-rose-600">-Rp ${Math.abs(item.price_diff).toLocaleString('id-ID')}</span>`;
        } else {
            diffEl.innerHTML = `
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                    0 (Sesuai)
                </span>
            `;
            priceDiffEl.innerHTML = `<span class="font-semibold text-slate-400">Rp 0</span>`;
        }
    }

    function renderOpnameTable() {
        const tbody = document.getElementById('opname_table_body');
        const emptyState = document.getElementById('opname_empty_state');
        const summaryFooter = document.getElementById('opname_summary_footer');
        const badgeCount = document.getElementById('selected_count_badge');
        const clearBtn = document.getElementById('btn_clear_all');

        tbody.innerHTML = '';

        if (selectedOpnameItems.length === 0) {
            emptyState.classList.remove('hidden');
            summaryFooter.classList.add('hidden');
            badgeCount.textContent = '0 Produk';
            clearBtn.classList.add('hidden');
            updateSummaryCalculations();
            return;
        }

        emptyState.classList.add('hidden');
        summaryFooter.classList.remove('hidden');
        clearBtn.classList.remove('hidden');
        badgeCount.textContent = `${selectedOpnameItems.length} Produk`;

        selectedOpnameItems.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.id = `opname_row_${index}`;
            tr.className = 'hover:bg-slate-50/70 transition-colors';

            const imageHtml = item.photo_url 
                ? `<img src="${escapeHtml(item.photo_url)}" alt="${escapeHtml(item.name)}" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0">` 
                : `<div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#006c49] flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-100"><span class="material-symbols-outlined text-base">inventory_2</span></div>`;

            const variantBadge = item.variant 
                ? `<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 ml-1">${escapeHtml(item.variant)}</span>` 
                : '';

            tr.innerHTML = `
                <td class="py-3 px-3 text-center text-slate-400 font-mono font-medium">${index + 1}</td>
                <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                        ${imageHtml}
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                                ${escapeHtml(item.name)}${variantBadge}
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                Harga Jual: Rp ${item.selling_price.toLocaleString('id-ID')}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-slate-600 whitespace-nowrap">
                    ${escapeHtml(item.sku)}
                </td>
                <td class="py-3 px-3 text-center font-mono font-bold text-slate-700 bg-slate-50/60 text-sm">
                    ${item.system_stock}
                </td>
                <td class="py-2.5 px-3 text-center">
                    <input 
                        type="number" 
                        value="${item.actual_stock !== null ? item.actual_stock : ''}" 
                        oninput="onActualStockChange(${index}, this.value)"
                        class="actual-stock-input w-24 px-2.5 py-1.5 text-center font-mono font-black text-sm text-slate-900 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition"
                        placeholder="Fisik..."
                        min="0"
                    >
                </td>
                <td class="py-3 px-3 text-center diff-badge whitespace-nowrap">
                    {{-- Diupdate via updateRowCalculations --}}
                </td>
                <td class="py-3 px-4 text-right price-diff-label font-mono whitespace-nowrap text-xs">
                    {{-- Diupdate via updateRowCalculations --}}
                </td>
                <td class="py-3 px-3 text-center">
                    <button 
                        type="button" 
                        onclick="removeOpnameItem(${index})" 
                        class="p-1 hover:bg-rose-50 rounded-lg text-rose-500 hover:text-rose-700 transition cursor-pointer border-0 bg-transparent" 
                        title="Hapus baris produk ini"
                    >
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            updateRowCalculations(index);
        });

        updateSummaryCalculations();
    }

    function updateSummaryCalculations() {
        let totalSystem = 0;
        let totalActual = 0;
        let totalDiff = 0;
        let totalPriceDiff = 0;

        selectedOpnameItems.forEach(item => {
            totalSystem += item.system_stock;
            if (item.actual_stock !== null) {
                totalActual += item.actual_stock;
                totalDiff += item.diff;
                totalPriceDiff += item.price_diff;
            }
        });

        const sumProducts = document.getElementById('sum_total_products');
        const sumSystem = document.getElementById('sum_total_system');
        const sumActual = document.getElementById('sum_total_actual');
        const sumDiff = document.getElementById('sum_total_diff');
        const sumPriceDiff = document.getElementById('sum_total_price_diff');

        if (sumProducts) sumProducts.textContent = selectedOpnameItems.length;
        if (sumSystem) sumSystem.textContent = totalSystem.toLocaleString('id-ID');
        if (sumActual) sumActual.textContent = totalActual.toLocaleString('id-ID');

        if (sumDiff) {
            const sign = totalDiff > 0 ? '+' : '';
            sumDiff.textContent = `${sign}${totalDiff.toLocaleString('id-ID')} unit`;
            sumDiff.className = totalDiff > 0 
                ? 'font-mono font-extrabold text-emerald-600' 
                : (totalDiff < 0 ? 'font-mono font-extrabold text-rose-600' : 'font-mono font-extrabold text-slate-800');
        }

        if (sumPriceDiff) {
            const sign = totalPriceDiff > 0 ? '+' : (totalPriceDiff < 0 ? '-' : '');
            sumPriceDiff.textContent = `${sign}Rp ${Math.abs(totalPriceDiff).toLocaleString('id-ID')}`;
            sumPriceDiff.className = totalPriceDiff > 0 
                ? 'font-mono font-black text-sm text-emerald-600' 
                : (totalPriceDiff < 0 ? 'font-mono font-black text-sm text-rose-600' : 'font-mono font-black text-sm text-slate-900');
        }
    }

    function filterOpnameTable(keyword) {
        const q = (keyword || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#opname_table_body tr');
        rows.forEach(r => {
            const text = r.innerText.toLowerCase();
            r.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    // ── SUBMIT & POSTING PROSES KE BACKEND ──
    function submitStockOpname() {
        if (selectedOpnameItems.length === 0) {
            alert('Daftar produk masih kosong. Silakan klik "+ Tambah Produk" untuk memilih barang yang akan di-opname.');
            openProductModal();
            return;
        }

        // Cek apakah ada input fisik yang kosong
        const uncalculated = selectedOpnameItems.find(i => i.actual_stock === null || isNaN(i.actual_stock));
        if (uncalculated) {
            alert(`Stok aktual untuk "${uncalculated.name}" belum diisi. Harap lengkapi semua stok aktual.`);
            return;
        }

        // Filter produk yang memiliki selisih fisik
        const itemsWithDiff = selectedOpnameItems.filter(i => i.diff !== 0);

        if (itemsWithDiff.length === 0) {
            alert('Semua stok aktual yang diisi sama persis dengan stok sistem (Selisih 0).\n\nTidak ada perubahan stok fisik yang perlu disesuaikan ke database.');
            return;
        }

        const dateVal = document.getElementById('opname_date_input').value;
        const timeVal = document.getElementById('modalTimeInput') ? document.getElementById('modalTimeInput').value : '';
        const noteVal = document.getElementById('opname_notes_input').value.trim();

        const fullNotes = timeVal ? `[${timeVal}] ${noteVal}`.trim() : noteVal;

        const confirmMsg = `Konfirmasi Posting Stok Opname:\n\n` +
            `• Tanggal: ${dateVal} ${timeVal ? '(' + timeVal + ')' : ''}\n` +
            `• Total Produk Diperiksa: ${selectedOpnameItems.length}\n` +
            `• Produk yang Mengalami Selisih: ${itemsWithDiff.length}\n\n` +
            `Lanjutkan posting penyesuaian stok ini sekarang?`;

        if (!confirm(confirmMsg)) {
            return;
        }

        // Siapkan form input tersembunyi
        const form = document.getElementById('realStockOpnameForm');
        const container = document.getElementById('form_items_container');
        container.innerHTML = '';

        document.getElementById('form_adjustment_date').value = dateVal;

        itemsWithDiff.forEach((item, idx) => {
            container.innerHTML += `
                <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                <input type="hidden" name="items[${idx}][quantity_change]" value="${item.diff}">
                <input type="hidden" name="items[${idx}][reason]" value="physical_count">
                <input type="hidden" name="items[${idx}][notes]" value="${escapeHtml(fullNotes)}">
            `;
        });

        // Disable button to prevent double submit
        document.getElementById('btn_submit_top').disabled = true;
        document.getElementById('btn_submit_bottom').disabled = true;
        document.getElementById('btn_submit_top_text').textContent = 'Memposting Opname...';
        document.getElementById('btn_submit_bottom_text').textContent = 'Memposting Opname...';

        form.submit();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
@endpush
