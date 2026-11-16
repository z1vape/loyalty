@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buat Rekonsiliasi Bank & Kas')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16">

    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <a href="{{ route('accounting.reconciliations.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80 transition-colors no-underline">
                    <span class="material-symbols-outlined text-sm">account_balance</span>
                    Rekonsiliasi Bank
                </a>
                <span class="text-xs text-on-surface-variant font-medium">/</span>
                <span class="text-xs text-on-surface-variant font-bold">Penyesuaian Saldo</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Pencocokan & Koreksi Saldo</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                Masukkan saldo riil yang ada pada mutasi ATM / rekening Anda. Sistem akan menghitung selisih dan membuatkan jurnal penyeimbang secara otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.reconciliations.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-surface-container-low text-on-surface hover:bg-surface-container-high transition-all border border-outline-variant/50 no-underline shadow-2xs">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('accounting.reconciliations.store') }}" method="POST" id="recon-form" class="space-y-6">
        @csrf

        {{-- 1. Step 1: Pilih Akun Rekening --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs">
            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-2">
                1. Pilih Akun Rekening / Kas Yang Ingin Disamakan
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($accounts as $acc)
                @php
                    $isSelected = $acc->id === $selectedAccount->id;
                @endphp
                <label class="relative flex flex-col p-4 rounded-xl border {{ $isSelected ? 'border-[#006c49] bg-emerald-50/40 ring-1 ring-[#006c49]/30' : 'border-outline-variant/70 bg-surface-container-lowest hover:bg-surface-container-low/40' }} transition-all cursor-pointer group">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <input 
                                type="radio" 
                                name="account_id" 
                                value="{{ $acc->id }}" 
                                data-balance="{{ (float) $acc->current_balance }}"
                                data-code="{{ $acc->code }}"
                                data-name="{{ $acc->name }}"
                                {{ $isSelected ? 'checked' : '' }}
                                onchange="onAccountChange(this)"
                                class="text-[#006c49] focus:ring-[#006c49]/30"
                            >
                            <span class="font-bold text-xs sm:text-sm text-on-surface group-hover:text-primary transition-colors">{{ $acc->name }}</span>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-surface-container text-on-surface-variant font-bold">{{ $acc->code }}</span>
                    </div>

                    <div class="mt-auto pt-2 border-t border-outline-variant/40 flex items-center justify-between">
                        <span class="text-[11px] text-on-surface-variant">Saldo Sistem:</span>
                        <span class="font-extrabold text-xs font-mono text-on-surface">Rp {{ number_format($acc->current_balance, 0, ',', '.') }}</span>
                    </div>
                </label>
                @endforeach
            </div>

            @if($store && $store->bank_name)
            <div class="mt-3.5 p-3 rounded-xl bg-blue-50/70 border border-blue-200/60 text-xs text-blue-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-blue-600 text-lg">account_balance</span>
                <span>Rekening Resmi Outlet: <strong>{{ $store->bank_name }} - {{ $store->bank_account_number }}</strong> (a/n {{ $store->bank_account_name ?: $store->name }})</span>
            </div>
            @endif
        </div>

        {{-- 2. Step 2: Input Saldo Nyata (ATM) & Tanggal --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider">
                2. Input Saldo Nyata & Tanggal Pencocokan
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Tanggal Rekonsiliasi --}}
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Tanggal Mutasi / Cut-off *</label>
                    <input 
                        type="date" 
                        name="reconciliation_date" 
                        value="{{ date('Y-m-d') }}" 
                        required 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest text-xs font-medium focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49]"
                    >
                    <span class="text-[11px] text-on-surface-variant mt-1 block">Biasanya tanggal hari ini atau tanggal cetak rekening koran.</span>
                </div>

                {{-- Saldo Riil ATM --}}
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">
                        Saldo Nyata di Rekening / ATM (Rp) *
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-xs font-bold text-on-surface-variant font-mono">Rp</span>
                        <input 
                            type="number" 
                            id="actual_balance" 
                            name="actual_balance" 
                            step="any"
                            min="0"
                            placeholder="0"
                            value="{{ old('actual_balance', $selectedAccount->current_balance) }}"
                            required 
                            oninput="calculateDiscrepancy()"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest text-sm font-extrabold font-mono focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49]"
                        >
                    </div>
                    <span id="actual_balance_preview" class="text-[11px] font-mono text-[#006c49] font-bold mt-1 block">
                        Rp {{ number_format($selectedAccount->current_balance, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- 3. Live Comparison & Difference Indicator --}}
            <div class="pt-4 border-t border-outline-variant/60">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-4">
                    {{-- Saldo Sistem --}}
                    <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/60">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant block">Saldo Sistem (Sebelum)</span>
                        <span id="card-system-balance" class="text-base sm:text-lg font-black font-mono text-on-surface mt-0.5 block">
                            Rp {{ number_format($selectedAccount->current_balance, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Saldo Riil ATM --}}
                    <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/60">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant block">Saldo Riil ATM (Input)</span>
                        <span id="card-actual-balance" class="text-base sm:text-lg font-black font-mono text-on-surface mt-0.5 block">
                            Rp {{ number_format($selectedAccount->current_balance, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Selisih --}}
                    <div id="card-diff-container" class="p-3.5 rounded-xl bg-slate-100 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant block">Selisih Yang Disesuaikan</span>
                        <span id="card-diff-balance" class="text-base sm:text-lg font-black font-mono text-slate-700 mt-0.5 block">
                            Rp 0
                        </span>
                    </div>
                </div>

                {{-- Status Banner --}}
                <div id="status-banner" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-600 text-xl mt-0.5">check_circle</span>
                    <div>
                        <span class="font-extrabold block text-sm mb-0.5">Saldo Sudah Cocok!</span>
                        <p class="leading-relaxed">Saldo sistem saat ini sudah sama persis dengan saldo yang Anda masukkan. Tidak ada penyesuaian nilai jurnal yang diperlukan.</p>
                    </div>
                </div>
            </div>

            {{-- 4. Step 3: Akun Penyeimbang (Tampil jika selisih != 0) --}}
            <div id="adjustment-section" class="pt-4 border-t border-outline-variant/60 hidden space-y-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5 flex items-center justify-between">
                        <span>Pilih Akun Penyeimbang (Akun Lawan) *</span>
                        <span id="adj-recommendation" class="text-[11px] text-primary font-bold">Rekomendasi: Beban Operasional Lain-lain</span>
                    </label>
                    <select 
                        id="adjustment_account_id" 
                        name="adjustment_account_id" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest text-xs font-semibold focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49]"
                    >
                        @php
                            $specialReconAccounts = $adjustmentAccounts->whereIn('code', ['6111', '6112', '4205', '3103']);
                            $otherExpenses = $adjustmentAccounts->where('type', 'expense')->whereNotIn('code', ['6111', '6112']);
                            $otherRevenues = $adjustmentAccounts->where('type', 'revenue')->whereNotIn('code', ['4205']);
                            $otherEquities = $adjustmentAccounts->where('type', 'equity')->whereNotIn('code', ['3103']);
                        @endphp

                        @if($specialReconAccounts->isNotEmpty())
                        <optgroup label="Akun Khusus Rekonsiliasi & Selisih Saldo (Paling Disarankan)">
                            @foreach($specialReconAccounts as $adj)
                                <option value="{{ $adj->id }}">
                                    {{ $adj->code }} - {{ $adj->name }} ({{ $adj->type === 'expense' ? 'Beban/Pengurang Saldo' : ($adj->type === 'revenue' ? 'Pendapatan/Penambah Saldo' : 'Ekuitas/Modal') }})
                                </option>
                            @endforeach
                        </optgroup>
                        @endif

                        <optgroup label="Akun Beban Operasional Lainnya (Jika selisih karena ada biaya toko belum dicatat)">
                            @foreach($otherExpenses as $adj)
                                <option value="{{ $adj->id }}">
                                    {{ $adj->code }} - {{ $adj->name }} ({{ $adj->category }})
                                </option>
                            @endforeach
                        </optgroup>

                        <optgroup label="Akun Pendapatan Lainnya (Jika selisih karena ada penerimaan belum dicatat)">
                            @foreach($otherRevenues as $adj)
                                <option value="{{ $adj->id }}">
                                    {{ $adj->code }} - {{ $adj->name }} ({{ $adj->category }})
                                </option>
                            @endforeach
                        </optgroup>

                        <optgroup label="Akun Modal & Ekuitas (Jika penyesuaian modal awal / prive pemilik)">
                            @foreach($otherEquities as $adj)
                                <option value="{{ $adj->id }}">
                                    {{ $adj->code }} - {{ $adj->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="text-[11px] text-on-surface-variant mt-1 leading-relaxed">
                        Akun ini digunakan untuk mencatat sisi Debit/Kredit penyeimbang agar pembukuan neraca dan laba-rugi tetap balance.
                    </p>
                </div>
            </div>

            {{-- 5. Catatan --}}
            <div class="pt-3 border-t border-outline-variant/60">
                <label class="block text-xs font-bold text-on-surface mb-1.5">Keterangan / Catatan Rekonsiliasi (Opsional)</label>
                <textarea 
                    name="notes" 
                    rows="2" 
                    placeholder="Contoh: Pencocokan saldo mutasi ATM BCA bulan berjalan atau penyesuaian potongan admin bank."
                    class="w-full px-3.5 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest text-xs focus:ring-2 focus:ring-[#006c49]/30 focus:border-[#006c49]"
                ></textarea>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-on-surface-variant text-center sm:text-left">
                Setelah disimpan, saldo rekening <strong id="selected-acc-name-label" class="text-on-surface">{{ $selectedAccount->name }}</strong> akan langsung sinkron dengan saldo ATM Anda.
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <a href="{{ route('accounting.reconciliations.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors border border-outline-variant/50 no-underline text-center">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-extrabold shadow-sm hover:shadow transition-all cursor-pointer w-full sm:w-auto">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span>Simpan & Samakan Saldo</span>
                </button>
            </div>
        </div>
    </form>

</div>

<script>
    let currentSystemBalance = {{ (float) $selectedAccount->current_balance }};
    let currentAccountName = "{{ $selectedAccount->name }}";
    let currentAccountCode = "{{ $selectedAccount->code }}";

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(number);
    }

    function onAccountChange(radio) {
        currentSystemBalance = parseFloat(radio.dataset.balance) || 0;
        currentAccountName = radio.dataset.name;
        currentAccountCode = radio.dataset.code;

        document.getElementById('card-system-balance').innerText = formatRupiah(currentSystemBalance);
        document.getElementById('selected-acc-name-label').innerText = currentAccountName;

        // Reset actual balance input if desired or recalculate
        calculateDiscrepancy();
    }

    function calculateDiscrepancy() {
        const inputVal = document.getElementById('actual_balance').value;
        const actualBalance = parseFloat(inputVal) || 0;
        const diff = Math.round((actualBalance - currentSystemBalance) * 100) / 100;

        // Update previews
        document.getElementById('actual_balance_preview').innerText = formatRupiah(actualBalance);
        document.getElementById('card-actual-balance').innerText = formatRupiah(actualBalance);

        const diffContainer = document.getElementById('card-diff-container');
        const diffLabel = document.getElementById('card-diff-balance');
        const statusBanner = document.getElementById('status-banner');
        const adjSection = document.getElementById('adjustment-section');
        const adjRec = document.getElementById('adj-recommendation');
        const adjSelect = document.getElementById('adjustment_account_id');

        if (Math.abs(diff) < 0.01) {
            // MATCHED
            diffContainer.className = 'p-3.5 rounded-xl bg-slate-100 border border-slate-200';
            diffLabel.innerText = 'Rp 0';
            diffLabel.className = 'text-base sm:text-lg font-black font-mono text-slate-700 mt-0.5 block';

            statusBanner.className = 'p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-start gap-3';
            statusBanner.innerHTML = `
                <span class="material-symbols-outlined text-emerald-600 text-xl mt-0.5">check_circle</span>
                <div>
                    <span class="font-extrabold block text-sm mb-0.5">Saldo Sudah Cocok!</span>
                    <p class="leading-relaxed">Saldo sistem saat ini sudah sama persis dengan saldo yang Anda masukkan. Tidak ada jurnal penyesuaian yang perlu dibuat.</p>
                </div>
            `;
            adjSection.classList.add('hidden');
        } else if (diff > 0) {
            // SURPLUS IN ATM
            diffContainer.className = 'p-3.5 rounded-xl bg-emerald-50 border border-emerald-300';
            diffLabel.innerText = '+' + formatRupiah(diff);
            diffLabel.className = 'text-base sm:text-lg font-black font-mono text-emerald-800 mt-0.5 block';

            statusBanner.className = 'p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-start gap-3';
            statusBanner.innerHTML = `
                <span class="material-symbols-outlined text-blue-600 text-xl mt-0.5">add_circle</span>
                <div>
                    <span class="font-extrabold block text-sm mb-0.5">Saldo ATM Lebih Besar (+${formatRupiah(diff)})</span>
                    <p class="leading-relaxed">Sistem akan <strong>MENAMBAH</strong> saldo rekening ${currentAccountName} sebesar ${formatRupiah(diff)} agar sama dengan ATM.</p>
                </div>
            `;
            adjSection.classList.remove('hidden');
            adjRec.innerText = 'Rekomendasi: 4205 - Pendapatan Selisih Kas & Rekonsiliasi Bank';

            // Select 4205 if available, fallback to 4201
            let selectedSurplus = false;
            for (let opt of adjSelect.options) {
                if (opt.text.includes('4205')) {
                    opt.selected = true;
                    selectedSurplus = true;
                    break;
                }
            }
            if (!selectedSurplus) {
                for (let opt of adjSelect.options) {
                    if (opt.text.includes('4201')) {
                        opt.selected = true;
                        break;
                    }
                }
            }
        } else {
            // DEFICIT IN ATM
            const absDiff = Math.abs(diff);
            diffContainer.className = 'p-3.5 rounded-xl bg-rose-50 border border-rose-300';
            diffLabel.innerText = '-' + formatRupiah(absDiff);
            diffLabel.className = 'text-base sm:text-lg font-black font-mono text-rose-800 mt-0.5 block';

            statusBanner.className = 'p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-3';
            statusBanner.innerHTML = `
                <span class="material-symbols-outlined text-amber-600 text-xl mt-0.5">remove_circle</span>
                <div>
                    <span class="font-extrabold block text-sm mb-0.5">Saldo ATM / Rekening Lebih Kecil (-${formatRupiah(absDiff)})</span>
                    <p class="leading-relaxed">Ada dana keluar, potongan admin bank, atau selisih fisik yang belum tercatat. Sistem akan <strong>MENGURANGI</strong> saldo rekening ${currentAccountName} sebesar ${formatRupiah(absDiff)} agar sama dengan mutasi nyata.</p>
                </div>
            `;
            adjSection.classList.remove('hidden');
            adjRec.innerText = 'Rekomendasi: 6111 - Beban Selisih Kas & Rekonsiliasi Bank atau 6112 - Beban Administrasi Bank';

            // Select 6111 if available, fallback to 6110
            let selectedDeficit = false;
            for (let opt of adjSelect.options) {
                if (opt.text.includes('6111')) {
                    opt.selected = true;
                    selectedDeficit = true;
                    break;
                }
            }
            if (!selectedDeficit) {
                for (let opt of adjSelect.options) {
                    if (opt.text.includes('6110')) {
                        opt.selected = true;
                        break;
                    }
                }
            }
        }
    }

    // Trigger calculation on load
    document.addEventListener('DOMContentLoaded', () => {
        calculateDiscrepancy();
    });
</script>
@endsection

