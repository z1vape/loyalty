<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $transfer->transfer_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 space-y-6">
        <div class="flex items-center justify-between no-print pb-4 border-b border-slate-200">
            <a href="{{ route('stock-transfers.show', $transfer->id) }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">&larr; Kembali ke Detail</a>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold cursor-pointer">
                Cetak Surat Jalan
            </button>
        </div>

        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight uppercase">Surat Jalan Mutasi Barang</h1>
                <p class="text-xs text-slate-600 mt-0.5">Z1 VapeOS • Dokumen Resmi Perpindahan Persediaan Antar Cabang</p>
            </div>
            <div class="text-right">
                <p class="font-mono text-lg font-bold">{{ $transfer->transfer_number }}</p>
                <p class="text-xs text-slate-600">Tanggal: {{ $transfer->transfer_date?->format('d M Y') }}</p>
                <p class="text-xs font-bold uppercase mt-1">Status: {{ $transfer->status_label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <p class="font-bold text-slate-500 uppercase">Cabang Pengirim (Asal)</p>
                <p class="text-sm font-extrabold mt-1">{{ $transfer->fromStore?->name }}</p>
                <p class="text-slate-600 mt-0.5">{{ $transfer->fromStore?->address ?? '-' }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <p class="font-bold text-slate-500 uppercase">Cabang Tujuan (Penerima)</p>
                <p class="text-sm font-extrabold mt-1">{{ $transfer->toStore?->name }}</p>
                <p class="text-slate-600 mt-0.5">{{ $transfer->toStore?->address ?? '-' }}</p>
            </div>
        </div>

        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-y border-slate-300 bg-slate-50 uppercase font-bold">
                    <th class="py-2.5 px-3">No</th>
                    <th class="py-2.5 px-3">SKU</th>
                    <th class="py-2.5 px-3">Nama Barang</th>
                    <th class="py-2.5 px-3 text-center">Qty Kirim</th>
                    <th class="py-2.5 px-3 text-center">Qty Terima</th>
                    <th class="py-2.5 px-3">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($transfer->items as $idx => $item)
                    <tr>
                        <td class="py-2.5 px-3 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-3 font-mono font-bold">{{ $item->sourceProduct?->sku ?: '-' }}</td>
                        <td class="py-2.5 px-3 font-semibold">{{ $item->sourceProduct?->name }}</td>
                        <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $item->quantity_sent ?: $item->quantity_requested }}</td>
                        <td class="py-2.5 px-3 text-center font-mono">{{ $transfer->status === 'received' ? $item->quantity_received : '____' }}</td>
                        <td class="py-2.5 px-3">{{ $item->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($transfer->notes)
            <div class="text-xs border-t border-slate-200 pt-3">
                <strong>Catatan:</strong> {{ $transfer->notes }}
            </div>
        @endif

        <div class="grid grid-cols-3 gap-6 pt-8 text-center text-xs">
            <div>
                <p class="font-bold">Disiapkan / Dikirim Oleh</p>
                <div class="h-16"></div>
                <p class="font-semibold border-t border-slate-400 pt-1">({{ $transfer->sender?->name ?? $transfer->creator?->name ?? '....................' }})</p>
            </div>
            <div>
                <p class="font-bold">Kurir / Pembawa Barang</p>
                <div class="h-16"></div>
                <p class="font-semibold border-t border-slate-400 pt-1">(....................)</p>
            </div>
            <div>
                <p class="font-bold">Diterima Cabang Tujuan</p>
                <div class="h-16"></div>
                <p class="font-semibold border-t border-slate-400 pt-1">({{ $transfer->receiver?->name ?? '....................' }})</p>
            </div>
        </div>
    </div>
</body>
</html>
