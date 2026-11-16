<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $po->po_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
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
        <a href="{{ route('purchasing.purchase-orders.show', $po->id) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Rincian Purchase Order
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow transition-all cursor-pointer">
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- PO Document Sheet --}}
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-3xl shadow-md border border-slate-200 print:border-none print:shadow-none print:p-0">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-6 mb-6">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 uppercase">Z1 VAPE STORE</h2>
                <p class="text-xs text-slate-600 font-bold mt-1">Divisi Pengadaan & Purchasing</p>
                <p class="text-[11px] text-slate-500 max-w-sm mt-0.5">{{ $po->store?->address ?? 'Pusat Distribusi & Operasional Retail' }}</p>
                @if($po->store?->phone)
                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">Telp: {{ $po->store->phone }}</p>
                @endif
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-900 text-xs font-black uppercase rounded-lg tracking-wider mb-2">
                    SURAT PESANAN (PURCHASE ORDER)
                </span>
                <h3 class="text-xl font-bold font-mono text-slate-900">{{ $po->po_number }}</h3>
                <p class="text-xs text-slate-500 mt-1">Tanggal: <strong>{{ \Carbon\Carbon::parse($po->po_date)->isoFormat('D MMMM Y') }}</strong></p>
                @if($po->purchaseRequest)
                    <p class="text-[11px] text-slate-500 font-mono">Ref PR: {{ $po->purchaseRequest->pr_number }}</p>
                @endif
            </div>
        </div>

        <!-- Supplier & Delivery Info Grid -->
        <div class="grid grid-cols-2 gap-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 mb-6 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Kepada Supplier / Vendor:</span>
                <h4 class="text-sm font-extrabold text-slate-900 mt-1">{{ $po->supplier_name }}</h4>
                <p class="text-slate-600 text-[11px] mt-0.5">Distributor & Penyedia Produk</p>
                <div class="mt-2 text-[11px] text-slate-600">
                    <span>Syarat Pembayaran: <strong class="uppercase text-slate-800">{{ $po->payment_method }}</strong> @if($po->payment_term_days > 0) ({{ $po->payment_term_days }} hari) @endif</span>
                </div>
            </div>

            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Alamat Kirim / Cabang Tujuan:</span>
                <h4 class="text-sm font-extrabold text-slate-900 mt-1">{{ $po->store?->name ?? 'Gudang Utama' }}</h4>
                <p class="text-slate-600 text-[11px] mt-0.5">{{ $po->store?->address ?? 'Area Toko Z1 Vape Store' }}</p>
                @if($po->expected_date)
                    <div class="mt-2 text-[11px] text-slate-600">
                        <span>Estimasi Tiba: <strong class="text-slate-800">{{ $po->expected_date->format('d/m/Y') }}</strong></span>
                    </div>
                @endif
            </div>

            @if($po->notes)
            <div class="col-span-2 pt-2 border-t border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Instruksi Pengiriman & Catatan:</span>
                <p class="text-slate-700 italic mt-0.5">"{{ $po->notes }}"</p>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="w-full text-xs text-left border-collapse mb-6">
            <thead>
                <tr class="border-b-2 border-slate-800 text-[10px] font-black uppercase tracking-wider text-slate-600 bg-slate-50">
                    <th class="py-3 px-3 w-10 text-center">No</th>
                    <th class="py-3 px-3">Nama Produk / Deskripsi Barang</th>
                    <th class="py-3 px-3">SKU</th>
                    <th class="py-3 px-3 text-center">Qty Dipesan</th>
                    <th class="py-3 px-3 text-right">Harga Satuan (Rp)</th>
                    <th class="py-3 px-3 text-right">Subtotal (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($po->items as $idx => $item)
                <tr>
                    <td class="py-3 px-3 text-center font-bold text-slate-500">{{ $idx + 1 }}</td>
                    <td class="py-3 px-3">
                        <span class="font-bold text-slate-900 block">{{ $item->item_name }}</span>
                        @if($item->notes)
                            <span class="text-[10px] text-slate-500 italic block">{{ $item->notes }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 font-mono text-slate-600">{{ $item->item_sku }}</td>
                    <td class="py-3 px-3 text-center font-bold text-slate-900">{{ $item->quantity_ordered }} pcs</td>
                    <td class="py-3 px-3 text-right font-mono text-slate-700">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-slate-800 font-bold bg-slate-50">
                <tr>
                    <td colspan="3" class="py-3 px-3 text-right uppercase tracking-wider text-[11px] text-slate-700">Total Akumulasi Pembelian:</td>
                    <td class="py-3 px-3 text-center font-black text-slate-900">{{ $totalUnitsOrdered }} pcs</td>
                    <td></td>
                    <td class="py-3 px-3 text-right font-mono text-sm font-black text-blue-900">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-6 pt-8 mt-8 border-t border-slate-200 text-center text-xs">
            <div>
                <p class="text-slate-500 font-medium">Dibuat Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">{{ $po->creator?->name ?? 'Staff Purchasing' }}</p>
                <span class="text-[10px] text-slate-400">Purchasing Officer</span>
            </div>

            <div>
                <p class="text-slate-500 font-medium">Disetujui Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">Head of Purchasing / Manager</p>
                <span class="text-[10px] text-slate-400">Pimpinan Operasional</span>
            </div>

            <div>
                <p class="text-slate-500 font-medium">Dikonfirmasi Supplier,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 border-t border-slate-300 pt-1">( {{ $po->supplier_name }} )</p>
                <span class="text-[10px] text-slate-400">Tanda Tangan & Stempel Vendor</span>
            </div>
        </div>

    </div>

</body>
</html>

