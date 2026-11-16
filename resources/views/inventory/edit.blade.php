@extends('layouts.app')

@section('title', 'Edit Produk: ' . $product->name . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Edit Data Produk</h1>
            <p class="text-on-surface-variant text-body-lg">
                Perbarui rincian katalog, penyesuaian harga jual/beli, atau foto produk <strong class="text-on-surface">{{ $product->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('inventory.show', $product) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>Lihat Detail</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Validation Error Alert Banner -->
    @if ($errors->any())
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-start gap-3">
            <span class="material-symbols-outlined text-error text-xl shrink-0 mt-0.5">error</span>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-error">Terdapat beberapa kesalahan input:</h4>
                <ul class="text-xs text-on-surface-variant mt-1 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Form -->
    <form action="{{ route('inventory.update', $product) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        <!-- SECTION 1: Informasi Identitas Produk & Foto -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">inventory_2</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Identitas & Detail Produk</h2>
                    <p class="text-xs text-on-surface-variant">Kode SKU, nama barang, klasifikasi kategori, dan foto visual</p>
                </div>
            </div>

            <!-- Photo Upload with Live Preview -->
            <div class="mb-6 pb-6 border-b border-outline-variant/40">
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Foto / Gambar Produk</label>
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <!-- Photo Preview Box -->
                    <div class="relative group shrink-0">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-outline-variant bg-surface-container-low flex items-center justify-center shadow-inner">
                            @if($product->photo)
                                <img id="photo-preview" src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-1 bg-white block" />
                                <span id="photo-placeholder" class="material-symbols-outlined text-4xl text-outline-variant hidden" style="display: none;">add_photo_alternate</span>
                            @else
                                <img id="photo-preview" src="" alt="Preview Foto" class="w-full h-full object-contain p-1 bg-white hidden" style="display: none;" />
                                <span id="photo-placeholder" class="material-symbols-outlined text-4xl text-outline-variant block">add_photo_alternate</span>
                            @endif
                        </div>
                        <label for="photo" class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full bg-[#006c49] text-white hover:bg-[#00553a] flex items-center justify-center shadow-md cursor-pointer transition-transform hover:scale-110" title="Ganti foto produk">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                        </label>
                    </div>

                    <!-- Upload Controls & Info -->
                    <div class="flex-1 flex flex-col justify-center text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                            <label for="photo" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-low hover:bg-surface-container text-xs font-bold text-on-surface cursor-pointer transition inline-flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-outlined text-base text-[#006c49]">file_upload</span>
                                <span>Ganti Berkas Foto</span>
                            </label>
                            <input type="file" name="photo" id="photo" accept="image/*" class="hidden" onchange="previewProductPhoto(this)" />
                            <button type="button" id="btn-clear-photo" onclick="clearSelectedProductPhoto()" class="hidden px-2.5 py-1.5 rounded-lg border border-error/30 text-error hover:bg-error-container/20 text-xs font-semibold transition items-center gap-1">
                                <span class="material-symbols-outlined text-sm">close</span>
                                <span>Batal</span>
                            </button>
                            <span id="file-name-display" class="text-xs text-on-surface-variant truncate max-w-xs block sm:inline">
                                {{ $product->photo ? 'Foto saat ini tersimpan' : 'Format: JPG, PNG, WEBP (Maks: 2MB)' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Biarkan kosong jika tidak ingin mengubah foto produk yang sudah ada.</p>
                    </div>
                </div>
                @error('photo') <p class="text-error text-xs mt-2 font-medium">{{ $message }}</p> @enderror
            </div>

            <!-- SKU & Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- SKU -->
                <div>
                    <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Kode SKU / Barcode <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">qr_code_2</span>
                        <input 
                            type="text" 
                            name="sku" 
                            id="sku" 
                            value="{{ old('sku', $product->sku) }}" 
                            required 
                            placeholder="Contoh: IND-901-HB" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] font-mono text-sm text-on-surface font-bold uppercase transition @error('sku') border-error @enderror"
                        />
                    </div>
                    @error('sku') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Product Name -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nama Produk <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">label</span>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $product->name) }}" 
                            required 
                            placeholder="Contoh: Precision Hex Bolt M8" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('name') border-error @enderror"
                        />
                    </div>
                    @error('name') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Category & Variant -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Kategori Produk
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">category</span>
                        <select 
                            name="category" 
                            id="category" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition cursor-pointer @error('category') border-error @enderror"
                        >
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('category', $product->category) == $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('category') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Variant -->
                <div>
                    <label for="variant" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Varian / Spesifikasi
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">style</span>
                        <select 
                            name="variant" 
                            id="variant" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition cursor-pointer @error('variant') border-error @enderror"
                        >
                            <option value="">-- Pilih Varian --</option>
                            @foreach($variants as $variant)
                                <option value="{{ $variant }}" {{ old('variant', $product->variant) == $variant ? 'selected' : '' }}>{{ $variant }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('variant') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- SECTION 2: Penetapan Harga & Manajemen Stok -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">payments</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Harga Jual/Beli & Manajemen Stok</h2>
                    <p class="text-xs text-on-surface-variant">Atur margin keuntungan, stok fisik saat ini, dan batas minimum pengingat</p>
                </div>
            </div>

            <!-- Price Inputs & Live Margin Preview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                <!-- Harga Beli -->
                <div>
                    <label for="purchase_price" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Harga Beli / Modal (Rp) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">shopping_bag</span>
                        <input 
                            type="number" 
                            step="1" 
                            min="0" 
                            name="purchase_price" 
                            id="purchase_price" 
                            value="{{ old('purchase_price', $product->purchase_price) }}" 
                            required 
                            placeholder="0" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] font-mono text-sm text-on-surface font-semibold transition @error('purchase_price') border-error @enderror"
                            oninput="calculateMargin()"
                        />
                    </div>
                    @error('purchase_price') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Harga Jual -->
                <div>
                    <label for="selling_price" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Harga Jual Konsumen (Rp) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">sell</span>
                        <input 
                            type="number" 
                            step="1" 
                            min="0" 
                            name="selling_price" 
                            id="selling_price" 
                            value="{{ old('selling_price', $product->selling_price) }}" 
                            required 
                            placeholder="0" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] font-mono text-sm text-on-surface font-bold text-[#006c49] transition @error('selling_price') border-error @enderror"
                            oninput="calculateMargin()"
                        />
                    </div>
                    @error('selling_price') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Estimated Profit Margin Card -->
                <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/60 flex flex-col justify-center">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Estimasi Margin Laba</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="margin-amount" class="font-mono text-base font-extrabold text-[#006c49]">Rp 0</span>
                        <span id="margin-percentage" class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#e8f5e9] text-[#006c49] border border-[#006c49]/30">0%</span>
                    </div>
                </div>
            </div>

            <!-- Stock Levels -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Current Stock -->
                <div>
                    <label for="stock" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Stok Fisik Tersedia <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">warehouse</span>
                        <input 
                            type="number" 
                            min="0" 
                            name="stock" 
                            id="stock" 
                            value="{{ old('stock', $product->stock) }}" 
                            required 
                            placeholder="0" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] font-mono text-sm text-on-surface font-bold transition @error('stock') border-error @enderror"
                        />
                    </div>
                    @error('stock') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Minimum Threshold -->
                <div>
                    <label for="min_stock" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Ambang Batas Minimum (Alert) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">warning_amber</span>
                        <input 
                            type="number" 
                            min="0" 
                            name="min_stock" 
                            id="min_stock" 
                            value="{{ old('min_stock', $product->min_stock) }}" 
                            required 
                            placeholder="10" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] font-mono text-sm text-on-surface font-medium transition @error('min_stock') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Sistem akan memberi tanda peringatan kuning saat stok sama atau di bawah angka ini.</p>
                    @error('min_stock') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                    Deskripsi / Keterangan Produk
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3" 
                    placeholder="Tuliskan keterangan spesifikasi teknis, garansi, atau catatan tambahan produk..."
                    class="w-full p-4 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface transition resize-none @error('description') border-error @enderror"
                >{{ old('description', $product->description) }}</textarea>
                @error('description') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Form Action Buttons -->
        <div class="flex items-center justify-end gap-3 pb-6">
            <a href="{{ route('inventory.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-on-surface text-sm font-semibold transition-all shadow-sm">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl active:scale-95 duration-150 transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Perbarui Produk</span>
            </button>
        </div>
    </form>
</div>

<script>
function previewProductPhoto(input) {
    const preview = document.getElementById('photo-preview');
    const placeholder = document.getElementById('photo-placeholder');
    const clearBtn = document.getElementById('btn-clear-photo');
    const fileNameDisplay = document.getElementById('file-name-display');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            preview.style.display = 'block';
            placeholder.classList.add('hidden');
            placeholder.style.display = 'none';
        }

        reader.readAsDataURL(file);
        clearBtn.classList.remove('hidden');
        clearBtn.style.display = 'inline-flex';
        fileNameDisplay.textContent = file.name;
    }
}

function clearSelectedProductPhoto() {
    const input = document.getElementById('photo');
    const preview = document.getElementById('photo-preview');
    const placeholder = document.getElementById('photo-placeholder');
    const clearBtn = document.getElementById('btn-clear-photo');
    const fileNameDisplay = document.getElementById('file-name-display');

    input.value = '';
    @if($product->photo)
        preview.src = "{{ asset('storage/' . $product->photo) }}";
        fileNameDisplay.textContent = 'Foto saat ini tersimpan';
    @else
        preview.src = '';
        preview.classList.add('hidden');
        preview.style.display = 'none';
        placeholder.classList.remove('hidden');
        placeholder.style.display = 'block';
        fileNameDisplay.textContent = 'Format: JPG, PNG, WEBP (Maks: 2MB)';
    @endif

    clearBtn.classList.add('hidden');
    clearBtn.style.display = 'none';
}

function calculateMargin() {
    const buy = parseFloat(document.getElementById('purchase_price').value) || 0;
    const sell = parseFloat(document.getElementById('selling_price').value) || 0;
    const margin = sell - buy;
    const percentage = buy > 0 ? ((margin / buy) * 100).toFixed(1) : 0;

    const marginElem = document.getElementById('margin-amount');
    const percentElem = document.getElementById('margin-percentage');

    marginElem.textContent = 'Rp ' + margin.toLocaleString('id-ID');
    percentElem.textContent = percentage + '%';

    if (margin < 0) {
        marginElem.className = 'font-mono text-base font-extrabold text-rose-600';
        percentElem.className = 'text-xs font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200';
    } else {
        marginElem.className = 'font-mono text-base font-extrabold text-[#006c49]';
        percentElem.className = 'text-xs font-bold px-2 py-0.5 rounded-full bg-[#e8f5e9] text-[#006c49] border border-[#006c49]/30';
    }
}

// Initial calculation on page load
document.addEventListener('DOMContentLoaded', calculateMargin);
</script>
@endsection
