@extends('layouts.app')

@section('title', 'Import Data Inventaris | InvenTrack')

@section('content')
<div class="max-w-3xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Import Data Inventaris</h1>
            <p class="text-on-surface-variant text-body-lg">
                Unggah berkas spreadsheet Excel (.xlsx, .xls) atau CSV untuk menambahkan katalog produk secara massal.
            </p>
        </div>

        <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline self-start sm:self-auto">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Daftar</span>
        </a>
    </div>

    <!-- Template Download Banner -->
    <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#eff4ff] text-[#006c49] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">description</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-on-surface">Format Template Excel</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Gunakan format kolom resmi agar data dapat diproses dan dibaca dengan akurat oleh sistem.</p>
            </div>
        </div>

        <a href="{{ route('inventory.import.template') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#006c49]/30 bg-[#e8f5e9] text-[#006c49] font-bold text-xs hover:bg-[#006c49] hover:text-white transition shadow-sm shrink-0">
            <span class="material-symbols-outlined text-base">download</span>
            <span>Unduh Template</span>
        </a>
    </div>

    <!-- Upload Form Card -->
    <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm">
        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            @csrf

            <div>
                <label for="file" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">
                    Pilih Berkas Spreadsheet (.xlsx, .xls, .csv) <span class="text-error">*</span>
                </label>
                <div class="relative border-2 border-dashed border-outline-variant hover:border-[#006c49] rounded-2xl p-8 text-center bg-surface-container-lowest transition-colors group cursor-pointer">
                    <input type="file" name="file" id="file" accept=".csv, .xlsx, .xls" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName(this)" />
                    <div class="flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center text-[#006c49] group-hover:scale-110 transition-transform mb-3">
                            <span class="material-symbols-outlined text-2xl">upload_file</span>
                        </div>
                        <p id="file-label" class="text-sm font-bold text-on-surface">Klik atau seret berkas spreadsheet ke area ini</p>
                        <p class="text-xs text-on-surface-variant mt-1">Mendukung format .xlsx, .xls, atau .csv (Maksimal 5MB)</p>
                    </div>
                </div>
                @error('file') <p class="text-error text-xs mt-2 font-medium">{{ $message }}</p> @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/60">
                <a href="{{ route('inventory.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-on-surface text-sm font-semibold transition shadow-sm">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl active:scale-95 duration-150 transition shadow-sm hover:shadow text-sm cursor-pointer">
                    <span class="material-symbols-outlined text-base">cloud_upload</span>
                    <span>Unggah & Proses Import</span>
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function updateFileName(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        label.textContent = 'Berkas dipilih: ' + input.files[0].name;
        label.classList.add('text-[#006c49]');
    } else {
        label.textContent = 'Klik atau seret berkas spreadsheet ke area ini';
        label.classList.remove('text-[#006c49]');
    }
}
</script>
@endsection
