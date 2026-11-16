@extends('layouts.app')

@section('title', 'Z1 VapeOS | Movement — ' . \Carbon\Carbon::parse($movementDate->date)->translatedFormat('d F Y'))

@section('content')

{{-- ─── Flash Messages ──────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 rounded-xl bg-secondary-container text-on-secondary-container font-semibold text-sm flex items-center gap-2 shadow-sm">
    <span class="material-symbols-outlined text-base">check_circle</span>
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 px-4 py-3 rounded-xl bg-error-container text-on-error-container font-semibold text-sm flex items-center gap-2 shadow-sm">
    <span class="material-symbols-outlined text-base">error</span>
    {{ $errors->first() }}
</div>
@endif

@php
    $dateLabel    = \Carbon\Carbon::parse($movementDate->date)->translatedFormat('d F Y');
    $movList = method_exists($movements, 'getCollection') ? $movements->getCollection() : $movements;
    $pageTotalHarga   = $movList->where('status', '!=', 'cancelled')->sum('total_price');
    $totalHarga   = $grandTotalHarga ?? $movList->where('status', '!=', 'cancelled')->sum('total_price');
    $totalDiscount= $grandTotalDiscount ?? $movList->where('status', '!=', 'cancelled')->sum('discount');
    $totalQuantity= $grandTotalQuantity ?? $movList->where('status', '!=', 'cancelled')->sum('quantity');
    $totalRecords = $grandTotalRecords ?? (method_exists($movements, 'total') ? $movements->total() : $movements->count());
    $totalCancelledRecords = $totalCancelledRecords ?? 0;
    $canDeleteDate = ($movementDate->type === 'in' && (auth()->user()->can('Stock In Destroy') || auth()->user()->can('Movement Destroy')))
                  || ($movementDate->type === 'out' && (auth()->user()->can('Stock Out Destroy') || auth()->user()->can('Movement Destroy')));
    $canApprove = auth()->user()->can('Stock In Store') || auth()->user()->can('Stock In Create') || auth()->user()->can('Movement List');
    $allDraftCount = \App\Models\StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
        ->where('type', $movementDate->type ?? 'in')
        ->where('status', 'draft')
        ->count();
    $allApprovedCount = \App\Models\StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
        ->where('type', 'in')
        ->where('status', 'approved')
        ->count();
@endphp

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-3 flex-wrap">
            <span class="material-symbols-outlined text-primary text-3xl">calendar_today</span>
            {{ $dateLabel }}
            @if($movementDate->type === 'out')
                <span class="inline-flex items-center gap-1 bg-error-container text-on-error-container px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm font-bold">north_east</span>
                    Stock Out
                </span>
            @else
                <span class="inline-flex items-center gap-1 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm font-bold">south_west</span>
                    Stock In
                </span>
            @endif
        </h1>
        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-on-surface-variant">
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">person</span>
                Dibuat oleh: <strong class="ml-1 text-on-surface">{{ $movementDate->creator->name ?? 'System' }}</strong>
            </span>
            <span class="opacity-40">·</span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">schedule</span>
                {{ $movementDate->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y, H:i') }} WIB
            </span>
            @if($movementDate->notes)
            <span class="opacity-40">·</span>
            <span class="italic">{{ $movementDate->notes }}</span>
            @endif
        </div>

        @if(($movementDate->type ?? 'in') === 'in')
        <div class="flex flex-wrap items-center gap-2 mt-2.5">
            {{-- Supplier Pill --}}
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-surface-container-high text-on-surface border border-outline-variant shadow-xs">
                <span class="material-symbols-outlined text-sm text-primary">storefront</span>
                <span>Supplier: <strong class="text-on-surface">{{ $movementDate->supplier_name ?: 'Umum / Belum Diset' }}</strong></span>
            </span>

            {{-- Payment Method Pill --}}
            @php
                $headerPm = strtolower($movementDate->payment_method ?? 'cash');
                $isHeaderTempo = in_array($headerPm, ['tempo', 'hutang', 'credit']);
            @endphp
            @if($isHeaderTempo)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-300 shadow-xs">
                    <span class="material-symbols-outlined text-sm text-amber-700">event_busy</span>
                    <span>Metode: <strong>Tempo (Hutang AP)</strong></span>
                    @if($movementDate->due_date)
                        <span class="opacity-40">·</span>
                        <span>Jatuh Tempo: <strong>{{ \Carbon\Carbon::parse($movementDate->due_date)->translatedFormat('d M Y') }}</strong></span>
                    @endif
                </span>
            @elseif($headerPm === 'transfer')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-900 border border-blue-200 shadow-xs">
                    <span class="material-symbols-outlined text-sm text-blue-600">account_balance</span>
                    <span>Metode: <strong>Transfer Bank</strong></span>
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-900 border border-emerald-200 shadow-xs">
                    <span class="material-symbols-outlined text-sm text-emerald-600">payments</span>
                    <span>Metode: <strong>Tunai (Cash)</strong></span>
                </span>
            @endif

            {{-- Quick Edit Session Button --}}
            <button type="button" onclick="document.getElementById('modal-edit-session-info').showModal()"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-primary hover:bg-primary-container/40 transition border border-dashed border-primary/40 bg-transparent cursor-pointer"
                    title="Ubah default Supplier atau Metode Pembayaran sesi ini">
                <span class="material-symbols-outlined text-xs">edit</span>
                <span>Ubah Default Sesi</span>
            </button>
        </div>
        @endif
    </div>

    <div class="flex items-center gap-2 self-start md:self-auto flex-wrap">
        {{-- Delete Date Movement Button --}}
        @if($canDeleteDate)
        <form action="{{ route('movement-dates.destroy', $movementDate->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Date Movement ini? Seluruh log movement di dalamnya akan dihapus dan stok barang akan disesuaikan kembali.');">
            @csrf
            @method('DELETE')
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-sm font-semibold transition-all shadow-sm active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-rose-600">delete</span>
                <span>Hapus Tanggal Ini</span>
            </button>
        </form>
        @endif

        {{-- Import Product Button (Stock In) --}}
        @if(($movementDate->type ?? 'in') === 'in')
        @can('Stock In Store')
        <button
            type="button"
            onclick="document.getElementById('modal-import-stock-in').showModal()"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold rounded-xl text-sm transition-all shadow border-0 cursor-pointer shrink-0"
        >
            <span class="material-symbols-outlined text-base">upload_file</span>
            Import Product
        </button>
        @endcan
        @endif

        {{-- Log Movement Button --}}
        @can('Stock In Store')
        <button
            onclick="document.getElementById('modal-log-movement').showModal()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-sm hover:opacity-90 active:scale-95 transition-all shadow border-0 cursor-pointer shrink-0"
        >
            <span class="material-symbols-outlined text-base">add</span>
            Log Movement
        </button>
        @endcan
    </div>
</div>

{{-- ─── Summary Cards ───────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-2xl bg-surface-container-high flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-on-surface-variant text-2xl">receipt_long</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Total Logs</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ number_format($totalRecords) }}</p>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">
                @if(method_exists($movements, 'hasPages') && $movements->hasPages())
                    Semua Halaman (Hal. {{ $movements->currentPage() }} dari {{ $movements->lastPage() }})
                @else
                    Seluruh transaksi pada tanggal ini
                @endif
            </p>
            @if(!empty($totalCancelledRecords) && $totalCancelledRecords > 0)
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                        <span class="material-symbols-outlined text-[12px]">cancel</span>
                        {{ $totalCancelledRecords }} dibatalkan (dikecualikan)
                    </span>
                </div>
            @endif
        </div>
    </div>
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-2xl bg-secondary-container/40 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-2xl">payments</span>
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Total Harga</p>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#006c49]/10 text-[#006c49]">Semua Data</span>
            </div>
            <p class="text-2xl font-extrabold text-primary font-mono truncate">Rp {{ number_format($totalHarga, 0, ',', '.') }}</p>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium truncate">
                @if(method_exists($movements, 'hasPages') && $movements->hasPages())
                    Halaman ini: Rp {{ number_format($pageTotalHarga, 0, ',', '.') }}
                @else
                    Akumulasi seluruh log transaksi
                @endif
            </p>
        </div>
    </div>
    @if(($movementDate->type ?? 'in') === 'out')
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-2xl bg-error-container/40 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-error text-2xl">local_offer</span>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Total Discount</p>
            <p class="text-2xl font-extrabold text-error font-mono truncate">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</p>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">Potongan harga (Semua Data)</p>
        </div>
    </div>
    @else
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-2xl bg-secondary-container/30 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-secondary text-2xl">inventory_2</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Total Unit Masuk</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ number_format($totalQuantity, 0, ',', '.') }} pcs</p>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">Akumulasi kuantitas masuk</p>
        </div>
    </div>
    @endif
</div>

{{-- ─── Rincian Metode Pembayaran (Metode Bayar Breakdown - Khusus Stock Out) ─ --}}
@if(($movementDate->type ?? 'in') === 'out' && isset($paymentBreakdown))
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 mb-6 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-outline-variant/60">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
            </div>
            <div>
                <h2 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                    <span>Rincian Metode Pembayaran</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#006c49]/10 text-[#006c49]">Semua Halaman</span>
                </h2>
                <p class="text-xs text-on-surface-variant">Breakdown akumulasi penerimaan pembayaran (Transfer, Tunai, QRIS, dsb.)</p>
            </div>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant text-xs">
            <span class="text-on-surface-variant font-medium">Total Terhitung:</span>
            <span class="text-primary font-mono font-extrabold text-sm">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($paymentBreakdown as $key => $info)
        @php
            $hasAmount = ($info['amount'] ?? 0) > 0;
            $colorMap = [
                'emerald' => [
                    'active' => 'border-emerald-300 bg-emerald-50/70',
                    'icon' => 'bg-emerald-100 text-emerald-800',
                    'amount' => 'text-emerald-800',
                ],
                'blue' => [
                    'active' => 'border-blue-300 bg-blue-50/70',
                    'icon' => 'bg-blue-100 text-blue-800',
                    'amount' => 'text-blue-800',
                ],
                'purple' => [
                    'active' => 'border-purple-300 bg-purple-50/70',
                    'icon' => 'bg-purple-100 text-purple-800',
                    'amount' => 'text-purple-800',
                ],
                'cyan' => [
                    'active' => 'border-cyan-300 bg-cyan-50/70',
                    'icon' => 'bg-cyan-100 text-cyan-800',
                    'amount' => 'text-cyan-800',
                ],
                'amber' => [
                    'active' => 'border-amber-300 bg-amber-50/70',
                    'icon' => 'bg-amber-100 text-amber-800',
                    'amount' => 'text-amber-800',
                ],
                'gray' => [
                    'active' => 'border-gray-300 bg-gray-50/70',
                    'icon' => 'bg-gray-100 text-gray-800',
                    'amount' => 'text-gray-800',
                ],
            ];
            $style = $colorMap[$info['color'] ?? 'gray'] ?? $colorMap['gray'];
        @endphp
        <div class="rounded-xl border p-3.5 flex flex-col justify-between transition-all {{ $hasAmount ? $style['active'] . ' shadow-xs ring-1 ring-black/5' : 'border-outline-variant/60 bg-surface-container-low/30 opacity-70' }}">
            <div class="flex items-center justify-between gap-1 mb-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider truncate text-on-surface">{{ $info['label'] }}</span>
                <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0 {{ $hasAmount ? $style['icon'] : 'bg-surface-container-high text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-sm">{{ $info['icon'] ?? 'payments' }}</span>
                </div>
            </div>
            <div>
                <p class="text-sm sm:text-base font-extrabold font-mono leading-tight {{ $hasAmount ? $style['amount'] : 'text-on-surface-variant' }}">
                    Rp {{ number_format($info['amount'] ?? 0, 0, ',', '.') }}
                </p>
                <p class="text-[10px] font-medium text-on-surface-variant mt-1">
                    {{ number_format($info['count'] ?? 0) }} transaksi
                </p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ─── Draft Approval Alert Banner ───────────────────────────────────────────── --}}
@if($allDraftCount > 0)
<div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 border border-amber-200">
            <span class="material-symbols-outlined text-2xl">pending_actions</span>
        </div>
        <div>
            <h3 class="text-sm font-bold text-amber-950 flex items-center gap-2 flex-wrap">
                <span>Terdapat {{ $allDraftCount }} Item Mutasi Berstatus Draft</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900 uppercase tracking-wider">Perlu Persetujuan</span>
            </h3>
            <p class="text-xs text-amber-800 mt-0.5">Item berstatus draft belum menambah stok fisik toko maupun memperbarui HPP. Silakan periksa kembali sebelum melakukan Approve.</p>
        </div>
    </div>
    @if($canApprove)
    <form action="{{ route('movements.bulk-approve') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (Approve) semua {{ $allDraftCount }} item draft pada tanggal ini? Stok produk dan HPP akan langsung diperbarui.');" class="shrink-0 w-full sm:w-auto">
        @csrf
        <input type="hidden" name="movement_date_id" value="{{ $movementDate->id }}">
        <input type="hidden" name="approve_all" value="1">
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap">
            <span class="material-symbols-outlined text-base">verified</span>
            Approve Semua Draft ({{ $allDraftCount }})
        </button>
    </form>
    @endif
</div>
@elseif($allApprovedCount > 0 && ($movementDate->type ?? 'in') === 'in' && $canApprove)
<div class="mb-6 p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 border border-emerald-200">
            <span class="material-symbols-outlined text-2xl">verified</span>
        </div>
        <div>
            <h3 class="text-sm font-bold text-emerald-950 flex items-center gap-2 flex-wrap">
                <span>Seluruh {{ $allApprovedCount }} Item Mutasi Telah Disetujui (Approved)</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-200 text-emerald-900 uppercase tracking-wider">Approved</span>
            </h3>
            <p class="text-xs text-emerald-800 mt-0.5">Stok fisik, HPP produk, dan jurnal transaksi telah tercatat. Jika terdapat kesalahan data import/mutasi, Anda dapat mengembalikannya ke draft.</p>
        </div>
    </div>
    <form action="{{ route('movements.bulk-unapprove') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan semua {{ $allApprovedCount }} item approved pada tanggal ini menjadi DRAFT?\n\n- Stok fisik barang akan dikurangi kembali.\n- HPP produk akan disesuaikan.\n- Seluruh jurnal transaksi terkait akan dihapus dari pembukuan.');" class="shrink-0 w-full sm:w-auto">
        @csrf
        <input type="hidden" name="movement_date_id" value="{{ $movementDate->id }}">
        <input type="hidden" name="unapprove_all" value="1">
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap">
            <span class="material-symbols-outlined text-base">undo</span>
            Kembalikan Semua ke Draft ({{ $allApprovedCount }})
        </button>
    </form>
</div>
@endif

{{-- ─── Filter Bar ──────────────────────────────────────────────────────────── --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl px-5 py-4 mb-6 shadow-sm">
    <form id="filter-form" action="{{ route('movement-dates.show', $movementDate->id) }}" method="GET"
        class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <label class="text-label-sm font-semibold text-on-surface-variant whitespace-nowrap hidden sm:block">FILTER PRODUCT:</label>
            <select name="product_id" id="filter_product_id"
                class="w-full sm:w-64 bg-surface-container-low border border-outline-variant rounded-lg py-1.5 px-3 text-sm focus:outline-none focus:border-primary">
                <option value="">All Products</option>
                @foreach($products as $product)
                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                    {{ $product->name }}{{ !empty($product->variant) ? ' (' . $product->variant . ')' : '' }} ({{ $product->sku }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            @if(request('product_id') || request('type'))
            <a href="{{ route('movement-dates.show', ['id' => $movementDate->id, 'type' => request('type')]) }}"
                class="text-error text-sm font-bold hover:underline">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- ─── Movements Table ─────────────────────────────────────────────────────── --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
    {{-- Bulk Action Bar --}}
    @if($canDeleteDate || $canApprove)
    <div id="movBulkActionBar" class="hidden px-5 py-3.5 bg-[#e8f5e9]/70 border-b border-[#006c49]/20 flex flex-wrap items-center justify-between gap-3 transition-all">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006c49]/15 text-[#006c49]">
                <span class="material-symbols-outlined text-base">check_box</span>
            </span>
            <span id="movSelectedCountBadge" class="text-sm font-bold text-[#006c49]">0 log dipilih</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" id="btnCancelMovSelection" class="px-3.5 py-1.5 rounded-xl border border-outline-variant bg-white hover:bg-surface-container-high text-xs font-semibold text-on-surface-variant transition active:scale-95 shadow-sm cursor-pointer">
                Batal
            </button>
            @if($canApprove)
            <button type="button" id="btnApproveSelectedMov" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-base">verified</span>
                <span>Approve Terpilih</span>
            </button>
            @if(($movementDate->type ?? 'in') === 'in')
            <button type="button" id="btnUnapproveSelectedMov" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer" title="Kembalikan mutasi approved yang dipilih ke status draft">
                <span class="material-symbols-outlined text-base">undo</span>
                <span>Kembalikan ke Draft</span>
            </button>
            @endif
            @endif
            @if($canDeleteDate)
            <button type="button" id="btnDeleteSelectedMov" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-base">delete</span>
                <span id="btnDeleteSelectedMovText">Hapus Log Terpilih</span>
            </button>
            @endif
        </div>
    </div>
    @endif

    @if($movements->count() > 0)
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-surface-container-low text-on-surface-variant text-xs uppercase tracking-wider border-b border-outline-variant">
                <tr>
                    <th class="px-3 py-4 w-12 text-center font-semibold text-on-surface-variant">No.</th>
                    @if($canDeleteDate || $canApprove)
                    <th class="px-4 py-4 w-10 text-center">
                        <input 
                            type="checkbox" 
                            id="selectAllMovItems" 
                            class="w-4 h-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30 cursor-pointer accent-[#006c49]"
                            title="Pilih semua di halaman ini"
                        />
                    </th>
                    @endif
                    <th class="px-5 py-4 font-semibold">Ref No.</th>
                    <th class="px-5 py-4 font-semibold">Item / SKU</th>
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Status</th>
                    <th class="px-5 py-4 font-semibold">Quantity</th>
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Harga Beli</th>
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Harga Jual</th>
                    @if(($movementDate->type ?? 'in') === 'out')
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Discount</th>
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Metode Bayar</th>
                    @endif
                    <th class="px-5 py-4 font-semibold whitespace-nowrap">Total Harga</th>
                    @if($canDeleteDate || $canApprove)
                    <th class="px-5 py-4 font-semibold text-right whitespace-nowrap">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-sm">
                @foreach($movements as $index => $mov)
                @php
                    $isCancelledMov = ($mov->status ?? 'approved') === 'cancelled';
                @endphp
                <tr id="mov-row-{{ $mov->id }}" class="{{ $isCancelledMov ? 'bg-rose-50/40 hover:bg-rose-50/70' : 'hover:bg-surface-container-low/40' }} transition-colors">
                    <td class="px-3 py-4 text-center font-semibold text-xs text-on-surface-variant/80">
                        {{ $index + 1 }}
                    </td>
                    @if($canDeleteDate || $canApprove)
                    <td class="px-4 py-4 text-center">
                        <input 
                            type="checkbox" 
                            name="mov_ids[]" 
                            value="{{ $mov->id }}" 
                            class="mov-checkbox w-4 h-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30 cursor-pointer accent-[#006c49]"
                        />
                    </td>
                    @endif
                    <td class="px-5 py-4 font-mono text-xs font-bold text-on-surface">#{{ $mov->reference_number }}</td>
                    <td class="px-5 py-4">
                        <div class="font-bold text-on-surface flex items-center gap-1.5 flex-wrap">
                            <span class="{{ $isCancelledMov ? 'line-through text-on-surface-variant' : '' }}">{{ $mov->product->name ?? 'Produk Dihapus' }}</span>
                            @if(!empty($mov->product?->variant))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-xs font-semibold">
                                    {{ $mov->product->variant }}
                                </span>
                            @endif
                        </div>
                        <div class="text-on-surface-variant text-xs mt-0.5">SKU: {{ $mov->product->sku ?? '-' }}</div>
                        @if($isCancelledMov)
                            <div class="inline-flex items-center gap-1 mt-1.5 text-[11px] font-bold text-rose-700 bg-rose-100/80 border border-rose-300 px-2 py-0.5 rounded-md" title="{{ $mov->notes }}">
                                <span class="material-symbols-outlined text-[13px]">cancel</span>
                                <span>{{ Str::limit($mov->notes ?: 'Dibatalkan oleh Kasir', 50) }}</span>
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($isCancelledMov)
                            <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider" title="{{ $mov->notes }}">
                                <span class="material-symbols-outlined text-xs font-bold">cancel</span>
                                Dibatalkan
                            </span>
                        @elseif(($mov->status ?? 'approved') === 'draft')
                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-xs font-bold">pending</span>
                                Draft
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-xs font-bold">verified</span>
                                Approved
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 font-extrabold text-on-surface text-base">
                        @if($isCancelledMov)
                            <span class="line-through text-on-surface-variant/50">
                                {{ $mov->type === 'in' ? '+' : '-' }}{{ number_format($mov->quantity) }}
                            </span>
                            <span class="text-[10px] text-rose-600 font-bold ml-1 block">(Kembali)</span>
                        @else
                            <span class="{{ $mov->type === 'in' ? 'text-on-secondary-container' : 'text-on-error-container' }}">
                                {{ $mov->type === 'in' ? '+' : '-' }}{{ number_format($mov->quantity) }}
                            </span>
                            <span class="text-on-surface-variant font-normal text-xs ml-1">units</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs font-medium text-on-surface">
                        @if($mov->purchase_price !== null)
                            <div class="font-bold text-on-surface text-xs">
                                Rp {{ number_format($mov->purchase_price, 0, ',', '.') }}
                            </div>
                            @php
                                $currentProdHpp = (float)($mov->product?->purchase_price ?? 0);
                                $currentProdStock = max(0, (float)($mov->product?->stock ?? 0));
                                $inQty = (float)$mov->quantity;
                                $inPrice = (float)$mov->purchase_price;
                                $hasPriceDiff = (($mov->type ?? $movementDate->type ?? 'in') === 'in' && $currentProdHpp > 0 && abs($inPrice - $currentProdHpp) >= 0.01);
                                if ($hasPriceDiff) {
                                    $simulatedAvg = ($currentProdStock + $inQty > 0)
                                        ? (($currentProdStock * $currentProdHpp) + ($inQty * $inPrice)) / ($currentProdStock + $inQty)
                                        : $inPrice;
                                    $diffNominal = $inPrice - $currentProdHpp;
                                    $isUp = $diffNominal > 0;
                                }
                            @endphp
                            @if($hasPriceDiff)
                                <div class="mt-1 flex flex-col gap-0.5" title="Harga beli berbeda dari HPP sebelumnya (Rp {{ number_format($currentProdHpp, 0, ',', '.') }})">
                                    <div class="flex items-center gap-0.5 text-[10px] {{ $isUp ? 'text-rose-600' : 'text-emerald-700' }} font-bold">
                                        <span class="material-symbols-outlined text-[12px] font-bold">{{ $isUp ? 'arrow_upward' : 'arrow_downward' }}</span>
                                        <span>Sebelumnya: Rp {{ number_format($currentProdHpp, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="inline-flex items-center gap-1 text-[10px] text-primary font-black bg-primary/10 border border-primary/20 px-1.5 py-0.5 rounded shadow-2xs whitespace-nowrap" title="Estimasi rata-rata harga beli (Moving Average HPP)">
                                        <span class="material-symbols-outlined text-[11px]">calculate</span>
                                        <span>Rata²: Rp {{ number_format($simulatedAvg, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs font-medium text-on-surface">
                        {{ $mov->selling_price !== null ? 'Rp ' . number_format($mov->selling_price, 0, ',', '.') : '—' }}
                    </td>
                    @if(($movementDate->type ?? 'in') === 'out')
                    <td class="px-5 py-4 text-xs font-medium">
                        @if($mov->discount !== null && $mov->discount > 0)
                            <span class="text-error font-semibold">-Rp {{ number_format($mov->discount, 0, ',', '.') }}</span>
                        @else
                            <span class="text-on-surface-variant/40">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs font-semibold whitespace-nowrap">
                        @php
                            $pm = strtolower($mov->payment_method ?? 'cash');
                            $pmLabels = [
                                'cash' => ['label' => 'TUNAI', 'bg' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                'qris' => ['label' => 'QRIS', 'bg' => 'bg-purple-50 text-purple-800 border-purple-200'],
                                'transfer' => ['label' => 'TRANSFER', 'bg' => 'bg-blue-50 text-blue-800 border-blue-200'],
                                'debit' => ['label' => 'DEBIT', 'bg' => 'bg-cyan-50 text-cyan-800 border-cyan-200'],
                                'credit' => ['label' => 'CREDIT', 'bg' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                'split' => ['label' => 'SPLIT', 'bg' => 'bg-purple-50 text-purple-800 border-purple-200'],
                                'other' => ['label' => 'LAINNYA', 'bg' => 'bg-gray-100 text-gray-800 border-gray-200'],
                            ];
                            $pmInfo = $pmLabels[$pm] ?? ['label' => strtoupper($mov->payment_method ?? 'TUNAI'), 'bg' => 'bg-gray-100 text-gray-800 border-gray-200'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold border {{ $pmInfo['bg'] }}">
                            {{ $pmInfo['label'] }}
                        </span>
                    </td>
                    @endif
                    <td class="px-5 py-4 text-sm font-extrabold {{ $isCancelledMov ? 'line-through text-on-surface-variant/50' : 'text-primary' }}">
                        {{ $mov->total_price !== null ? 'Rp ' . number_format($mov->total_price, 0, ',', '.') : '—' }}
                    </td>
                    @if($canDeleteDate || $canApprove)
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            @if(($mov->status ?? 'approved') === 'draft' && ($mov->type ?? $movementDate->type ?? 'in') === 'in' && $canApprove)
                            <button type="button" 
                                onclick="openEditDraftModal({{ json_encode([
                                    'id' => $mov->id,
                                    'reference_number' => $mov->reference_number,
                                    'product_name' => $mov->product->name ?? 'Produk',
                                    'product_sku' => $mov->product->sku ?? '-',
                                    'current_stock' => (float)($mov->product->stock ?? 0),
                                    'current_hpp' => (float)($mov->product->purchase_price ?? 0),
                                    'current_selling_price' => (float)($mov->product->selling_price ?? 0),
                                    'quantity' => (int)$mov->quantity,
                                    'purchase_price' => (float)($mov->purchase_price ?? 0),
                                    'selling_price' => (float)($mov->selling_price ?? $mov->product->selling_price ?? 0),
                                ]) }})"
                                class="p-1.5 px-2.5 text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition cursor-pointer inline-flex items-center gap-1 text-xs font-bold shadow-xs" 
                                title="Edit Draft Movement">
                                <span class="material-symbols-outlined text-base">edit</span> Edit
                            </button>
                            @endif

                            @if(($mov->status ?? 'approved') === 'draft' && $canApprove)
                            <form action="{{ route('movements.approve', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (Approve) log mutasi #{{ $mov->reference_number }}? Stok produk {{ $mov->product->name ?? 'ini' }} akan bertambah {{ $mov->quantity }} unit.');" class="inline-block">
                                @csrf
                                <button type="submit" class="p-1.5 px-2.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer inline-flex items-center gap-1 text-xs font-bold shadow-sm" title="Approve Movement">
                                    <span class="material-symbols-outlined text-base">verified</span> Approve
                                </button>
                            </form>
                            @elseif(($mov->status ?? 'approved') === 'approved' && ($movementDate->type ?? 'in') === 'in' && $canApprove)
                            <form action="{{ route('movements.unapprove', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan status mutasi #{{ $mov->reference_number }} menjadi DRAFT?\n\n- Stok produk {{ $mov->product->name ?? 'ini' }} akan dikurangi kembali {{ $mov->quantity }} unit.\n- Jurnal transaksi terkait akan dihapus.');" class="inline-block">
                                @csrf
                                <button type="submit" class="p-1.5 px-2.5 text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition cursor-pointer inline-flex items-center gap-1 text-xs font-bold shadow-xs" title="Kembalikan ke Draft">
                                    <span class="material-symbols-outlined text-base">undo</span> Draft
                                </button>
                            </form>
                            @endif

                            @if($canDeleteDate)
                            <form action="{{ route('movements.destroy', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus log movement #{{ $mov->reference_number }}? {{ ($mov->status ?? 'approved') === 'draft' ? 'Stok tidak akan berubah karena mutasi masih berstatus draft.' : 'Stok produk ' . ($mov->product->name ?? '') . ' akan dikembalikan.' }}');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-error hover:bg-error-container/50 rounded-lg transition border-0 cursor-pointer inline-flex items-center gap-1 text-xs font-bold" title="Hapus Movement Log">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            @php
                $leadingColspan = 7; // no, ref, item, status, qty, beli, jual
                if ($canDeleteDate || $canApprove) $leadingColspan += 1; // checkbox
                if (($movementDate->type ?? 'in') === 'out') $leadingColspan += 2; // discount, metode bayar
                $trailingColspan = 0;
                if ($canDeleteDate || $canApprove) $trailingColspan += 1; // action
            @endphp
            <tfoot class="border-t-2 border-outline-variant bg-surface-container-low font-bold text-xs">
                @if(method_exists($movements, 'hasPages') && $movements->hasPages())
                <tr class="text-on-surface-variant border-b border-outline-variant/60">
                    <td colspan="{{ $leadingColspan }}" class="px-5 py-3 text-right uppercase tracking-wider font-semibold">
                        Subtotal Halaman Ini ({{ $movements->count() }} log):
                    </td>
                    <td class="px-5 py-3 text-primary font-mono text-sm font-extrabold whitespace-nowrap">
                        Rp {{ number_format($pageTotalHarga, 0, ',', '.') }}
                    </td>
                    @if($trailingColspan > 0)
                    <td colspan="{{ $trailingColspan }}"></td>
                    @endif
                </tr>
                @endif
                <tr class="bg-surface-container text-on-surface font-extrabold">
                    <td colspan="{{ $leadingColspan }}" class="px-5 py-3.5 text-right uppercase tracking-wider text-xs font-extrabold text-on-surface">
                        Total Keseluruhan ({{ number_format($totalRecords) }} log &bull; Semua Halaman):
                    </td>
                    <td class="px-5 py-3.5 text-primary font-mono text-base font-extrabold whitespace-nowrap">
                        Rp {{ number_format($totalHarga, 0, ',', '.') }}
                    </td>
                    @if($trailingColspan > 0)
                    <td colspan="{{ $trailingColspan }}"></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Mobile view: Card-based list -->
    <div class="block md:hidden divide-y divide-outline-variant">
        @foreach($movements as $index => $mov)
        @php
            $isCancelledMov = ($mov->status ?? 'approved') === 'cancelled';
        @endphp
        <div id="mov-card-{{ $mov->id }}" class="p-5 flex flex-col gap-3 {{ $isCancelledMov ? 'bg-rose-50/40' : '' }} hover:bg-surface-container-low/40 transition-colors">
            <!-- Top row: Ref No & Type Tag -->
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-surface-container-high text-[11px] font-bold text-on-surface-variant">
                        #{{ $index + 1 }}
                    </span>
                    @if($canDeleteDate)
                    <input 
                        type="checkbox" 
                        name="mov_ids[]" 
                        value="{{ $mov->id }}" 
                        class="mov-checkbox w-4 h-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30 cursor-pointer accent-[#006c49]"
                    />
                    @endif
                    <span class="font-mono text-xs text-on-surface-variant font-bold">#{{ $mov->reference_number }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($isCancelledMov)
                        <span class="inline-flex items-center gap-0.5 bg-rose-100 text-rose-800 border border-rose-300 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase" title="{{ $mov->notes }}">
                            <span class="material-symbols-outlined text-[10px]">cancel</span> Dibatalkan
                        </span>
                    @elseif(($mov->status ?? 'approved') === 'draft')
                        <span class="inline-flex items-center gap-0.5 bg-amber-50 text-amber-800 border border-amber-300 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase">
                            <span class="material-symbols-outlined text-[10px]">pending</span> Draft
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase">
                            <span class="material-symbols-outlined text-[10px]">verified</span> Approved
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 {{ $mov->type === 'in' ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container' }} px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[10px] font-bold">
                            {{ $mov->type === 'in' ? 'south_west' : 'north_east' }}
                        </span>
                        {{ $mov->type === 'in' ? 'Stock In' : 'Stock Out' }}
                    </span>
                </div>
            </div>

            <!-- Item & SKU -->
            <div>
                <div class="font-bold text-on-surface text-sm flex items-center gap-1.5 flex-wrap">
                    <span class="{{ $isCancelledMov ? 'line-through text-on-surface-variant' : '' }}">{{ $mov->product->name ?? 'Produk Dihapus' }}</span>
                    @if(!empty($mov->product?->variant))
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-semibold">
                            {{ $mov->product->variant }}
                        </span>
                    @endif
                </div>
                <div class="text-on-surface-variant text-[11px] mt-0.5 font-semibold">SKU: {{ $mov->product->sku ?? '-' }}</div>
                @if($isCancelledMov)
                    <div class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold text-rose-700 bg-rose-100/80 border border-rose-300 px-2 py-0.5 rounded-md" title="{{ $mov->notes }}">
                        <span class="material-symbols-outlined text-[12px]">cancel</span>
                        <span>{{ Str::limit($mov->notes ?: 'Dibatalkan oleh Kasir', 45) }}</span>
                    </div>
                @endif
            </div>

            <!-- Details grid -->
            <div class="grid grid-cols-2 gap-y-3 gap-x-2 text-xs border-t border-outline-variant/40 pt-3 mt-1">
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Quantity</span>
                    <span class="font-extrabold text-on-surface text-xs">
                        <span class="{{ $mov->type === 'in' ? 'text-on-secondary-container' : 'text-on-error-container' }}">
                            {{ $mov->type === 'in' ? '+' : '-' }}{{ number_format($mov->quantity) }}
                        </span>
                        <span class="text-on-surface-variant font-normal text-[9px] ml-0.5">units</span>
                    </span>
                </div>
                @if(($movementDate->type ?? 'in') === 'in')
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Harga Beli</span>
                    <span class="font-bold text-on-surface text-xs">
                        {{ $mov->purchase_price !== null ? 'Rp ' . number_format($mov->purchase_price, 0, ',', '.') : '—' }}
                    </span>
                    @php
                        $currentProdHppMobile = (float)($mov->product?->purchase_price ?? 0);
                        $currentProdStockMobile = max(0, (float)($mov->product?->stock ?? 0));
                        $inQtyMobile = (float)$mov->quantity;
                        $inPriceMobile = (float)($mov->purchase_price ?? 0);
                        $hasPriceDiffMobile = (($mov->type ?? $movementDate->type ?? 'in') === 'in' && $currentProdHppMobile > 0 && abs($inPriceMobile - $currentProdHppMobile) >= 0.01);
                        if ($hasPriceDiffMobile) {
                            $simAvgMobile = ($currentProdStockMobile + $inQtyMobile > 0)
                                ? (($currentProdStockMobile * $currentProdHppMobile) + ($inQtyMobile * $inPriceMobile)) / ($currentProdStockMobile + $inQtyMobile)
                                : $inPriceMobile;
                            $diffNominalMobile = $inPriceMobile - $currentProdHppMobile;
                            $isUpMobile = $diffNominalMobile > 0;
                        }
                    @endphp
                    @if($hasPriceDiffMobile)
                        <div class="mt-1 flex flex-col gap-0.5">
                            <span class="text-[9px] {{ $isUpMobile ? 'text-rose-600' : 'text-emerald-700' }} font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[11px] font-bold">{{ $isUpMobile ? 'arrow_upward' : 'arrow_downward' }}</span>
                                Prev: Rp {{ number_format($currentProdHppMobile, 0, ',', '.') }}
                            </span>
                            <span class="text-[9px] text-primary font-black bg-primary/10 border border-primary/20 px-1 py-0.5 rounded inline-block">
                                Rata²: Rp {{ number_format($simAvgMobile, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Harga Jual</span>
                    <span class="font-bold text-on-surface text-xs">
                        {{ $mov->selling_price !== null ? 'Rp ' . number_format($mov->selling_price, 0, ',', '.') : '—' }}
                    </span>
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Supplier & Metode</span>
                    <div class="font-bold text-xs text-on-surface truncate">{{ $mov->supplier_name ?: '—' }}</div>
                    <div class="text-[10px] text-on-surface-variant font-semibold uppercase mt-0.5">{{ $mov->payment_method ?? 'TUNAI' }}</div>
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Total Harga</span>
                    <span class="font-extrabold text-primary text-xs">
                        {{ $mov->total_price !== null ? 'Rp ' . number_format($mov->total_price, 0, ',', '.') : '—' }}
                    </span>
                </div>
                @else
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Harga Jual</span>
                    <span class="font-medium text-on-surface text-xs">
                        {{ $mov->selling_price !== null ? 'Rp ' . number_format($mov->selling_price, 0, ',', '.') : '—' }}
                    </span>
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Discount</span>
                    <span class="font-medium text-xs">
                        @if($mov->discount !== null && $mov->discount > 0)
                            <span class="text-error font-semibold">-Rp {{ number_format($mov->discount, 0, ',', '.') }}</span>
                        @else
                            <span class="text-on-surface-variant/40">—</span>
                        @endif
                    </span>
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Metode Bayar</span>
                    <span class="font-bold text-xs uppercase text-on-surface">
                        {{ $mov->payment_method ?? 'CASH' }}
                    </span>
                </div>
                <div>
                    <span class="text-on-surface-variant text-[9px] font-bold uppercase tracking-wider block mb-0.5">Total Harga</span>
                    <span class="font-extrabold text-primary text-xs">
                        {{ $mov->total_price !== null ? 'Rp ' . number_format($mov->total_price, 0, ',', '.') : '—' }}
                    </span>
                </div>
                @endif
                <div class="col-span-2 flex items-center justify-between border-t border-outline-variant/20 pt-2.5 mt-1 text-on-surface-variant text-[10px]">
                    <span class="flex items-center gap-1.5 font-bold">
                        <span class="w-5 h-5 rounded-full bg-primary text-on-primary text-[8px] font-extrabold flex items-center justify-center uppercase shrink-0 shadow-sm">
                            {{ substr($mov->user?->name ?? 'S', 0, 1) }}
                        </span>
                        <span class="text-on-surface font-semibold">{{ $mov->user?->name ?? 'System' }}</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-semibold text-on-surface-variant flex items-center gap-1 mr-1">
                            <span class="material-symbols-outlined text-xs opacity-75">schedule</span>
                            {{ $mov->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y, H:i') }} WIB
                        </span>
                        @if(($mov->status ?? 'approved') === 'draft' && ($mov->type ?? $movementDate->type ?? 'in') === 'in' && $canApprove)
                        <button type="button" 
                            onclick="openEditDraftModal({{ json_encode([
                                'id' => $mov->id,
                                'reference_number' => $mov->reference_number,
                                'product_name' => $mov->product->name ?? 'Produk',
                                'product_sku' => $mov->product->sku ?? '-',
                                'current_stock' => (float)($mov->product->stock ?? 0),
                                'current_hpp' => (float)($mov->product->purchase_price ?? 0),
                                'current_selling_price' => (float)($mov->product->selling_price ?? 0),
                                'quantity' => (int)$mov->quantity,
                                'purchase_price' => (float)($mov->purchase_price ?? 0),
                                'selling_price' => (float)($mov->selling_price ?? $mov->product->selling_price ?? 0),
                            ]) }})"
                            class="p-1 px-2 text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition cursor-pointer flex items-center gap-0.5 text-[11px] font-bold shadow-xs" 
                            title="Edit Draft Movement">
                            <span class="material-symbols-outlined text-xs">edit</span> Edit
                        </button>
                        @endif
                        @if(($mov->status ?? 'approved') === 'draft' && $canApprove)
                        <form action="{{ route('movements.approve', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (Approve) log mutasi #{{ $mov->reference_number }}? Stok produk {{ $mov->product->name ?? 'ini' }} akan bertambah {{ $mov->quantity }} unit.');" class="inline-block">
                            @csrf
                            <button type="submit" class="p-1 px-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer flex items-center gap-0.5 text-[11px] font-bold shadow-sm" title="Approve Movement Log">
                                <span class="material-symbols-outlined text-xs">verified</span> Approve
                            </button>
                        </form>
                        @elseif(($mov->status ?? 'approved') === 'approved' && ($movementDate->type ?? 'in') === 'in' && $canApprove)
                        <form action="{{ route('movements.unapprove', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan status mutasi #{{ $mov->reference_number }} menjadi DRAFT?\n\n- Stok produk {{ $mov->product->name ?? 'ini' }} akan dikurangi kembali {{ $mov->quantity }} unit.\n- Jurnal transaksi terkait akan dihapus.');" class="inline-block">
                            @csrf
                            <button type="submit" class="p-1 px-2 text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition cursor-pointer flex items-center gap-0.5 text-[11px] font-bold shadow-xs" title="Kembalikan ke Draft">
                                <span class="material-symbols-outlined text-xs">undo</span> Draft
                            </button>
                        </form>
                        @endif
                        @if($canDeleteDate)
                        <form action="{{ route('movements.destroy', $mov->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus log movement #{{ $mov->reference_number }}? {{ ($mov->status ?? 'approved') === 'draft' ? 'Stok tidak akan berubah karena mutasi masih berstatus draft.' : 'Stok produk ' . ($mov->product->name ?? '') . ' akan dikembalikan.' }}');" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-error hover:bg-error-container/50 rounded-lg transition border-0 cursor-pointer flex items-center gap-0.5 text-[11px] font-bold" title="Hapus Movement Log">
                                <span class="material-symbols-outlined text-xs">delete</span> Hapus
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if(method_exists($movements, 'hasPages') && $movements->hasPages())
    <div class="px-5 py-4 border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-container-low/50">
        <span class="text-on-surface-variant text-sm font-semibold text-center sm:text-left">
            Showing {{ $movements->firstItem() }} – {{ $movements->lastItem() }} of {{ $movements->total() }} records
        </span>
        <div class="flex gap-2 w-full sm:w-auto justify-center">
            @if($movements->onFirstPage())
            <button disabled class="px-4 py-1.5 border border-outline-variant rounded-lg text-on-surface-variant text-xs font-bold cursor-not-allowed">← Prev</button>
            @else
            <a href="{{ $movements->previousPageUrl() }}" class="px-4 py-1.5 border border-outline-variant rounded-lg text-on-surface text-xs font-bold hover:bg-surface-container-low transition">← Prev</a>
            @endif
            @if($movements->hasMorePages())
            <a href="{{ $movements->nextPageUrl() }}" class="px-4 py-1.5 border border-outline-variant rounded-lg text-on-surface text-xs font-bold hover:bg-surface-container-low transition">Next →</a>
            @else
            <button disabled class="px-4 py-1.5 border border-outline-variant rounded-lg text-on-surface-variant text-xs font-bold cursor-not-allowed">Next →</button>
            @endif
        </div>
    </div>
    @endif

    @else
    {{-- Empty state --}}
    <div class="py-16 text-center">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-4 block">inventory_2</span>
        <p class="font-bold text-on-surface-variant text-sm">Belum ada movement untuk tanggal ini.</p>
        <p class="text-on-surface-variant text-xs mt-1 mb-5">Klik tombol <strong>Log Movement</strong> untuk mencatat transaksi.</p>
        @can('Stock In Store')
        <button onclick="document.getElementById('modal-log-movement').showModal()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-sm hover:opacity-90 transition border-0 cursor-pointer">
            <span class="material-symbols-outlined text-base">add</span>
            Log Movement Sekarang
        </button>
        @endcan
    </div>
    @endif
</div>


{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL: Log Movement untuk tanggal ini
═══════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-log-movement"
    class="backdrop:bg-black/60 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-2xl sm:max-w-3xl bg-white overflow-hidden max-h-[92vh] m-auto my-auto">
    <form id="log-movement-form" action="{{ route('movements.store') }}" method="POST" class="flex flex-col max-h-[92vh] h-full m-0">
        @csrf
        <input type="hidden" name="movement_date_id" value="{{ $movementDate->id }}" />
        <input type="hidden" name="movement_date" value="{{ \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d') }}" />
        <input type="hidden" name="type" value="{{ $movementDate->type ?? 'in' }}" />

        {{-- Sticky Header --}}
        <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between shrink-0">
            <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">add_box</span>
                <span>Log Movement — <strong class="text-primary">{{ $dateLabel }}</strong></span>
            </h3>
            <button type="button" onclick="document.getElementById('modal-log-movement').close()"
                class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Scrollable Body --}}
        <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1 overscroll-contain">
            @if(($movementDate->type ?? 'in') === 'in')
                {{-- Automated Session Inputs (Hidden) --}}
                <input type="hidden" name="user_id" value="{{ auth()->id() }}" />
                <input type="hidden" name="payment_method" value="{{ $movementDate->payment_method ?? 'cash' }}" />
                <input type="hidden" name="supplier_name" value="{{ $movementDate->supplier_name }}" />
                @if($movementDate->due_date)
                <input type="hidden" name="due_date" value="{{ $movementDate->due_date->format('Y-m-d') }}" />
                @endif

                {{-- Hidden Container for dynamically generated item inputs --}}
                <div id="queue_hidden_inputs"></div>

                {{-- ── 1. Input Box: Pilih Produk, Qty, Harga Beli & Tombol Add ── --}}
                <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4 flex flex-col gap-3.5 shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="builder_product_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                PILIH PRODUK <span class="text-error font-bold">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span id="builder_stock_badge" class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 hidden"></span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    Bisa pilih banyak produk sekaligus
                                </span>
                            </div>
                        </div>
                        <select id="builder_product_id" multiple placeholder="Cari & pilih produk (bisa multiple)...">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" 
                                    data-name="{{ $product->name }}"
                                    data-variant="{{ $product->variant }}"
                                    data-sku="{{ $product->sku }}"
                                    data-stock="{{ $product->stock }}"
                                    data-purchase="{{ $product->purchase_price }}" 
                                    data-selling="{{ $product->selling_price }}">
                                {{ $product->name }}{{ !empty($product->variant) ? ' - ' . $product->variant : '' }} ({{ $product->sku }}) • Beli: Rp {{ number_format($product->purchase_price, 0, ',', '.') }} • Stok: {{ number_format($product->stock) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        {{-- Quantity --}}
                        <div class="sm:col-span-4 flex flex-col gap-1">
                            <label for="builder_qty" class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                                JUMLAH MASUK (QTY) <span class="text-error font-bold">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="number" min="1" id="builder_qty" value="1"
                                       class="w-full px-3.5 py-2.5 bg-white border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition"
                                       placeholder="1" />
                                <span class="absolute right-3.5 text-xs font-bold text-on-surface-variant/60 pointer-events-none">pcs</span>
                            </div>
                        </div>

                        {{-- Harga Beli --}}
                        <div class="sm:col-span-5 flex flex-col gap-1">
                            <label for="builder_purchase_price" class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                                HARGA BELI / UNIT <span class="text-error font-bold">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3.5 text-xs font-bold text-on-surface-variant/70 pointer-events-none">Rp</span>
                                <input type="number" min="0" step="0.01" id="builder_purchase_price"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-outline-variant rounded-xl text-sm font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition"
                                       placeholder="Contoh: 85000" />
                            </div>
                        </div>

                        {{-- Tombol Add --}}
                        <div class="sm:col-span-3">
                            <button type="button" id="btn_add_to_queue" onclick="addItemToQueue()"
                                    class="w-full py-2.5 px-4 bg-primary text-on-primary hover:opacity-90 active:scale-95 transition-all rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 shadow-xs cursor-pointer border-0 h-[42px]">
                                <span class="material-symbols-outlined text-lg">add_circle</span>
                                <span>Tambah</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── 2. Tabel Antrian Barang yang Ditambahkan ── --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-primary">format_list_bulleted</span>
                            <span>Daftar Barang yang Akan Dimasukkan</span>
                        </span>
                        <span id="queue_badge_count" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">
                            0 Produk
                        </span>
                    </div>

                    <div class="border border-outline-variant rounded-xl overflow-hidden bg-white shadow-xs">
                        <div class="max-h-60 overflow-y-auto overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                                        <th class="py-2.5 px-3">Produk & SKU</th>
                                        <th class="py-2.5 px-3 text-center w-28">Qty</th>
                                        <th class="py-2.5 px-3 text-right w-36">Harga Beli</th>
                                        <th class="py-2.5 px-3 text-right w-32">Subtotal</th>
                                        <th class="py-2.5 px-3 w-12 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="queue_table_body" class="divide-y divide-outline-variant">
                                    {{-- Dynamically populated via JS --}}
                                </tbody>
                            </table>

                            {{-- Empty State --}}
                            <div id="queue_empty_state" class="py-8 px-4 text-center text-on-surface-variant flex flex-col items-center justify-center">
                                <div class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center mb-2 text-on-surface-variant/60">
                                    <span class="material-symbols-outlined text-2xl">playlist_add</span>
                                </div>
                                <p class="text-xs font-semibold text-on-surface">Daftar masih kosong</p>
                                <p class="text-[11px] text-on-surface-variant/70 mt-0.5">Pilih produk dan tentukan jumlah di atas, lalu klik <strong>"Tambah"</strong>.</p>
                            </div>
                        </div>

                        {{-- Summary Footer --}}
                        <div id="queue_summary_footer" class="hidden px-4 py-2.5 bg-surface-container-low border-t border-outline-variant flex flex-wrap items-center justify-between text-xs">
                            <div class="text-on-surface-variant font-medium">
                                Total: <strong id="queue_total_qty_label" class="text-on-surface">0 pcs</strong> (<span id="queue_total_types_label">0</span> produk)
                            </div>
                            <div class="text-on-surface-variant font-medium">
                                Estimasi Nilai: <strong id="queue_total_amount_label" class="text-primary font-bold text-sm font-mono">Rp 0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Stock Out (Single Item Entry) --}}
                <div class="space-y-4">
                    <div>
                        <label for="log_product_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Pilih Produk <span class="text-error">*</span>
                        </label>
                        <select name="product_id" id="log_product_id" required
                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" 
                                    data-stock="{{ $product->stock }}"
                                    data-purchase="{{ $product->purchase_price }}" 
                                    data-selling="{{ $product->selling_price }}">
                                {{ $product->name }}{{ !empty($product->variant) ? ' - ' . $product->variant : '' }} ({{ $product->sku }}) • Stok: {{ number_format($product->stock) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="log_quantity" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                                Quantity <span class="text-error">*</span>
                            </label>
                            <input type="number" name="quantity" id="log_quantity" min="1" value="1" required
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="log_payment_method" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                                Metode Pembayaran <span class="text-error">*</span>
                            </label>
                            <select name="payment_method" id="log_payment_method" required
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition">
                                <option value="cash" selected>Tunai (Cash)</option>
                                <option value="qris">QRIS</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="debit">Kartu Debit</option>
                                <option value="credit">Kartu Kredit</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="log_discount" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">Discount</label>
                            <input type="number" name="discount" id="log_discount" min="0" step="0.01" value="0" oninput="calculateTotal()"
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="log_total_price" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">Total Harga</label>
                            <input type="number" name="total_price" id="log_total_price" step="0.01" readonly
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold text-primary focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition bg-gray-50 font-mono" />
                        </div>

                        <input type="hidden" name="purchase_price" id="log_purchase_price" value="0" />
                        <input type="hidden" name="selling_price" id="log_selling_price" value="0" />

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="log_user_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">Created By <span class="text-error">*</span></label>
                            <select name="user_id" id="log_user_id" required
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition">
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->roles->first()?->name ?? 'No Role' }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="log_notes" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">Notes</label>
                            <textarea name="notes" id="log_notes" rows="2"
                                placeholder="Keterangan tambahan..."
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition resize-none"></textarea>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sticky Footer with Action Buttons --}}
        <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest flex gap-3 shrink-0 shadow-sm">
            <button type="button"
                onclick="document.getElementById('modal-log-movement').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit" id="btn_submit_movement"
                class="flex-1 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-bold hover:opacity-90 active:scale-95 transition border-0 cursor-pointer shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                @if(($movementDate->type ?? 'in') === 'in')
                    Simpan Movement (<span id="btn_save_item_count">0</span> Item)
                @else
                    Simpan Movement
                @endif
            </button>
        </div>
    </form>
</dialog>

@if(($movementDate->type ?? 'in') === 'in')
{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Edit Pengaturan Sesi (Supplier, Payment Method, Due Date, Notes)
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-edit-session-info"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-md bg-white overflow-visible">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">edit_calendar</span>
            <span>Ubah Pengaturan Sesi</span>
        </h3>
        <button onclick="document.getElementById('modal-edit-session-info').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <form action="{{ route('movement-dates.update', $movementDate->id) }}" method="POST" class="px-6 py-5 space-y-4">
        @csrf
        @method('PATCH')

        {{-- Supplier Name --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="edit_supplier_name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-primary">storefront</span>
                    <span>Nama Supplier / Distributor</span>
                </label>
                <span class="text-[10px] font-semibold text-primary bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                    Pilih / Ketik Baru
                </span>
            </div>
            <select name="supplier_name" id="edit_supplier_name" class="w-full">
                <option value="">-- Pilih atau Ketik Nama Supplier Baru --</option>
                @if(isset($suppliers))
                    @foreach($suppliers as $sName)
                        <option value="{{ $sName }}" {{ old('supplier_name', $movementDate->supplier_name) === $sName ? 'selected' : '' }}>{{ $sName }}</option>
                    @endforeach
                @endif
                @if($movementDate->supplier_name && (!isset($suppliers) || !$suppliers->contains($movementDate->supplier_name)))
                    <option value="{{ $movementDate->supplier_name }}" selected>{{ $movementDate->supplier_name }}</option>
                @endif
            </select>
            <p class="text-[11px] text-on-surface-variant/70 mt-1">Mengubah nilai default untuk sesi ini (otomatis tersimpan ke Pengaturan Sistem).</p>
        </div>

        {{-- Payment Method --}}
        <div>
            <label for="edit_payment_method" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-primary">payments</span>
                <span>Metode Pembayaran Sesi</span>
            </label>
            <select name="payment_method" id="edit_payment_method" onchange="toggleEditSessionTempo(this.value)"
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition cursor-pointer">
                <option value="cash" {{ ($movementDate->payment_method ?? 'cash') === 'cash' ? 'selected' : '' }}>Tunai / Kas Toko (Langsung Lunas)</option>
                <option value="transfer" {{ ($movementDate->payment_method ?? 'cash') === 'transfer' ? 'selected' : '' }}>Transfer Bank (Langsung Lunas)</option>
                <option value="tempo" {{ in_array($movementDate->payment_method ?? '', ['tempo', 'hutang', 'credit']) ? 'selected' : '' }}>Tempo / Hutang Supplier (Bayar Nanti / AP)</option>
            </select>
        </div>

        {{-- Due Date (Khusus Tempo) --}}
        <div id="edit_session_due_date_container" class="{{ in_array($movementDate->payment_method ?? '', ['tempo', 'hutang', 'credit']) ? '' : 'hidden' }}">
            <div class="flex items-center justify-between mb-1.5">
                <label for="edit_due_date" class="block text-xs font-bold text-amber-950 uppercase tracking-wide flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-amber-700">event_busy</span>
                    <span>Tanggal Jatuh Tempo <span class="text-error font-bold">*</span></span>
                </label>
                <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">
                    Hutang Dagang (AP)
                </span>
            </div>
            <input type="date" name="due_date" id="edit_due_date"
                value="{{ old('due_date', $movementDate->due_date ? $movementDate->due_date->format('Y-m-d') : date('Y-m-d', strtotime('+30 days'))) }}"
                class="w-full px-4 py-2.5 bg-white border border-amber-300 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 transition" />
            <p class="text-[11px] text-amber-900/80 mt-1">Tagihan sesi ini tercatat di Buku Hutang Supplier.</p>
        </div>

        {{-- Notes --}}
        <div>
            <label for="edit_notes" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Notes <span class="text-on-surface-variant font-normal">(opsional)</span>
            </label>
            <textarea name="notes" id="edit_notes" rows="2"
                placeholder="Keterangan opsional..."
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition resize-none">{{ old('notes', $movementDate->notes) }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-1">
            <button type="button"
                onclick="document.getElementById('modal-edit-session-info').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-primary text-on-primary hover:opacity-90 rounded-xl text-sm font-bold active:scale-95 transition shadow-sm border-0 cursor-pointer">
                Simpan Perubahan
            </button>
        </div>
    </form>
</dialog>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Import Produk Stock In via Excel
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-import-stock-in"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-lg bg-white overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">upload_file</span>
            Import Produk Stock In
        </h3>
        <button onclick="document.getElementById('modal-import-stock-in').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Download Template Banner --}}
    <div class="p-6 pb-2">
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">table_view</span>
                <div>
                    <h4 class="text-xs font-bold text-emerald-950 uppercase tracking-wide">Template Excel Stock In</h4>
                    <p class="text-xs text-emerald-800 mt-0.5">Gunakan format resmi agar data SKU, jumlah, dan harga terbaca sempurna.</p>
                </div>
            </div>
            <a href="{{ route('movement-dates.import-template') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm active:scale-95 no-underline shrink-0">
                <span class="material-symbols-outlined text-sm">download</span>
                <span>Unduh Template</span>
            </a>
        </div>
    </div>

    {{-- Column Format Guide --}}
    <div class="px-6 py-2">
        <div class="text-[11px] bg-surface-container-low rounded-xl p-3 border border-outline-variant text-on-surface-variant space-y-1">
            <div class="font-bold text-on-surface flex items-center gap-1 text-xs mb-1.5">
                <span class="material-symbols-outlined text-sm text-primary">info</span>
                Petunjuk Format Kolom:
            </div>
            <div>• <strong class="text-on-surface">sku</strong> <span class="text-rose-600 font-bold">*Wajib</span>: Kode SKU produk di inventory.</div>
            <div>• <strong class="text-on-surface">product_name</strong>: Nama produk (opsional jika SKU sudah ada; wajib jika produk baru).</div>
            <div>• <strong class="text-on-surface">variant</strong>: Varian produk (opsional, cth: 250ml, 500ml, Size L, atau kosongkan jika tidak ada varian).</div>
            <div>• <strong class="text-on-surface">quantity</strong> <span class="text-rose-600 font-bold">*Wajib</span>: Jumlah barang masuk (angka bulat &gt; 0).</div>
            <div>• <strong class="text-on-surface">purchase_price</strong>: Harga modal/beli per unit (opsional, jika kosong pakai HPP saat ini).</div>
            <div>• <strong class="text-on-surface">selling_price</strong>: Harga jual baru per unit (opsional).</div>
            <div>• <strong class="text-on-surface">notes</strong>: Catatan transaksi per item (opsional, cth: nama supplier / batch).</div>
        </div>
    </div>

    <form action="{{ route('movement-dates.import', $movementDate->id) }}" method="POST" enctype="multipart/form-data" class="px-6 py-4 space-y-4">
        @csrf

        {{-- File Upload --}}
        <div>
            <label for="import_stock_file" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Pilih File Excel / CSV <span class="text-error">*</span>
            </label>
            <input type="file" name="file" id="import_stock_file" accept=".xlsx,.xls,.csv" required
                class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-600 transition file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer" />
            <p class="text-[11px] text-on-surface-variant mt-1 opacity-70">Mendukung file .xlsx, .xls, atau .csv (maksimal 10 MB).</p>
        </div>

        {{-- Created By --}}
        <div>
            <label for="import_user_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">Dicatat Oleh</label>
            <select name="user_id" id="import_user_id"
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-600 transition">
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->roles->first()?->name ?? 'Staff' }})
                </option>
                @endforeach
            </select>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-3 border-t border-outline-variant">
            <button type="button"
                onclick="document.getElementById('modal-import-stock-in').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold active:scale-95 transition border-0 cursor-pointer flex items-center justify-center gap-1.5 shadow">
                <span class="material-symbols-outlined text-base">file_upload</span>
                Mulai Import
            </button>
        </div>
    </form>
</dialog>
@endif

{{-- ════════════════════════════════════════════════════════════════════════════
     MODAL: EDIT DRAFT STOCK IN ITEM
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-edit-draft-movement"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-lg bg-white overflow-visible">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">edit_note</span>
            <span>Edit Item Stock In (Draft)</span>
        </h3>
        <button type="button" onclick="document.getElementById('modal-edit-draft-movement').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <form id="form-edit-draft-movement" action="" method="POST" class="px-6 py-5 space-y-4">
        @csrf
        @method('PUT')

        {{-- Product Info Card --}}
        <div class="p-3.5 bg-surface-container-low rounded-xl border border-outline-variant/60 flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">inventory_2</span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span id="modal-edit-ref" class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-surface-container border border-outline-variant text-on-surface-variant">#REF</span>
                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-300 uppercase">DRAFT</span>
                </div>
                <h4 id="modal-edit-product-name" class="font-bold text-sm text-on-surface truncate mt-1">Nama Produk</h4>
                <div class="flex items-center gap-3 text-xs text-on-surface-variant mt-1 flex-wrap">
                    <span>SKU: <strong id="modal-edit-product-sku" class="font-mono text-on-surface">-</strong></span>
                    <span>•</span>
                    <span>Stok Fisik Saat Ini: <strong id="modal-edit-product-stock" class="text-on-surface">0</strong> unit</span>
                </div>
            </div>
        </div>

        {{-- Grid for Inputs: Qty, Harga Beli, Harga Jual --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Qty --}}
            <div>
                <label for="edit_draft_qty" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Jumlah Qty <span class="text-error">*</span>
                </label>
                <input type="number" name="quantity" id="edit_draft_qty" min="1" required
                    class="w-full px-3 py-2 bg-white border border-outline-variant rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition"
                    oninput="calculateDraftMovingAverage()" />
                <span class="text-[10px] text-on-surface-variant mt-1 block">Unit fisik masuk</span>
            </div>

            {{-- Harga Beli Satuan --}}
            <div>
                <label for="edit_draft_purchase_price" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Harga Beli <span class="text-error">*</span>
                </label>
                <input type="number" step="any" name="purchase_price" id="edit_draft_purchase_price" min="0" required
                    class="w-full px-3 py-2 bg-white border border-outline-variant rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition"
                    oninput="calculateDraftMovingAverage()" />
                <span class="text-[10px] text-on-surface-variant mt-1 block">Modal per unit (Rp)</span>
            </div>

            {{-- Harga Jual Satuan --}}
            <div>
                <label for="edit_draft_selling_price" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Harga Jual <span class="text-error">*</span>
                </label>
                <input type="number" step="any" name="selling_price" id="edit_draft_selling_price" min="0" required
                    class="w-full px-3 py-2 bg-white border border-outline-variant rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition"
                    oninput="calculateDraftMovingAverage()" />
                <span class="text-[10px] text-on-surface-variant mt-1 block">Harga jual ecer (Rp)</span>
            </div>
        </div>

        {{-- Live Calculation Box: Moving Average & Price Comparison --}}
        <div id="draft-moving-average-box" class="p-3.5 rounded-xl border transition-all duration-200">
            <!-- Dynamically populated by calculateDraftMovingAverage() -->
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-2">
            <button type="button"
                onclick="document.getElementById('modal-edit-draft-movement').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-bold hover:bg-primary/90 transition shadow-sm cursor-pointer border-0 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">save</span>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</dialog>

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
    const isStockIn = {{ ($movementDate->type ?? 'in') === 'in' ? 'true' : 'false' }};
    let logTomSelect = null;
    let builderTomSelect = null;
    let itemQueue = [];

    function formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num || 0);
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

    // ─── EDIT DRAFT STOCK IN FUNCTIONS ─────────────────────────────────────────────
    let currentEditProduct = {
        id: null,
        stock: 0,
        hpp: 0,
        selling_price: 0
    };

    function openEditDraftModal(data) {
        currentEditProduct = {
            id: data.id,
            stock: parseFloat(data.current_stock) || 0,
            hpp: parseFloat(data.current_hpp) || 0,
            selling_price: parseFloat(data.current_selling_price) || 0
        };

        document.getElementById('modal-edit-ref').textContent = '#' + data.reference_number;
        document.getElementById('modal-edit-product-name').textContent = data.product_name;
        document.getElementById('modal-edit-product-sku').textContent = data.product_sku;
        document.getElementById('modal-edit-product-stock').textContent = currentEditProduct.stock.toLocaleString('id-ID');

        document.getElementById('edit_draft_qty').value = data.quantity;
        document.getElementById('edit_draft_purchase_price').value = data.purchase_price;
        document.getElementById('edit_draft_selling_price').value = data.selling_price;

        const form = document.getElementById('form-edit-draft-movement');
        form.action = '/movements/' + data.id;

        calculateDraftMovingAverage();
        document.getElementById('modal-edit-draft-movement').showModal();
    }

    function calculateDraftMovingAverage() {
        const qtyInput = parseFloat(document.getElementById('edit_draft_qty').value) || 0;
        const priceInput = parseFloat(document.getElementById('edit_draft_purchase_price').value) || 0;
        const sellingInput = parseFloat(document.getElementById('edit_draft_selling_price').value) || 0;

        const currentStock = Math.max(0, currentEditProduct.stock);
        const prevHpp = currentEditProduct.hpp;
        const box = document.getElementById('draft-moving-average-box');

        const totalIncoming = qtyInput * priceInput;
        let newMovingAverage = priceInput;

        if (currentStock <= 0) {
            newMovingAverage = priceInput;
        } else {
            const totalCurrentVal = currentStock * prevHpp;
            const totalIncomingVal = qtyInput * priceInput;
            const totalQty = currentStock + qtyInput;
            newMovingAverage = totalQty > 0 ? (totalCurrentVal + totalIncomingVal) / totalQty : priceInput;
        }

        const priceDiff = priceInput - prevHpp;
        const hasPriceChange = prevHpp > 0 && Math.abs(priceDiff) >= 0.01;

        let html = `
            <div class="flex items-center justify-between text-xs pb-2 border-b border-outline-variant/40">
                <span class="text-on-surface-variant font-semibold">Total Nilai Pembelian (Qty × Harga Beli):</span>
                <span class="font-extrabold text-primary text-sm">Rp ${formatRupiah(totalIncoming)}</span>
            </div>
        `;

        if (hasPriceChange) {
            const isUp = priceDiff > 0;
            const percentChange = prevHpp > 0 ? Math.abs((priceDiff / prevHpp) * 100).toFixed(1) : 0;
            const badgeColor = isUp ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
            const iconName = isUp ? 'trending_up' : 'trending_down';

            box.className = `p-3.5 rounded-xl border ${isUp ? 'bg-amber-50/70 border-amber-200' : 'bg-emerald-50/70 border-emerald-200'} transition-all duration-200`;

            html += `
                <div class="mt-2.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold ${isUp ? 'text-amber-900' : 'text-emerald-900'} flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">${iconName}</span>
                            Perubahan Harga Beli Terdeteksi:
                        </span>
                        <span class="text-[11px] font-extrabold px-2 py-0.5 rounded border ${badgeColor}">
                            ${isUp ? '+' : '-'}Rp ${formatRupiah(Math.abs(priceDiff))} (${percentChange}%)
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div class="bg-white/90 p-2 rounded-lg border border-outline-variant/40">
                            <span class="text-[10px] text-on-surface-variant block uppercase font-bold">Harga Beli Sebelumnya</span>
                            <span class="font-bold text-on-surface text-xs">Rp ${formatRupiah(prevHpp)}</span>
                        </div>
                        <div class="bg-white/90 p-2 rounded-lg border border-primary/30">
                            <span class="text-[10px] text-primary block uppercase font-black">Rata² Harga Beli (HPP)</span>
                            <span class="font-black text-primary text-xs">Rp ${formatRupiah(newMovingAverage)}</span>
                        </div>
                    </div>

                    <p class="text-[10px] text-on-surface-variant/80 mt-1 italic leading-tight">
                        *Estimasi Moving Average: (Stok ${currentStock} × Rp ${formatRupiah(prevHpp)} + Masuk ${qtyInput} × Rp ${formatRupiah(priceInput)}) ÷ ${currentStock + qtyInput} unit.
                    </p>
                </div>
            `;
        } else {
            box.className = "p-3.5 rounded-xl border bg-surface-container-low border-outline-variant/60 transition-all duration-200";
            html += `
                <div class="mt-2 flex items-center justify-between text-xs text-on-surface-variant">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
                        Harga beli sama dengan HPP sebelumnya (Rp ${formatRupiah(prevHpp || priceInput)})
                    </span>
                    <span class="font-bold text-on-surface">Rata² tetap: Rp ${formatRupiah(prevHpp || priceInput)}</span>
                </div>
            `;
        }

        if (sellingInput > 0) {
            const margin = sellingInput - newMovingAverage;
            const marginPercent = newMovingAverage > 0 ? ((margin / newMovingAverage) * 100).toFixed(1) : 0;
            const marginClass = margin >= 0 ? 'text-emerald-700' : 'text-rose-600';
            html += `
                <div class="mt-2 pt-2 border-t border-outline-variant/40 flex items-center justify-between text-xs">
                    <span class="text-on-surface-variant">Estimasi Margin vs Rata² HPP:</span>
                    <span class="font-extrabold ${marginClass}">
                        Rp ${formatRupiah(margin)} / unit (${marginPercent}%)
                    </span>
                </div>
            `;
        }

        box.innerHTML = html;
    }

    // ─── STOCK IN QUEUE BUILDER FUNCTIONS ──────────────────────────────────────────
    function addItemToQueue() {
        const selectElem = document.getElementById('builder_product_id');
        const rawVals = builderTomSelect ? builderTomSelect.getValue() : (selectElem ? selectElem.value : null);
        const valArray = Array.isArray(rawVals) ? rawVals : (rawVals ? [rawVals] : []);

        if (valArray.length === 0) {
            alert('Silakan pilih minimal satu produk terlebih dahulu sebelum menambahkan ke antrian.');
            if (builderTomSelect) builderTomSelect.focus();
            return;
        }

        const qtyInput = document.getElementById('builder_qty');
        const priceInput = document.getElementById('builder_purchase_price');
        const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
        if (qty <= 0) {
            alert('Jumlah masuk (Qty) minimal 1 pcs.');
            if (qtyInput) qtyInput.focus();
            return;
        }

        const customPriceRaw = priceInput && priceInput.value !== '' ? parseFloat(priceInput.value) : null;

        valArray.forEach(pId => {
            const opt = selectElem ? selectElem.querySelector(`option[value="${pId}"]`) : null;
            const name = opt ? (opt.dataset.name || opt.text) : ('Produk #' + pId);
            const variant = opt ? (opt.dataset.variant || '') : '';
            const sku = opt ? (opt.dataset.sku || '') : '';
            const masterPurchase = opt ? (parseFloat(opt.dataset.purchase || 0)) : 0;

            const finalPrice = (customPriceRaw !== null && !isNaN(customPriceRaw)) ? customPriceRaw : masterPurchase;

            const existingIdx = itemQueue.findIndex(item => String(item.product_id) === String(pId));
            if (existingIdx >= 0) {
                itemQueue[existingIdx].quantity += qty;
                if (customPriceRaw !== null && !isNaN(customPriceRaw)) {
                    itemQueue[existingIdx].purchase_price = customPriceRaw;
                }
            } else {
                itemQueue.push({
                    product_id: pId,
                    name: name,
                    variant: variant,
                    sku: sku,
                    quantity: qty,
                    purchase_price: finalPrice
                });
            }
        });

        renderQueueUI();

        // Reset input fields and re-focus TomSelect
        if (builderTomSelect) {
            builderTomSelect.clear();
            builderTomSelect.focus();
        }
        if (qtyInput) qtyInput.value = 1;
        if (priceInput) {
            priceInput.value = '';
            priceInput.placeholder = 'Contoh: 85000';
        }
        const badge = document.getElementById('builder_stock_badge');
        if (badge) badge.classList.add('hidden');
    }

    function removeItemFromQueue(index) {
        if (index >= 0 && index < itemQueue.length) {
            itemQueue.splice(index, 1);
            renderQueueUI();
        }
    }

    function updateQueueItemQty(index, newQty) {
        const parsed = parseInt(newQty);
        if (!isNaN(parsed) && parsed > 0 && index >= 0 && index < itemQueue.length) {
            itemQueue[index].quantity = parsed;
            renderQueueUI();
        }
    }

    function updateQueueItemPrice(index, newPrice) {
        const parsed = parseFloat(newPrice);
        if (!isNaN(parsed) && parsed >= 0 && index >= 0 && index < itemQueue.length) {
            itemQueue[index].purchase_price = parsed;
            renderQueueUI();
        }
    }

    function renderQueueUI() {
        const tbody = document.getElementById('queue_table_body');
        const emptyState = document.getElementById('queue_empty_state');
        const summaryFooter = document.getElementById('queue_summary_footer');
        const hiddenInputs = document.getElementById('queue_hidden_inputs');
        const badgeCount = document.getElementById('queue_badge_count');
        const btnSaveCount = document.getElementById('btn_save_item_count');
        const submitBtn = document.getElementById('btn_submit_movement');

        if (badgeCount) badgeCount.textContent = `${itemQueue.length} Produk`;
        if (btnSaveCount) btnSaveCount.textContent = itemQueue.length;
        if (submitBtn) submitBtn.disabled = (itemQueue.length === 0);

        if (itemQueue.length === 0) {
            if (emptyState) emptyState.classList.remove('hidden');
            if (summaryFooter) summaryFooter.classList.add('hidden');
            if (tbody) tbody.innerHTML = '';
            if (hiddenInputs) hiddenInputs.innerHTML = '';
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');
        if (summaryFooter) summaryFooter.classList.remove('hidden');

        let totalQty = 0;
        let totalAmount = 0;
        let rowsHtml = '';
        let hiddenHtml = '';

        itemQueue.forEach((item, idx) => {
            const subtotal = item.quantity * item.purchase_price;
            totalQty += item.quantity;
            totalAmount += subtotal;

            rowsHtml += `
                <tr class="hover:bg-surface-container-low/50 transition">
                    <td class="py-2.5 px-3 text-center text-on-surface-variant font-mono font-medium">${idx + 1}</td>
                    <td class="py-2.5 px-3">
                        <div class="font-bold text-on-surface leading-snug">${escapeHtml(item.name)}${item.variant ? ' - ' + escapeHtml(item.variant) : ''}</div>
                        <div class="text-[10px] text-on-surface-variant/70 font-mono">${item.sku ? escapeHtml(item.sku) : '-'}</div>
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <div class="inline-flex items-center gap-1">
                            <input type="number" min="1" value="${item.quantity}"
                                onchange="updateQueueItemQty(${idx}, this.value)"
                                class="w-16 px-2 py-1 text-center font-bold text-xs bg-surface-container-low border border-outline-variant rounded-lg focus:outline-none focus:ring-1 focus:ring-primary" />
                            <span class="text-[10px] text-on-surface-variant font-semibold">pcs</span>
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-right">
                        <div class="relative inline-flex items-center">
                            <span class="absolute left-2 text-[10px] font-bold text-on-surface-variant pointer-events-none">Rp</span>
                            <input type="number" min="0" step="0.01" value="${item.purchase_price}"
                                onchange="updateQueueItemPrice(${idx}, this.value)"
                                class="w-28 pl-6 pr-2 py-1 text-right font-mono font-semibold text-xs bg-surface-container-low border border-outline-variant rounded-lg focus:outline-none focus:ring-1 focus:ring-primary" />
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono font-bold text-primary">
                        Rp ${formatRupiah(subtotal)}
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <button type="button" onclick="removeItemFromQueue(${idx})"
                            title="Hapus dari antrian"
                            class="p-1 text-error/70 hover:text-error hover:bg-error-container/30 rounded-lg transition border-0 bg-transparent cursor-pointer inline-flex items-center">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </td>
                </tr>
            `;

            hiddenHtml += `
                <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                <input type="hidden" name="items[${idx}][purchase_price]" value="${item.purchase_price}">
            `;
        });

        if (tbody) tbody.innerHTML = rowsHtml;
        if (hiddenInputs) hiddenInputs.innerHTML = hiddenHtml;

        const totalQtyLabel = document.getElementById('queue_total_qty_label');
        const totalTypesLabel = document.getElementById('queue_total_types_label');
        const totalAmountLabel = document.getElementById('queue_total_amount_label');

        if (totalQtyLabel) totalQtyLabel.textContent = `${formatRupiah(totalQty)} pcs`;
        if (totalTypesLabel) totalTypesLabel.textContent = itemQueue.length;
        if (totalAmountLabel) totalAmountLabel.textContent = `Rp ${formatRupiah(totalAmount)}`;
    }

    // ─── STOCK OUT HELPER FUNCTIONS ───────────────────────────────────────────────
    function calculateTotal() {
        const qtyEl = document.getElementById('log_quantity');
        const pPriceEl = document.getElementById('log_purchase_price');
        const sPriceEl = document.getElementById('log_selling_price');
        const discEl = document.getElementById('log_discount');
        const totalEl = document.getElementById('log_total_price');

        if (!qtyEl || !totalEl) return;

        const qty = parseFloat(qtyEl.value) || 0;
        const purchasePrice = parseFloat(pPriceEl ? pPriceEl.value : 0) || 0;
        const sellingPrice = parseFloat(sPriceEl ? sPriceEl.value : 0) || 0;
        const discount = discEl ? (parseFloat(discEl.value) || 0) : 0;
        
        const basePrice = isStockIn ? (purchasePrice > 0 ? purchasePrice : 0) : sellingPrice;
        let total = (basePrice * qty) - discount;
        if (total < 0) total = 0;
        totalEl.value = total;
    }

    function toggleEditSessionTempo(val) {
        const container = document.getElementById('edit_session_due_date_container');
        if (container) {
            if (val === 'tempo' || val === 'hutang') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('log-movement-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (isStockIn) {
                    if (itemQueue.length === 0) {
                        e.preventDefault();
                        alert('Daftar barang masih kosong! Silakan tambah produk terlebih dahulu sebelum menyimpan.');
                        if (builderTomSelect) builderTomSelect.focus();
                        return false;
                    }
                } else {
                    const qtyEl = document.getElementById('log_quantity');
                    const qty = parseFloat(qtyEl ? qtyEl.value : 0);
                    if (isNaN(qty) || qty <= 0) {
                        e.preventDefault();
                        alert('Quantity tidak bisa 0! Silakan masukkan jumlah yang valid (minimal 1).');
                        return false;
                    }
                }
            });
        }

        if (isStockIn) {
            const builderSelect = document.getElementById('builder_product_id');
            if (builderSelect) {
                builderTomSelect = new TomSelect('#builder_product_id', {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: null,
                    placeholder: 'Cari & pilih produk (bisa multiple)...',
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
                        const badge = document.getElementById('builder_stock_badge');
                        const priceInput = document.getElementById('builder_purchase_price');
                        const qtyInput = document.getElementById('builder_qty');

                        if (valArray.length === 0) {
                            if (badge) badge.classList.add('hidden');
                            if (priceInput) {
                                priceInput.value = '';
                                priceInput.placeholder = 'Contoh: 85000';
                            }
                            return;
                        }

                        if (valArray.length === 1) {
                            const opt = builderSelect.querySelector(`option[value="${valArray[0]}"]`);
                            if (opt) {
                                const buyPrice = parseFloat(opt.dataset.purchase || 0);
                                const stock = parseFloat(opt.dataset.stock || 0);
                                if (priceInput) {
                                    priceInput.value = buyPrice || '';
                                    priceInput.placeholder = 'Contoh: 85000';
                                }
                                if (badge) {
                                    badge.textContent = `Stok master: ${formatRupiah(stock)} pcs`;
                                    badge.classList.remove('hidden');
                                }
                                if (qtyInput) qtyInput.focus();
                            }
                        } else {
                            if (badge) {
                                badge.textContent = `${valArray.length} produk dipilih`;
                                badge.classList.remove('hidden');
                            }
                            let allSame = true;
                            const firstOpt = builderSelect.querySelector(`option[value="${valArray[0]}"]`);
                            const firstPrice = parseFloat(firstOpt?.dataset?.purchase || 0);
                            valArray.forEach(id => {
                                const opt = builderSelect.querySelector(`option[value="${id}"]`);
                                if (opt && parseFloat(opt.dataset.purchase || 0) !== firstPrice) {
                                    allSame = false;
                                }
                            });

                            if (priceInput) {
                                if (allSame) {
                                    priceInput.value = firstPrice || '';
                                    priceInput.placeholder = 'Harga master seragam';
                                } else {
                                    priceInput.value = '';
                                    priceInput.placeholder = 'Harga master masing-masing';
                                }
                            }
                        }
                    }
                });
            }

            const bQty = document.getElementById('builder_qty');
            const bPrice = document.getElementById('builder_purchase_price');
            [bQty, bPrice].forEach(inp => {
                if (inp) {
                    inp.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addItemToQueue();
                        }
                    });
                }
            });

            renderQueueUI();
        } else {
            const logQty = document.getElementById('log_quantity');
            if (logQty) logQty.addEventListener('input', calculateTotal);

            const logProd = document.getElementById('log_product_id');
            if (logProd) {
                logTomSelect = new TomSelect('#log_product_id', {
                    create: false,
                    maxItems: 1,
                    placeholder: '-- Pilih Produk --',
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    onChange: function(val) {
                        if (!val) return;
                        const opt = logProd.querySelector(`option[value="${val}"]`);
                        if (opt) {
                            const buy = parseFloat(opt.dataset.purchase || 0);
                            const sell = parseFloat(opt.dataset.selling || 0);
                            const pInp = document.getElementById('log_purchase_price');
                            const sInp = document.getElementById('log_selling_price');
                            if (pInp) pInp.value = buy || 0;
                            if (sInp) sInp.value = sell || 0;
                        }
                        calculateTotal();
                    }
                });
            }
        }

        new TomSelect('#filter_product_id',{
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            onChange: function(value) {
                document.getElementById('filter-form').submit();
            }
        });

        if (document.getElementById('edit_supplier_name')) {
            new TomSelect('#edit_supplier_name', {
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

        // Bulk Delete for Movement Items
        const selectAllMov = document.getElementById('selectAllMovItems');
        const movCheckboxes = document.querySelectorAll('.mov-checkbox');
        const movBulkBar = document.getElementById('movBulkActionBar');
        const movBadge = document.getElementById('movSelectedCountBadge');
        const btnDeleteSelectedMov = document.getElementById('btnDeleteSelectedMov');
        const btnCancelMov = document.getElementById('btnCancelMovSelection');

        function updateMovBulkUI() {
            const checked = Array.from(document.querySelectorAll('.mov-checkbox')).filter(cb => cb.checked);
            if (selectAllMov) {
                if (checked.length === 0) {
                    selectAllMov.checked = false;
                    selectAllMov.indeterminate = false;
                } else if (checked.length === movCheckboxes.length && movCheckboxes.length > 0) {
                    selectAllMov.checked = true;
                    selectAllMov.indeterminate = false;
                } else {
                    selectAllMov.checked = false;
                    selectAllMov.indeterminate = true;
                }
            }

            if (checked.length > 0) {
                if (movBulkBar) movBulkBar.classList.remove('hidden');
                if (movBadge) movBadge.textContent = checked.length + ' log dipilih';
            } else {
                if (movBulkBar) movBulkBar.classList.add('hidden');
            }
        }

        if (selectAllMov) {
            selectAllMov.addEventListener('change', function () {
                document.querySelectorAll('.mov-checkbox').forEach(cb => cb.checked = selectAllMov.checked);
                updateMovBulkUI();
            });
        }

        document.querySelectorAll('.mov-checkbox').forEach(cb => {
            cb.addEventListener('change', updateMovBulkUI);
        });

        if (btnCancelMov) {
            btnCancelMov.addEventListener('click', function () {
                if (selectAllMov) {
                    selectAllMov.checked = false;
                    selectAllMov.indeterminate = false;
                }
                document.querySelectorAll('.mov-checkbox').forEach(cb => cb.checked = false);
                updateMovBulkUI();
            });
        }

        const btnApproveSelectedMov = document.getElementById('btnApproveSelectedMov');
        if (btnApproveSelectedMov) {
            btnApproveSelectedMov.addEventListener('click', function () {
                const checked = Array.from(document.querySelectorAll('.mov-checkbox')).filter(cb => cb.checked);
                if (checked.length === 0) {
                    alert('Pilih minimal satu movement log untuk di-approve.');
                    return;
                }
                if (confirm(`Apakah Anda yakin ingin menyetujui (Approve) ${checked.length} movement log yang dipilih? Stok produk dan HPP akan langsung disesuaikan.`)) {
                    const form = document.getElementById('bulkApproveMovForm');
                    const container = document.getElementById('bulkApproveMovContainer');
                    container.innerHTML = '';
                    checked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });
                    form.submit();
                }
            });
        }

        const btnUnapproveSelectedMov = document.getElementById('btnUnapproveSelectedMov');
        if (btnUnapproveSelectedMov) {
            btnUnapproveSelectedMov.addEventListener('click', function () {
                const checked = Array.from(document.querySelectorAll('.mov-checkbox')).filter(cb => cb.checked);
                if (checked.length === 0) {
                    alert('Pilih minimal satu movement log untuk dikembalikan ke draft.');
                    return;
                }
                if (confirm(`Apakah Anda yakin ingin mengembalikan ${checked.length} movement log yang dipilih menjadi DRAFT?\n\n- Stok produk akan dikurangi kembali.\n- HPP produk akan disesuaikan.\n- Jurnal transaksi terkait akan dihapus.`)) {
                    const form = document.getElementById('bulkUnapproveMovForm');
                    const container = document.getElementById('bulkUnapproveMovContainer');
                    container.innerHTML = '';
                    checked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });
                    form.submit();
                }
            });
        }

        if (btnDeleteSelectedMov) {
            btnDeleteSelectedMov.addEventListener('click', function () {
                const checked = Array.from(document.querySelectorAll('.mov-checkbox')).filter(cb => cb.checked);
                if (checked.length === 0) {
                    alert('Pilih minimal satu movement log untuk dihapus.');
                    return;
                }
                if (confirm(`Apakah Anda yakin ingin menghapus ${checked.length} movement log yang dipilih? Stok barang akan disesuaikan kembali.`)) {
                    const form = document.getElementById('bulkDeleteMovForm');
                    const container = document.getElementById('bulkDeleteMovContainer');
                    container.innerHTML = '';
                    checked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });
                    form.submit();
                }
            });
        }
    });
</script>
@endpush

{{-- Hidden Form for bulk movement approve --}}
<form id="bulkApproveMovForm" action="{{ route('movements.bulk-approve') }}" method="POST" class="hidden">
    @csrf
    <div id="bulkApproveMovContainer"></div>
</form>

{{-- Hidden Form for bulk movement unapprove --}}
<form id="bulkUnapproveMovForm" action="{{ route('movements.bulk-unapprove') }}" method="POST" class="hidden">
    @csrf
    <div id="bulkUnapproveMovContainer"></div>
</form>

{{-- Hidden Form for bulk movement delete --}}
<form id="bulkDeleteMovForm" action="{{ route('movements.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    <div id="bulkDeleteMovContainer"></div>
</form>

@endsection
