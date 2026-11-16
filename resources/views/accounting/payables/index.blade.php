@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buku Hutang Supplier')

@section('content')

{{-- Header --}}
<div class="mb-6">
    <h1 class="font-headline-lg text-headline-lg text-on-surface">Buku Hutang Supplier (Accounts Payable)</h1>
    <p class="text-on-surface-variant text-body-lg">Pemantauan tagihan pembelian stok tempo/kredit, jatuh tempo, dan riwayat pelunasan hutang.</p>
</div>

{{-- Main Navigation Tabs (Tanpa Icon Sesuai Permintaan) --}}
<div class="flex items-center gap-2 mb-6 border-b border-outline-variant pb-3">
    <a href="{{ route('accounting.payables.index', ['tab' => 'branch']) }}"
       class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all no-underline {{ $tab === 'branch' ? 'bg-primary text-on-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
        Hutang Belanja Cabang
    </a>
    <a href="{{ route('accounting.payables.index', ['tab' => 'legacy']) }}"
       class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all no-underline {{ $tab === 'legacy' ? 'bg-primary text-on-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
        Hutang Warisan Bersama (3 Cabang)
    </a>
</div>

@if($tab === 'branch')
    {{-- ================= TAB 1: HUTANG BELANJA CABANG ================= --}}
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Sisa Hutang Aktif</p>
                <h3 class="text-xl font-headline-md text-rose-800 font-extrabold mt-0.5">
                    Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ $overdueCount > 0 ? 'bg-error-container text-error' : 'bg-emerald-500/10 text-emerald-700' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Hutang Melewati Jatuh Tempo</p>
                <h3 class="text-xl font-headline-md {{ $overdueCount > 0 ? 'text-error' : 'text-emerald-800' }} font-extrabold mt-0.5">
                    {{ $overdueCount }} Tagihan (Rp {{ number_format($overdueAmount, 0, ',', '.') }})
                </h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Terbayar Lunas</p>
                <h3 class="text-xl font-headline-md text-emerald-800 font-extrabold mt-0.5">
                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="p-4 mb-6 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        {{-- Status Filter Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => 'unpaid', 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline whitespace-nowrap {{ $statusFilter === 'unpaid' ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                Belum Lunas
            </a>
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => 'pending', 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline whitespace-nowrap {{ $statusFilter === 'pending' ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                Belum Dibayar
            </a>
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => 'partial', 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline whitespace-nowrap {{ $statusFilter === 'partial' ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                Dicicil / Sebagian
            </a>
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => 'paid', 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline whitespace-nowrap {{ $statusFilter === 'paid' ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                Lunas
            </a>
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => 'all', 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline whitespace-nowrap {{ $statusFilter === 'all' ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container-high' }}">
                Semua Tagihan
            </a>
        </div>

        {{-- Search Bar --}}
        <form method="GET" action="{{ route('accounting.payables.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="branch">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari supplier / no. faktur..."
                       class="w-full pl-9 pr-3 py-1.5 text-xs bg-surface-container-low border border-outline-variant rounded-xl focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-surface-container-high border border-outline-variant rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container-highest cursor-pointer">
                Cari
            </button>
            @if($search)
            <a href="{{ route('accounting.payables.index', ['tab' => 'branch', 'status' => $statusFilter]) }}" class="text-xs text-error font-semibold hover:underline">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold uppercase tracking-wider">
                        <th class="px-4 py-3.5">Faktur / Pengadaan</th>
                        <th class="px-4 py-3.5">Supplier</th>
                        <th class="px-4 py-3.5">Rincian Produk</th>
                        <th class="px-4 py-3.5">Tgl Pembelian</th>
                        <th class="px-4 py-3.5">Jatuh Tempo</th>
                        <th class="px-4 py-3.5 text-right">Total Tagihan</th>
                        <th class="px-4 py-3.5 text-right">Terbayar</th>
                        <th class="px-4 py-3.5 text-right">Sisa Hutang</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($bills as $bill)
                    @php
                        $isOverdue = $bill->is_overdue;
                        $statusBadge = match($bill->payment_status) {
                            'paid' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                            'partial' => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
                            'pending' => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
                            default => 'bg-surface-container text-on-surface border-outline-variant',
                        };
                        $dateObj = \Illuminate\Support\Carbon::parse($bill->movement_date);
                        $productSnippet = $bill->items->map(fn($item) => $item->product?->name)->filter()->unique()->take(2)->implode(', ');
                        if ($bill->total_items > 2) {
                            $productSnippet .= ' (+' . ($bill->total_items - 2) . ' lainnya)';
                        }
                    @endphp
                    {{-- Group / Folder Row --}}
                    <tr class="hover:bg-surface-container-low/60 transition-colors {{ $isOverdue ? 'bg-error-container/10' : '' }}">
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="togglePayableFolder('{{ $bill->key }}')"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 transition cursor-pointer border-0"
                                        title="Klik untuk membuka isi folder pengadaan ini">
                                    <span class="material-symbols-outlined text-lg" id="icon-{{ $bill->key }}">folder</span>
                                </button>
                                <div>
                                    <div class="font-extrabold text-on-surface text-sm cursor-pointer hover:text-primary transition" onclick="togglePayableFolder('{{ $bill->key }}')">
                                        Stock In {{ $dateObj->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.2 rounded-full border border-amber-300">
                                            {{ $bill->total_items }} Produk
                                        </span>
                                        <span class="text-[11px] text-on-surface-variant font-medium">({{ $bill->total_units }} unit)</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-on-surface">
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-on-surface-variant">storefront</span>
                                <span>{{ $bill->supplier_name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="max-w-xs cursor-pointer" onclick="togglePayableFolder('{{ $bill->key }}')">
                                <span class="font-medium text-on-surface">{{ $productSnippet ?: '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-on-surface font-medium">
                            {{ $dateObj->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($bill->due_date)
                                @php $dueDateObj = \Illuminate\Support\Carbon::parse($bill->due_date); @endphp
                                <span class="{{ $isOverdue ? 'text-error font-bold flex items-center gap-1' : 'text-on-surface' }}">
                                    @if($isOverdue) <span class="material-symbols-outlined text-xs">warning</span> @endif
                                    {{ $dueDateObj->translatedFormat('d M Y') }}
                                </span>
                            @else
                                <span class="text-on-surface-variant">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono font-bold text-on-surface whitespace-nowrap">
                            Rp {{ number_format($bill->total_bill, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono font-medium text-emerald-700 whitespace-nowrap">
                            Rp {{ number_format($bill->total_paid, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono font-extrabold {{ $bill->remaining_debt > 0 ? 'text-rose-700' : 'text-on-surface-variant' }} whitespace-nowrap">
                            Rp {{ number_format($bill->remaining_debt, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusBadge }}">
                                {{ match($bill->payment_status) {
                                    'paid' => 'Lunas',
                                    'partial' => 'Dicicil',
                                    'pending' => 'Belum Bayar',
                                    default => ucfirst($bill->payment_status)
                                } }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($bill->remaining_debt > 0)
                                    @can('Accounting Payable Manage')
                                        @if($bill->total_items > 1)
                                        <a href="{{ route('accounting.payables.batch-pay', ['ids' => implode(',', $bill->item_ids)]) }}" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs active:scale-95 transition-all shadow-xs no-underline">
                                            <span class="material-symbols-outlined text-sm">payments</span>
                                            Bayar Tagihan
                                        </a>
                                        @else
                                        <a href="{{ route('accounting.payables.pay', $bill->first_id) }}" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-on-primary rounded-xl font-bold text-xs hover:opacity-90 active:scale-95 transition-all shadow-xs no-underline">
                                            <span class="material-symbols-outlined text-sm">payments</span>
                                            Bayar
                                        </a>
                                        @endif
                                    @endcan
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-emerald-700 font-bold text-xs">
                                        <span class="material-symbols-outlined text-sm">check_circle</span> Lunas
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- Collapsible Folder Details Row --}}
                    <tr id="folder-{{ $bill->key }}" class="hidden bg-surface-container-low/40">
                        <td colspan="10" class="p-3 sm:p-5">
                            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-5 shadow-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 mb-3 border-b border-outline-variant">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-amber-600 text-xl">folder_open</span>
                                        <div>
                                            <span class="font-extrabold text-on-surface text-sm">Rincian Produk: Stock In {{ $dateObj->translatedFormat('d F Y') }}</span>
                                            <span class="text-xs text-on-surface-variant ml-1 font-normal">• Supplier: <strong>{{ $bill->supplier_name }}</strong></span>
                                        </div>
                                    </div>
                                    @if($bill->remaining_debt > 0 && $bill->total_items > 1)
                                    <a href="{{ route('accounting.payables.batch-pay', ['ids' => implode(',', $bill->item_ids)]) }}" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs no-underline self-start sm:self-auto">
                                        <span class="material-symbols-outlined text-sm">payments</span>
                                        Bayar Sekaligus Seluruh Item (Rp {{ number_format($bill->remaining_debt, 0, ',', '.') }})
                                    </a>
                                    @endif
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="text-on-surface-variant font-bold uppercase tracking-wider border-b border-outline-variant pb-2 bg-surface-container-low/50">
                                                <th class="px-3 py-2">No. Ref</th>
                                                <th class="px-3 py-2">Nama Produk & Varian</th>
                                                <th class="px-3 py-2 text-center">Qty</th>
                                                <th class="px-3 py-2 text-right">Harga Beli</th>
                                                <th class="px-3 py-2 text-right">Total Tagihan</th>
                                                <th class="px-3 py-2 text-right">Terbayar</th>
                                                <th class="px-3 py-2 text-right">Sisa Hutang</th>
                                                <th class="px-3 py-2 text-center">Status</th>
                                                <th class="px-3 py-2 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant">
                                            @foreach($bill->items as $item)
                                            @php
                                                $iTotal = (float) ($item->total_price ?: ($item->quantity * ($item->purchase_price ?? 0)));
                                                $iRemaining = $item->remaining_debt;
                                            @endphp
                                            <tr class="hover:bg-surface-container-low/30 transition-colors">
                                                <td class="px-3 py-2.5 font-mono font-bold text-primary">{{ $item->reference_number ?: "IN-{$item->id}" }}</td>
                                                <td class="px-3 py-2.5">
                                                    <div class="font-bold text-on-surface">{{ $item->product?->name ?? 'Produk' }}</div>
                                                    @if(!empty($item->product?->variant))
                                                        <div class="text-[11px] text-on-surface-variant">{{ $item->product->variant }} ({{ $item->product->sku }})</div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 text-center font-medium">{{ $item->quantity }} pcs</td>
                                                <td class="px-3 py-2.5 text-right font-mono text-on-surface">Rp {{ number_format($item->purchase_price ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2.5 text-right font-mono font-semibold text-on-surface">Rp {{ number_format($iTotal, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2.5 text-right font-mono text-emerald-700">Rp {{ number_format($item->paid_amount ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2.5 text-right font-mono font-bold {{ $iRemaining > 0 ? 'text-rose-700' : 'text-on-surface-variant' }}">
                                                    Rp {{ number_format($iRemaining, 0, ',', '.') }}
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $item->payment_status_badge }}">
                                                        {{ match($item->payment_status) {
                                                            'paid' => 'Lunas',
                                                            'partial' => 'Dicicil',
                                                            'pending' => 'Belum Bayar',
                                                            default => ucfirst($item->payment_status)
                                                        } }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    @if($iRemaining > 0)
                                                    <a href="{{ route('accounting.payables.pay', $item->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary text-on-primary rounded-lg text-[11px] font-bold hover:opacity-90 transition no-underline">
                                                        <span class="material-symbols-outlined text-xs">payments</span>
                                                        Bayar Item
                                                    </a>
                                                    @else
                                                    <a href="{{ route('accounting.payables.pay', $item->id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 text-on-surface-variant hover:text-primary text-[11px] font-semibold transition no-underline">
                                                        <span class="material-symbols-outlined text-xs">visibility</span> Riwayat
                                                    </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">receipt_long</span>
                                <p class="font-semibold text-sm">Tidak ada tagihan atau hutang supplier belanja cabang yang ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($bills->hasPages())
        <div class="p-4 border-t border-outline-variant bg-surface-container-low">
            {{ $bills->links() }}
        </div>
        @endif
    </div>

    <script>
        function togglePayableFolder(folderKey) {
            const el = document.getElementById('folder-' + folderKey);
            const icon = document.getElementById('icon-' + folderKey);
            if (!el) return;
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) icon.textContent = 'folder_open';
            } else {
                el.classList.add('hidden');
                if (icon) icon.textContent = 'folder';
            }
        }
    </script>

@else
    {{-- ================= TAB 2: HUTANG WARISAN BERSAMA (3 CABANG) ================= --}}
    @php
        $currentStoreObj = \App\Models\Store::find(session('active_store_id')) ?? auth()->user()->stores()->first();
        $currentStoreName = $currentStoreObj ? $currentStoreObj->name : 'Toko Aktif';
    @endphp

    {{-- Banner Informasi --}}
    <div class="p-5 mb-6 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                    <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Hutang Warisan Bersama (3 Cabang)</h3>
                    <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5 leading-relaxed">
                        Hutang dari manajemen lama (Vape Break, Vapezoo, Vape On) yang menjadi tanggung jawab bersama antara <strong>Vol 1</strong>, <strong>Vol 2</strong>, dan <strong>Belitung</strong>.
                        Setiap cicilan yang dibayarkan oleh cabang mana pun akan langsung mengurangi akun kewajiban <em>(2105: Hutang Warisan Manajemen Lama)</em> tanpa membebani Laba Rugi operasional berjalan toko.
                    </p>
                </div>
            </div>
            <div class="shrink-0 self-start md:self-center">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500/10 text-amber-900 border border-amber-500/20 rounded-xl text-xs font-bold">
                    Toko Aktif Pencatat: {{ $currentStoreName }}
                </span>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-500/10 text-slate-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">history_edu</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Hutang Warisan Awal</p>
                <h3 class="text-xl font-headline-md text-on-surface font-extrabold mt-0.5">
                    Rp {{ number_format($totalLegacyInitial, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">3 Supplier Lama</p>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Sudah Dicicil</p>
                <h3 class="text-xl font-headline-md text-emerald-800 font-extrabold mt-0.5">
                    Rp {{ number_format($totalLegacyPaid, 0, ',', '.') }}
                </h3>
                @php
                    $overallProgress = $totalLegacyInitial > 0 ? round(($totalLegacyPaid / $totalLegacyInitial) * 100, 1) : 0;
                @endphp
                <p class="text-[11px] text-emerald-700 font-bold mt-0.5">{{ $overallProgress }}% Terlunasi</p>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Sisa Hutang Warisan</p>
                <h3 class="text-xl font-headline-md text-rose-800 font-extrabold mt-0.5">
                    Rp {{ number_format($totalLegacyRemaining, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-rose-700 font-bold mt-0.5">Belum Selesai</p>
            </div>
        </div>
    </div>

    {{-- Rekap Kontribusi Pembayaran per Cabang --}}
    <div class="p-5 mb-6 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-extrabold text-on-surface">Transparansi Kontribusi Cicilan per Cabang</h3>
                <p class="text-xs text-on-surface-variant">Rekap nominal yang telah disetorkan oleh masing-masing toko untuk mencicil hutang warisan bersama.</p>
            </div>
        </div>

        @if($legacyStoreContributions->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach($legacyStoreContributions as $sc)
            <div class="p-4 bg-surface-container-low border border-outline-variant rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-on-surface block">{{ $sc->name }}</span>
                    <span class="text-[11px] text-on-surface-variant">{{ $sc->payment_count }} kali angsuran</span>
                </div>
                <div class="text-right">
                    <span class="text-sm font-extrabold text-emerald-700 block">Rp {{ number_format($sc->total_contributed, 0, ',', '.') }}</span>
                    <span class="text-[10px] text-on-surface-variant font-semibold">
                        {{ $totalLegacyPaid > 0 ? round(($sc->total_contributed / $totalLegacyPaid) * 100, 1) : 0 }}% porsi
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-4 bg-surface-container-low border border-dashed border-outline-variant rounded-xl text-center text-xs text-on-surface-variant">
            Belum ada cabang yang mencatatkan pembayaran cicilan untuk hutang warisan ini.
        </div>
        @endif
    </div>

    {{-- 3 Kartu Supplier Hutang Warisan --}}
    <div class="mb-8">
        <h3 class="text-sm font-extrabold text-on-surface mb-3">Daftar 3 Supplier Hutang Warisan</h3>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @foreach($legacyDebts as $debt)
            @php
                $isPaidOff = $debt->remaining_amount <= 0.01;
                $hasStarted = $debt->paid_amount > 0.01;
            @endphp
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">{{ $debt->code }}</span>
                            <h4 class="text-base font-extrabold text-on-surface mt-1.5">{{ $debt->supplier_name }}</h4>
                            <p class="text-xs text-on-surface-variant font-medium flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-xs">location_on</span>
                                {{ $debt->location ?: 'Pusat' }}
                            </p>
                        </div>
                        <div>
                            @if($isPaidOff)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                                Lunas
                            </span>
                            @elseif($hasStarted)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20">
                                Dicicil
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-700 border border-rose-500/20">
                                Belum Dicicil
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-on-surface-variant font-semibold">Progress Pelunasan</span>
                            <span class="font-extrabold text-on-surface">{{ $debt->progress_percent }}%</span>
                        </div>
                        <div class="w-full bg-surface-container-high rounded-full h-2.5 overflow-hidden">
                            <div class="bg-emerald-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $debt->progress_percent }}%"></div>
                        </div>
                    </div>

                    {{-- Financial Breakdown --}}
                    <div class="space-y-2 py-3 border-y border-outline-variant text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Hutang Awal:</span>
                            <span class="font-bold text-on-surface">Rp {{ number_format($debt->initial_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-on-surface-variant">Sudah Dicicil:</span>
                            <span class="font-bold text-emerald-700">Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <span class="font-extrabold text-on-surface">Sisa Hutang:</span>
                            <span class="font-extrabold text-base text-rose-800">Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($debt->notes)
                    <p class="text-[11px] text-on-surface-variant italic mt-3">{{ $debt->notes }}</p>
                    @endif
                </div>

                <div class="mt-5 pt-3">
                    @if(!$isPaidOff)
                    <button type="button" 
                            onclick="openLegacyPayModal({{ json_encode($debt) }})"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs active:scale-95 transition-all shadow-xs cursor-pointer border-0">
                        <span class="material-symbols-outlined text-sm">payments</span>
                        Bayar Cicilan ({{ $currentStoreName }})
                    </button>
                    @else
                    <div class="text-center py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl border border-emerald-200">
                        Hutang ini telah lunas sepenuhnya
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Riwayat Pembayaran Cicilan Hutang Warisan --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden mb-6">
        <div class="p-4 sm:p-5 border-b border-outline-variant flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-on-surface">Riwayat Pembayaran Cicilan Utang Warisan</h3>
                <p class="text-xs text-on-surface-variant">Semua pembayaran cicilan yang telah disetor oleh cabang-cabang.</p>
            </div>
            <span class="text-xs font-bold text-on-surface-variant bg-surface-container-low px-3 py-1 rounded-full border border-outline-variant">
                Total {{ $legacyDebts->flatMap->payments->count() }} Transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold uppercase tracking-wider">
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Supplier Warisan</th>
                        <th class="px-4 py-3.5">Cabang yang Membayar</th>
                        <th class="px-4 py-3.5">Sumber Dana (Kas/Bank)</th>
                        <th class="px-4 py-3.5 text-right">Nominal Cicilan</th>
                        <th class="px-4 py-3.5">No. Referensi / Catatan</th>
                        <th class="px-4 py-3.5">Pencatat</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @php
                        $allLegacyPayments = $legacyDebts->flatMap->payments->sortByDesc('payment_date');
                    @endphp
                    @forelse($allLegacyPayments as $p)
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-4 py-3.5 font-medium whitespace-nowrap text-on-surface">
                            {{ $p->payment_date ? \Illuminate\Support\Carbon::parse($p->payment_date)->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="font-extrabold text-on-surface">{{ $p->legacyDebt?->supplier_name }}</span>
                            <span class="text-[11px] text-on-surface-variant block">{{ $p->legacyDebt?->location }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-lg font-bold text-[11px]">
                                {{ $p->store?->name ?? 'Pusat' }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="font-semibold text-on-surface">{{ $p->paymentAccount?->name ?? 'Kas / Bank' }}</span>
                            <span class="text-[10px] font-mono text-on-surface-variant block">{{ $p->paymentAccount?->code }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right font-mono font-extrabold text-emerald-700 whitespace-nowrap">
                            Rp {{ number_format($p->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5">
                            @if($p->reference_number)
                            <div class="font-mono text-xs font-semibold text-on-surface">{{ $p->reference_number }}</div>
                            @endif
                            @if($p->notes)
                            <div class="text-[11px] text-on-surface-variant mt-0.5">{{ $p->notes }}</div>
                            @endif
                            @if(!$p->reference_number && !$p->notes)
                            <span class="text-on-surface-variant">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-on-surface-variant">
                            {{ $p->user?->name ?? 'Sistem' }}
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @can('Accounting Payable Manage')
                            <form method="POST" action="{{ route('accounting.payables.legacy.destroy-payment', $p->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan cicilan sebesar Rp {{ number_format($p->amount, 0, ',', '.') }} ini? Jurnal akuntansi terkait akan dihapus.');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-error hover:bg-error-container/20 rounded-lg transition border-0 bg-transparent cursor-pointer" title="Batalkan Pembayaran Cicilan">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">savings</span>
                                <p class="font-semibold text-sm">Belum ada riwayat pembayaran cicilan hutang warisan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Bayar Cicilan Hutang Warisan --}}
    <div id="legacyPayModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xl max-w-lg w-full overflow-hidden animate-fade-in">
            <div class="p-5 border-b border-outline-variant flex items-center justify-between bg-surface-container-low">
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Bayar Cicilan Hutang Warisan</h3>
                    <p class="text-xs text-on-surface-variant" id="modalSupplierSubtitle">Supplier Warisan</p>
                </div>
                <button type="button" onclick="closeLegacyPayModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high border-0 bg-transparent cursor-pointer">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form id="legacyPayForm" method="POST" action="" class="p-5 space-y-4">
                @csrf
                
                {{-- Info Box --}}
                <div class="p-3.5 bg-surface-container-low border border-outline-variant rounded-xl flex items-center justify-between text-xs">
                    <div>
                        <span class="text-on-surface-variant block font-medium">Toko yang Membayar:</span>
                        <span class="font-extrabold text-on-surface text-sm">{{ $currentStoreName }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-on-surface-variant block font-medium">Sisa Hutang:</span>
                        <span class="font-extrabold text-rose-800 text-sm" id="modalRemainingText">Rp 0</span>
                    </div>
                </div>

                {{-- Tanggal Pembayaran --}}
                <div>
                    <label for="legacy_payment_date" class="block text-xs font-bold text-on-surface mb-1.5">
                        Tanggal Pembayaran <span class="text-error">*</span>
                    </label>
                    <input type="date" id="legacy_payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required
                           class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                {{-- Akun Kas / Bank --}}
                <div>
                    <label for="legacy_payment_account_id" class="block text-xs font-bold text-on-surface mb-1.5">
                        Sumber Dana (Kas / Bank Toko) <span class="text-error">*</span>
                    </label>
                    <select id="legacy_payment_account_id" name="payment_account_id" required
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Pilih Akun Pembayaran --</option>
                        @foreach($paymentAccounts as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->code }} - {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance ?? 0, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Metode Pembayaran --}}
                <div>
                    <label for="legacy_payment_method" class="block text-xs font-bold text-on-surface mb-1.5">
                        Metode Pembayaran
                    </label>
                    <select id="legacy_payment_method" name="payment_method"
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai / Cash</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                {{-- Nominal Cicilan --}}
                <div>
                    <label for="legacy_amount" class="block text-xs font-bold text-on-surface mb-1.5">
                        Nominal Pembayaran Cicilan (Rp) <span class="text-error">*</span>
                    </label>
                    <input type="number" id="legacy_amount" name="amount" min="1" step="1" required placeholder="Contoh: 10000000"
                           class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-extrabold focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-[11px] text-on-surface-variant mt-1" id="modalMaxHelp">Maksimum pembayaran sesuai sisa hutang.</p>
                </div>

                {{-- No Referensi --}}
                <div>
                    <label for="legacy_reference_number" class="block text-xs font-bold text-on-surface mb-1.5">
                        No. Referensi / Bukti Transfer
                    </label>
                    <input type="text" id="legacy_reference_number" name="reference_number" placeholder="Contoh: TF-BCA-20261023-01"
                           class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                {{-- Catatan --}}
                <div>
                    <label for="legacy_notes" class="block text-xs font-bold text-on-surface mb-1.5">
                        Catatan Tambahan
                    </label>
                    <textarea id="legacy_notes" name="notes" rows="2" placeholder="Keterangan tambahan cicilan..."
                              class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant">
                    <button type="button" onclick="closeLegacyPayModal()" class="px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold text-xs rounded-xl border-0 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl active:scale-95 transition-all shadow-xs border-0 cursor-pointer">
                        Simpan Pembayaran Cicilan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLegacyPayModal(debt) {
            const modal = document.getElementById('legacyPayModal');
            const form = document.getElementById('legacyPayForm');
            const subtitle = document.getElementById('modalSupplierSubtitle');
            const remainingText = document.getElementById('modalRemainingText');
            const amountInput = document.getElementById('legacy_amount');
            const maxHelp = document.getElementById('modalMaxHelp');

            const remaining = parseFloat(debt.remaining_amount) || 0;
            const formatted = new Intl.NumberFormat('id-ID').format(remaining);

            subtitle.textContent = debt.supplier_name + (debt.location ? ' - ' + debt.location : '') + ' (' + debt.code + ')';
            remainingText.textContent = 'Rp ' + formatted;
            amountInput.max = remaining;
            maxHelp.textContent = 'Maksimum nominal pembayaran: Rp ' + formatted;

            form.action = '/accounting/payables/legacy/' + debt.id + '/pay';

            modal.classList.remove('hidden');
        }

        function closeLegacyPayModal() {
            const modal = document.getElementById('legacyPayModal');
            modal.classList.add('hidden');
        }
    </script>
@endif

@endsection
