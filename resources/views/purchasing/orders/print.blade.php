<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $pr->pr_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8">

    {{-- Action Bar (Hidden on print) --}}
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <a href="{{ route('purchasing.orders.show', $pr->id) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Rincian Order
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow transition-all cursor-pointer">
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- Document Sheet --}}
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-3xl shadow-md border border-slate-200 print:border-none print:shadow-none print:p-0">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-6 mb-6">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 uppercase">Z1 VAPE STORE</h2>
                <p class="text-xs text-slate-600 font-medium mt-1">{{ $pr->store?->name ?? 'Z1 Vape Store' }}</p>
                <p class="text-[11px] text-slate-500 max-w-sm mt-0.5">{{ $pr->store?->address ?? 'Pusat Distribusi & Operasional Toko Retail' }}</p>
                @if($pr->store?->phone)
                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">Telp: {{ $pr->store->phone }}</p>
                @endif
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-black uppercase rounded-lg tracking-wider mb-2">
                    PURCHASE ORDER
                </span>
                <h3 class="text-xl font-bold font-mono text-slate-900">{{ $pr->pr_number }}</h3>
                <p class="text-xs text-slate-500 mt-1">Tanggal: <strong>{{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}</strong></p>
                <p class="text-xs text-slate-500">Status: <strong class="uppercase">{{ $pr->status }}</strong></p>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 gap-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 mb-6 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Pemohon / Pengaju:</span>
                <p class="font-bold text-slate-800 mt-0.5">{{ $pr->user?->name ?? 'Staff Toko' }}</p>
                <span class="text-[11px] text-slate-500">Dibuat: {{ $pr->created_at ? $pr->created_at->format('d/m/Y H:i') . ' WIB' : '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Persetujuan (Purchasing):</span>
                <p class="font-bold text-slate-800 mt-0.5">{{ $pr->approvedByUser?->name ?? ($pr->status === 'approved' ? 'Manajer Purchasing' : 'Menunggu Review') }}</p>
                <span class="text-[11px] text-slate-500">Tanggal: {{ $pr->approved_at ? $pr->approved_at->format('d/m/Y H:i') . ' WIB' : '-' }}</span>
            </div>
            @if($pr->notes)
            <div class="col-span-2 pt-2 border-t border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Catatan Pengadaan:</span>
                <p class="text-slate-700 italic mt-0.5">"{{ $pr->notes }}"</p>
            </div>
            @endif
        </div>

        <!-- Table of Items -->
        <table class="w-full text-xs text-left border-collapse mb-6">
            <thead>
                <tr class="border-b-2 border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-600 bg-slate-50">
                    <th class="py-3 px-3 w-10 text-center">No</th>
                    <th class="py-3 px-3">Deskripsi Barang / Produk</th>
                    <th class="py-3 px-3">SKU</th>
                    <th class="py-3 px-3 text-center">Qty</th>
                    <th class="py-3 px-3 text-right">Harga Satuan (Est.)</th>
                    <th class="py-3 px-3 text-right">Total Nilai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($pr->items as $idx => $item)
                @php
                    $subtotal = $item->item_price * $item->quantity_requested;
                @endphp
                <tr>
                    <td class="py-3 px-3 text-center font-bold text-slate-500">{{ $idx + 1 }}</td>
                    <td class="py-3 px-3">
                        <span class="font-bold text-slate-900 block">{{ $item->item_name }}</span>
                        @if($item->notes)
                            <span class="text-[10px] text-slate-500 italic block">{{ $item->notes }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 font-mono text-slate-600">{{ $item->item_sku }}</td>
                    <td class="py-3 px-3 text-center font-bold text-slate-800">{{ $item->quantity_requested }} pcs</td>
                    <td class="py-3 px-3 text-right font-mono text-slate-700">Rp {{ number_format($item->item_price, 0, ',', '.') }}</td>
                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-slate-400">Tidak ada item dalam purchase order ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 border-slate-800 font-bold bg-slate-50">
                <tr>
                    <td colspan="3" class="py-3 px-3 text-right uppercase tracking-wider text-[11px] text-slate-700">Total Akumulasi:</td>
                    <td class="py-3 px-3 text-center font-black text-slate-900">{{ $totalUnits }} pcs</td>
                    <td></td>
                    <td class="py-3 px-3 text-right font-mono text-sm font-black text-emerald-800">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Row -->
        <div class="grid grid-cols-3 gap-6 pt-8 mt-8 border-t border-slate-200 text-center text-xs">
            <div>
                <p class="text-slate-500 font-medium">Diajukan Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">{{ $pr->user?->name ?? 'Staff Toko' }}</p>
                <span class="text-[10px] text-slate-400">Pemohon Cabang</span>
            </div>

            <div>
                <p class="text-slate-500 font-medium">Disetujui Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">{{ $pr->approvedByUser?->name ?? 'Purchasing / Manajer' }}</p>
                <span class="text-[10px] text-slate-400">Head of Purchasing</span>
            </div>

            <div>
                <p class="text-slate-500 font-medium">Diterima / Supplier,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">( ........................................ )</p>
                <span class="text-[10px] text-slate-400">Petugas Gudang / Ekspedisi</span>
            </div>
        </div>

    </div>

</body>
</html>

