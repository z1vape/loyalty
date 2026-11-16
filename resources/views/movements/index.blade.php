@extends('layouts.app')

@section('title', 'Z1 VapeOS | Movement History')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Flash Alert Messages --}}
    @if(session('success'))
        <div class="p-4 bg-[#e8f5e9] border border-[#006c49]/30 rounded-2xl flex items-center justify-between gap-3 text-[#006c49]">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span class="text-sm font-bold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ $errors->first() }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-error hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @php
        $categoryName = 'Operation';
        $pageTitle = 'Riwayat Mutasi Stok';
        $pageDesc = 'Review dan pantau riwayat mutasi buku besar stok fisik barang masuk dan keluar.';
        $canDeleteGlobal = false;
        if(request('type') === 'in') {
            $categoryName = 'Purchasing';
            $pageTitle = 'Stock In (Mutasi Masuk)';
            $pageDesc = 'Kelola tanggal pengadaan stok barang masuk, penerimaan supplier, dan rekap nilai belanja.';
            $canDeleteGlobal = auth()->user()->can('Stock In Destroy') || auth()->user()->can('Movement Destroy');
        } elseif(request('type') === 'out') {
            $categoryName = 'Operation';
            $pageTitle = 'Stock Out (Mutasi Keluar)';
            $pageDesc = 'Kelola tanggal pengeluaran barang fisik, distribusi toko, dan log transaksi per sesi.';
            $canDeleteGlobal = auth()->user()->can('Stock Out Destroy') || auth()->user()->can('Movement Destroy');
        } else {
            $canDeleteGlobal = auth()->user()->can('Movement Destroy') || (auth()->user()->can('Stock In Destroy') && auth()->user()->can('Stock Out Destroy'));
        }
        $canDeleteAny = auth()->user()->can('Stock In Destroy') || auth()->user()->can('Stock Out Destroy') || auth()->user()->can('Movement Destroy');
    @endphp

    {{-- Header & Action Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">{{ $pageTitle }}</h1>
            <p class="text-on-surface-variant text-body-lg">{{ $pageDesc }}</p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            @if(request('type') !== 'out' && $movementDates->total() > 0 && $canDeleteGlobal)
            <!-- Delete All Button -->
            <button
                type="button"
                onclick="openBulkDeleteDatesModal(true)"
                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs sm:text-sm font-bold transition-all shadow-xs active:scale-95 cursor-pointer"
                title="Hapus seluruh Date Movement di halaman ini"
            >
                <span class="material-symbols-outlined text-base text-rose-600">delete_sweep</span>
                <span>Hapus Semua</span>
            </button>
            @endif

            {{-- Primary Action: Buat Tanggal Sesi Movement --}}
            @can('Movement List')
            <button
                type="button"
                onclick="document.getElementById('modal-date-movement').showModal()"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
            >
                <span class="material-symbols-outlined text-base">calendar_add_on</span>
                <span>{{ request('type') === 'out' ? 'Buat Tanggal Stock Out' : (request('type') === 'in' ? 'Buat Tanggal Stock In' : 'Buat Tanggal Mutasi') }}</span>
            </button>
            @endcan
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <!-- Card 1: Total Logs -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Log Transaksi</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($summaryTotalLogs ?? 0) }} Log</h3>
            </div>
        </div>

        <!-- Card 2: Total Units -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ request('type') === 'out' ? 'bg-error-container/30 text-error' : 'bg-secondary-container/40 text-on-secondary-container' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">{{ request('type') === 'in' ? 'Total Unit Masuk' : (request('type') === 'out' ? 'Total Unit Keluar' : 'Total Fisik Termutasi') }}</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($summaryTotalQuantity ?? 0) }} Pcs</h3>
            </div>
        </div>

        <!-- Card 3: Total Nilai Transaksi -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Nilai Transaksi</p>
                <h3 class="text-xl font-headline-md font-extrabold text-emerald-800 font-mono mt-0.5 truncate">Rp {{ number_format($summaryTotalAmount ?? 0, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    {{-- Payment Method Breakdown (Stock Out & Stock In) --}}
    @if(!empty($paymentBreakdown))
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
        <div class="flex items-center justify-between gap-3 mb-3 pb-2 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">account_balance_wallet</span>
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface">
                    Akumulasi Metode Pembayaran {{ request('type') === 'in' ? 'Stock In (Pengadaan / Pembelian)' : (request('type') === 'out' ? 'Stock Out (Penjualan)' : 'Mutasi Stok') }}
                </span>
            </div>
            <span class="text-[10px] font-bold text-on-surface-variant bg-surface-container-high px-2.5 py-0.5 rounded-full">Semua Halaman</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
            @foreach($paymentBreakdown as $key => $info)
            @php
                $hasAmt = ($info['amount'] ?? 0) > 0;
            @endphp
            <div class="rounded-xl border p-2.5 flex flex-col justify-between transition-all {{ $hasAmt ? 'border-primary/30 bg-primary/5 shadow-xs' : 'border-outline-variant/50 bg-surface-container-low/30 opacity-60' }}">
                <span class="text-[10px] font-bold uppercase text-on-surface-variant truncate">{{ $info['label'] }}</span>
                <span class="text-xs font-extrabold font-mono mt-1 {{ $hasAmt ? 'text-primary' : 'text-on-surface-variant' }}">
                    Rp {{ number_format($info['amount'] ?? 0, 0, ',', '.') }}
                </span>
                <span class="text-[9px] text-on-surface-variant mt-0.5">{{ number_format($info['count'] ?? 0) }} log</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter & Search Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
        <form action="{{ route('movements.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Tanggal Sesi</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary transition" />
            </div>

            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Filter Produk</label>
                <select name="product_id" id="movement_product_id" class="w-full">
                    <option value="">Semua Produk</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>{{ $prod->name }} ({{ $prod->sku }})</option>
                    @endforeach
                </select>
            </div>

            @if(!request()->has('type'))
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Jenis Mutasi</label>
                <select name="type" class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary transition cursor-pointer">
                    <option value="">Semua Jenis (In & Out)</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stock In (Masuk)</option>
                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stock Out (Keluar)</option>
                </select>
            </div>
            @endif

            <div class="flex items-end gap-2 {{ request()->has('type') ? 'lg:col-span-2' : '' }}">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-secondary text-on-secondary hover:opacity-90 font-bold text-xs rounded-xl active:scale-95 transition-all cursor-pointer h-[38px]">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(request()->filled('date') || request()->filled('product_id') || (!request()->has('type') && request()->filled('type')))
                <a href="{{ route('movements.index', request('type') ? ['type' => request('type')] : []) }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-2 bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface font-semibold text-xs rounded-xl transition-all cursor-pointer h-[38px] no-underline" title="Reset Filter">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    <span>Reset</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Bulk Action Bar --}}
    @if($canDeleteAny)
    <div id="bulkActionBar" class="hidden px-4 py-3 mb-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex flex-wrap items-center justify-between gap-3 transition-all shadow-xs">
        <div class="flex items-center gap-3">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="selectAllCheckbox" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30 cursor-pointer accent-primary" />
                <span class="text-xs font-bold text-on-surface">Pilih Semua</span>
            </label>
            <span class="text-on-surface-variant text-xs">&bull;</span>
            <span id="selectedCountBadge" class="text-xs font-bold text-primary">0 tanggal dipilih</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" id="btnCancelSelection" class="px-3 py-1.5 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high text-xs font-semibold text-on-surface-variant transition active:scale-95 shadow-xs cursor-pointer">
                Batal
            </button>
            <button type="button" id="btnDeleteSelected" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-base">delete</span>
                <span id="btnDeleteSelectedText">Hapus Terpilih</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Movement Dates Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        @if($canDeleteAny)
                        <th class="px-4 py-3.5 w-10 text-center">
                            <input 
                                type="checkbox" 
                                id="selectAllThCheckbox" 
                                class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30 cursor-pointer accent-primary" 
                                title="Pilih semua di halaman ini"
                            />
                        </th>
                        @endif
                        <th class="px-4 py-3.5">Tanggal Sesi</th>
                        <th class="px-4 py-3.5">Jenis Mutasi</th>
                        <th class="px-4 py-3.5 text-center">Total Log</th>
                        <th class="px-4 py-3.5 text-right">Total Nilai</th>
                        <th class="px-4 py-3.5">Rincian Pembayaran / Catatan</th>
                        <th class="px-4 py-3.5">Dicatat Oleh</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($movementDates as $movementDate)
                    @php
                        $dateLabel    = \Carbon\Carbon::parse($movementDate->date)->translatedFormat('d F Y');
                        $type         = $movementDate->type ?? 'in';
                        $matchingMovements = $movementDate->movements
                            ->where('type', $type)
                            ->filter(fn($m) => ($m->status ?? 'approved') !== 'cancelled');
                        $matchingCount= $movementDate->movements->where('type', $type)->count();
                        $itemDrafts   = $movementDate->movements->where('status', 'draft')->count();
                        $dateTotalHarga = $matchingMovements->sum(function($m) {
                            return $m->total_price ?? ($m->quantity * ($m->type === 'in' ? ($m->purchase_price ?? 0) : ($m->selling_price ?? 0)));
                        });
                        $dateCash = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? 'cash') === 'cash')->sum('total_price');
                        $dateTransfer = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? '') === 'transfer')->sum('total_price');
                        $dateQris = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? '') === 'qris')->sum('total_price');
                        $dateDebit = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? '') === 'debit')->sum('total_price');
                        $dateCredit = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? '') === 'credit')->sum('total_price');
                        $dateSplit = $matchingMovements->filter(fn($m) => strtolower($m->payment_method ?? '') === 'split')->sum('total_price');
                        $canDeleteRow = ($type === 'in' && (auth()->user()->can('Stock In Destroy') || auth()->user()->can('Movement Destroy')))
                                     || ($type === 'out' && (auth()->user()->can('Stock Out Destroy') || auth()->user()->can('Movement Destroy')));
                    @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        @if($canDeleteAny)
                        <td class="px-4 py-3 text-center">
                            @if($canDeleteRow)
                            <input 
                                type="checkbox" 
                                name="date_ids[]" 
                                value="{{ $movementDate->id }}" 
                                class="date-checkbox w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30 cursor-pointer accent-primary transition"
                                title="Pilih {{ $dateLabel }}"
                            />
                            @endif
                        </td>
                        @endif

                        <td class="px-4 py-3">
                            <a href="{{ route('movement-dates.show', ['id' => $movementDate->id, 'type' => request('type')]) }}" class="font-bold text-on-surface hover:text-primary transition flex items-center gap-2.5 no-underline">
                                <div class="w-8 h-8 rounded-xl {{ $type === 'out' ? 'bg-error-container text-on-error-container' : 'bg-secondary-container text-on-secondary-container' }} flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base">calendar_today</span>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-on-surface hover:text-primary block">{{ $dateLabel }}</span>
                                    <span class="text-[10px] text-on-surface-variant font-mono">{{ $movementDate->date }}</span>
                                </div>
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            @if($type === 'out')
                                <span class="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-extrabold uppercase">
                                    <span class="material-symbols-outlined text-xs font-bold">north_east</span>
                                    Stock Out
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-extrabold uppercase">
                                    <span class="material-symbols-outlined text-xs font-bold">south_west</span>
                                    Stock In
                                </span>
                                @if($itemDrafts > 0)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-300 font-extrabold ml-1" title="Terdapat mutasi berstatus draft">
                                        <span class="material-symbols-outlined text-[11px]">pending</span>
                                        {{ $itemDrafts }} Draft
                                    </span>
                                @endif
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-on-surface text-xs font-mono">{{ number_format($matchingCount) }}</span>
                            <span class="text-[10px] text-on-surface-variant block">log transaksi</span>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <span class="font-mono font-extrabold text-xs text-primary">
                                Rp {{ number_format($dateTotalHarga, 0, ',', '.') }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            @if($type === 'out' && ($dateCash > 0 || $dateTransfer > 0 || $dateQris > 0 || $dateDebit > 0 || $dateCredit > 0 || $dateSplit > 0))
                            <div class="flex items-center gap-1 flex-wrap">
                                @if($dateCash > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Cash: Rp {{ number_format($dateCash, 0, ',', '.') }}
                                </span>
                                @endif
                                @if($dateTransfer > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200">
                                    TF: Rp {{ number_format($dateTransfer, 0, ',', '.') }}
                                </span>
                                @endif
                                @if($dateQris > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-800 border border-purple-200">
                                    QRIS: Rp {{ number_format($dateQris, 0, ',', '.') }}
                                </span>
                                @endif
                                @if($dateDebit > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-800 border border-cyan-200">
                                    Debit: Rp {{ number_format($dateDebit, 0, ',', '.') }}
                                </span>
                                @endif
                                @if($dateCredit > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                    Kredit: Rp {{ number_format($dateCredit, 0, ',', '.') }}
                                </span>
                                @endif
                                @if($dateSplit > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-800 border border-purple-200">
                                    Split: Rp {{ number_format($dateSplit, 0, ',', '.') }}
                                </span>
                                @endif
                            </div>
                            @elseif($movementDate->notes)
                                <span class="text-[11px] text-on-surface-variant line-clamp-1 italic">{{ $movementDate->notes }}</span>
                            @else
                                <span class="text-[11px] text-on-surface-variant opacity-40">-</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-md bg-surface-container text-on-surface-variant text-[10px] font-black flex items-center justify-center uppercase shrink-0">
                                    {{ substr($movementDate->creator->name ?? 'S', 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-on-surface text-xs truncate max-w-[120px]">{{ $movementDate->creator->name ?? 'System' }}</div>
                                    <div class="text-[10px] text-on-surface-variant font-mono">{{ $movementDate->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i') }} WIB</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('movement-dates.show', ['id' => $movementDate->id, 'type' => request('type')]) }}" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-all no-underline" title="Buka Detail Sesi">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                </a>
                                @if($canDeleteRow)
                                <button 
                                    type="button" 
                                    onclick="confirmDeleteSingleDate('{{ $movementDate->id }}', '{{ $dateLabel }}', '{{ $type === 'out' ? 'Stock Out' : 'Stock In' }}')"
                                    class="p-1.5 text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer" 
                                    title="Hapus Date Movement ini"
                                >
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canDeleteAny ? '8' : '7' }}" class="p-12 text-center">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2 block">calendar_today</span>
                            <p class="text-on-surface-variant font-semibold text-xs">Belum ada Date Movement.</p>
                            <p class="text-on-surface-variant text-[11px] mt-0.5">Klik tombol <strong>Buat Tanggal Mutasi</strong> di atas untuk membuat sesi baru.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($movementDates->hasPages())
    <div class="mt-6 px-4 py-3.5 border border-outline-variant rounded-2xl flex flex-col sm:flex-row justify-between items-center bg-surface-container-lowest gap-4 shadow-xs">
        <span class="text-on-surface-variant text-xs font-semibold text-center sm:text-left">
            Showing {{ $movementDates->firstItem() }} – {{ $movementDates->lastItem() }} of {{ $movementDates->total() }} dates
        </span>
        <div class="flex gap-2 w-full sm:w-auto justify-center">
            @if($movementDates->onFirstPage())
            <button disabled class="px-3 py-1.5 border border-outline-variant rounded-xl text-on-surface-variant text-xs font-bold cursor-not-allowed">← Prev</button>
            @else
            <a href="{{ $movementDates->previousPageUrl() }}" class="px-3 py-1.5 border border-outline-variant rounded-xl text-on-surface text-xs font-bold hover:bg-surface-container-low transition no-underline">← Prev</a>
            @endif
            @if($movementDates->hasMorePages())
            <a href="{{ $movementDates->nextPageUrl() }}" class="px-3 py-1.5 border border-outline-variant rounded-xl text-on-surface text-xs font-bold hover:bg-surface-container-low transition no-underline">Next →</a>
            @else
            <button disabled class="px-3 py-1.5 border border-outline-variant rounded-xl text-on-surface-variant text-xs font-bold cursor-not-allowed">Next →</button>
            @endif
        </div>
    </div>
    @endif

</div> {{-- End of .flex flex-col gap-6 --}}

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Date Movement — hanya membuat tanggal saja
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-date-movement"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-md bg-white overflow-visible">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c49]">calendar_add_on</span>
            <span>Buat Tanggal Sesi</span>
            @if(request('type') === 'in')
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-extrabold">Stock In</span>
            @elseif(request('type') === 'out')
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-extrabold">Stock Out</span>
            @endif
        </h3>
        <button onclick="document.getElementById('modal-date-movement').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <form action="{{ route('movement-dates.store') }}" method="POST" class="px-6 py-5 space-y-4">
        @csrf
        @if(request()->has('type'))
            <input type="hidden" name="type" id="dm_type_hidden" value="{{ request('type') }}">
        @else
            <div>
                <label for="dm_type" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Jenis Mutasi <span class="text-error">*</span>
                </label>
                <select name="type" id="dm_type" required onchange="toggleDmType(this.value)" class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition cursor-pointer">
                    <option value="in" selected>Stock In (Mutasi Masuk)</option>
                    <option value="out">Stock Out (Mutasi Keluar)</option>
                </select>
            </div>
        @endif

        {{-- Date picker --}}
        <div>
            <label for="dm_date" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Tanggal Movement <span class="text-error">*</span>
            </label>
            <input type="date" name="date" id="dm_date"
                value="{{ date('Y-m-d') }}"
                required
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition" />
        </div>

        {{-- Stock In Specific Defaults: Supplier & Payment Method --}}
        <div id="dm_stock_in_fields" class="{{ request('type') === 'out' ? 'hidden' : 'space-y-4' }}">
            {{-- Supplier Name --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="dm_supplier_name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-[#006c49]">storefront</span>
                        <span>Nama Supplier / Distributor</span>
                    </label>
                    <span class="text-[10px] font-semibold text-[#006c49] bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        Pilih / Ketik Baru
                    </span>
                </div>
                <select name="supplier_name" id="dm_supplier_name" class="w-full">
                    <option value="">-- Pilih atau Ketik Nama Supplier Baru --</option>
                    @if(isset($suppliers))
                        @foreach($suppliers as $sName)
                            <option value="{{ $sName }}">{{ $sName }}</option>
                        @endforeach
                    @endif
                </select>
                <p class="text-[11px] text-on-surface-variant/70 mt-1">Pilih dari Master Data atau ketik baru (otomatis tersimpan ke Pengaturan Sistem).</p>
            </div>

            {{-- Payment Method --}}
            <div>
                <label for="dm_payment_method" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-[#006c49]">payments</span>
                    <span>Metode Pembayaran Sesi</span>
                </label>
                <select name="payment_method" id="dm_payment_method" onchange="toggleDmTempo(this.value)"
                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition cursor-pointer">
                    <option value="cash" selected>Tunai / Kas Toko (Langsung Lunas)</option>
                    <option value="transfer">Transfer Bank (Langsung Lunas)</option>
                    <option value="tempo">Tempo / Hutang Supplier (Bayar Nanti / AP)</option>
                </select>
            </div>

            {{-- Due Date (Khusus Tempo) --}}
            <div id="dm_due_date_container" class="hidden">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="dm_due_date" class="block text-xs font-bold text-amber-950 uppercase tracking-wide flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-amber-700">event_busy</span>
                        <span>Tanggal Jatuh Tempo <span class="text-error font-bold">*</span></span>
                    </label>
                    <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">
                        Hutang Dagang (AP)
                    </span>
                </div>
                <input type="date" name="due_date" id="dm_due_date"
                    value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                    class="w-full px-4 py-2.5 bg-white border border-amber-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 transition" />
                <p class="text-[11px] text-amber-900/80 mt-1">Tagihan sesi ini otomatis dicatat di <strong>Buku Hutang Supplier</strong>.</p>
            </div>
        </div>

        {{-- Created by (read-only) --}}
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">Created By</label>
            <div class="w-full px-4 py-2.5 bg-surface-container border border-outline-variant rounded-xl text-sm text-on-surface-variant flex items-center gap-2 select-none">
                <span class="w-7 h-7 rounded-full bg-primary-container text-on-primary-container text-xs font-extrabold flex items-center justify-center uppercase shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </span>
                <span class="font-semibold">{{ auth()->user()->name }}</span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-primary-container text-on-primary-container font-bold">
                    {{ auth()->user()->roles->first()?->name ?? 'No Role' }}
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-1 opacity-60">Otomatis tercatat sebagai user yang sedang login.</p>
        </div>

        {{-- Notes --}}
        <div>
            <label for="dm_notes" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Notes <span class="text-on-surface-variant font-normal">(opsional)</span>
            </label>
            <textarea name="notes" id="dm_notes" rows="2"
                placeholder="Keterangan opsional..."
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition resize-none"></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-1">
            <button type="button"
                onclick="document.getElementById('modal-date-movement').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] rounded-xl text-sm font-bold active:scale-95 transition shadow-sm border-0 cursor-pointer">
                Buat Tanggal
            </button>
        </div>
    </form>
</dialog>

{{-- Delete Confirmation Modal --}}
<div id="deleteDateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-white rounded-2xl border border-outline-variant shadow-2xl max-w-md w-full mx-4 p-6 flex flex-col gap-5">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">delete_forever</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="deleteDateModalTitle" class="text-lg font-bold text-on-surface">Konfirmasi Hapus</h3>
                <p id="deleteDateModalDesc" class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data movement ini? Seluruh log transaksi dan perubahan stok akan disesuaikan kembali.
                </p>
            </div>
        </div>

        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-rose-600 shrink-0">warning</span>
            <span>Tindakan ini tidak dapat dibatalkan.</span>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
            <button type="button" onclick="closeDeleteDateModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="confirmDeleteDateBtn" onclick="submitDeleteDateForm()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-base">delete</span>
                <span id="confirmDeleteDateBtnText">Ya, Hapus</span>
            </button>
        </div>
    </div>
</div>

{{-- Hidden Form for bulk delete submission --}}
<form id="bulkDeleteDatesForm" action="{{ route('movement-dates.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    @if(request('type'))
        <input type="hidden" name="type" value="{{ request('type') }}">
    @endif
    <input type="hidden" name="delete_all" id="deleteAllDatesInput" value="0">
    <div id="bulkDeleteDatesContainer"></div>
</form>

{{-- Hidden Form for single delete submission --}}
<form id="singleDeleteDateForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
    @if(request('type'))
        <input type="hidden" name="type" value="{{ request('type') }}">
    @endif
</form>

<script>
    let isDeletingAll = false;
    let singleDateDeleteUrl = null;

    function getDateCheckboxes() {
        return document.querySelectorAll('.date-checkbox');
    }

    function updateDateBulkUI() {
        const checkboxes = getDateCheckboxes();
        const checkedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked);
        const checkedCount = checkedCheckboxes.length;
        const totalCount = checkboxes.length;
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllThCheckbox = document.getElementById('selectAllThCheckbox');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountBadge = document.getElementById('selectedCountBadge');
        const btnDeleteSelectedText = document.getElementById('btnDeleteSelectedText');

        const syncSelectAll = (el) => {
            if (!el) return;
            if (checkedCount === 0) {
                el.checked = false;
                el.indeterminate = false;
            } else if (checkedCount === totalCount && totalCount > 0) {
                el.checked = true;
                el.indeterminate = false;
            } else {
                el.checked = false;
                el.indeterminate = true;
            }
        };

        syncSelectAll(selectAllCheckbox);
        syncSelectAll(selectAllThCheckbox);

        if (checkedCount > 0) {
            if (bulkActionBar) bulkActionBar.classList.remove('hidden');
            if (selectedCountBadge) selectedCountBadge.textContent = checkedCount + ' tanggal dipilih';
            if (btnDeleteSelectedText) btnDeleteSelectedText.textContent = 'Hapus Terpilih (' + checkedCount + ')';
        } else {
            if (bulkActionBar) bulkActionBar.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('movement_product_id')) {
            new TomSelect('#movement_product_id', {
                create: false,
                allowEmptyOption: true,
                maxOptions: 1000,
                placeholder: 'Semua Produk',
            });
        }

        if (document.getElementById('dm_supplier_name')) {
            new TomSelect('#dm_supplier_name', {
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

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllThCheckbox = document.getElementById('selectAllThCheckbox');
        const btnCancelSelection = document.getElementById('btnCancelSelection');
        const btnDeleteSelected = document.getElementById('btnDeleteSelected');

        const handleSelectAllChange = function(isChecked) {
            const checkboxes = getDateCheckboxes();
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateDateBulkUI();
        };

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                handleSelectAllChange(selectAllCheckbox.checked);
            });
        }

        if (selectAllThCheckbox) {
            selectAllThCheckbox.addEventListener('change', function () {
                handleSelectAllChange(selectAllThCheckbox.checked);
            });
        }

        document.querySelectorAll('.date-checkbox').forEach(cb => {
            cb.addEventListener('change', updateDateBulkUI);
        });

        if (btnCancelSelection) {
            btnCancelSelection.addEventListener('click', function () {
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
                if (selectAllThCheckbox) {
                    selectAllThCheckbox.checked = false;
                    selectAllThCheckbox.indeterminate = false;
                }
                getDateCheckboxes().forEach(cb => cb.checked = false);
                updateDateBulkUI();
            });
        }

        if (btnDeleteSelected) {
            btnDeleteSelected.addEventListener('click', function () {
                openBulkDeleteDatesModal(false);
            });
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteDateModal');
            if (e.target === modal) {
                closeDeleteDateModal();
            }
        });
    });

    function confirmDeleteSingleDate(id, dateLabel, typeLabel) {
        singleDateDeleteUrl = `{{ url('/movement-dates') }}/${id}`;
        isDeletingAll = false;
        const modal = document.getElementById('deleteDateModal');
        const title = document.getElementById('deleteDateModalTitle');
        const desc = document.getElementById('deleteDateModalDesc');
        const btnText = document.getElementById('confirmDeleteDateBtnText');

        title.textContent = `Hapus Date Movement ${typeLabel}`;
        desc.innerHTML = `Apakah Anda yakin ingin menghapus <strong>Date Movement ${typeLabel} (${dateLabel})</strong>? Seluruh movement log terkait akan dihapus dan stok barang akan disesuaikan kembali.`;
        btnText.textContent = 'Hapus Tanggal';

        modal.classList.remove('hidden');
    }

    function openBulkDeleteDatesModal(deleteAll = false) {
        singleDateDeleteUrl = null;
        isDeletingAll = deleteAll;
        const modal = document.getElementById('deleteDateModal');
        const title = document.getElementById('deleteDateModalTitle');
        const desc = document.getElementById('deleteDateModalDesc');
        const btnText = document.getElementById('confirmDeleteDateBtnText');

        if (deleteAll) {
            title.textContent = 'Hapus Seluruh Date Movement';
            desc.innerHTML = 'Apakah Anda yakin ingin menghapus <strong>seluruh {{ $movementDates->total() }} Date Movement</strong>? Seluruh catatan movement dan riwayat stok terkait akan disesuaikan secara permanen.';
            btnText.textContent = 'Hapus Seluruh Tanggal';
        } else {
            const checkedCount = Array.from(getDateCheckboxes()).filter(cb => cb.checked).length;
            if (checkedCount === 0) {
                alert('Pilih minimal satu tanggal movement untuk dihapus.');
                return;
            }
            title.textContent = 'Hapus Tanggal Terpilih';
            desc.innerHTML = `Apakah Anda yakin ingin menghapus <strong>${checkedCount} tanggal movement terpilih</strong>? Seluruh movement log di dalamnya akan dihapus dan stok barang akan disesuaikan kembali.`;
            btnText.textContent = `Hapus ${checkedCount} Tanggal`;
        }

        modal.classList.remove('hidden');
    }

    function closeDeleteDateModal() {
        const modal = document.getElementById('deleteDateModal');
        if (modal) modal.classList.add('hidden');
    }

    function submitDeleteDateForm() {
        if (singleDateDeleteUrl) {
            const form = document.getElementById('singleDeleteDateForm');
            form.action = singleDateDeleteUrl;
            form.submit();
        } else {
            const form = document.getElementById('bulkDeleteDatesForm');
            const deleteAllInput = document.getElementById('deleteAllDatesInput');
            const container = document.getElementById('bulkDeleteDatesContainer');
            container.innerHTML = '';

            if (isDeletingAll) {
                deleteAllInput.value = '1';
            } else {
                deleteAllInput.value = '0';
                const checked = Array.from(getDateCheckboxes()).filter(cb => cb.checked);
                checked.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    container.appendChild(input);
                });
            }

            form.submit();
        }
    }

    function toggleDmTempo(val) {
        const dueDateContainer = document.getElementById('dm_due_date_container');
        if (dueDateContainer) {
            if (val === 'tempo' || val === 'hutang') {
                dueDateContainer.classList.remove('hidden');
            } else {
                dueDateContainer.classList.add('hidden');
            }
        }
    }

    function toggleDmType(val) {
        const stockInFields = document.getElementById('dm_stock_in_fields');
        if (stockInFields) {
            if (val === 'in') {
                stockInFields.classList.remove('hidden');
            } else {
                stockInFields.classList.add('hidden');
            }
        }
    }
</script>

@endsection
