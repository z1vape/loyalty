<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z1 Vape Loyalty - Kasir & Manajemen Member</title>
    <!-- Google Fonts: Inter & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#006c49',
                            600: '#005a3c',
                            700: '#004730',
                            900: '#003322',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans min-h-screen flex flex-col antialiased selection:bg-brand-100 selection:text-brand-900">

    {{-- Top Navbar (InvenTrack / Z1 VapeOS Design System) --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-[1580px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                {{-- Logo & Brand Identification --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-[#006c49] via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                        <span class="material-symbols-outlined text-[22px]">loyalty</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-slate-900 tracking-tight leading-none">Z1 Vape Loyalty Card</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-brand-50 text-brand-700 border border-brand-100">Portal Kasir</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Manajemen Kartu & Data Member Digital</p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition border border-slate-200/80 no-underline shadow-2xs">
                        <span class="material-symbols-outlined text-[18px]">apps</span>
                        <span>Portal</span>
                    </a>
                    <button onclick="openRegisterModal()" class="px-4 py-2 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                        <span>+ DAFTAR MEMBER BARU</span>
                    </button>
                </div>

            </div>
        </div>
    </header>

    {{-- Flash Notifications --}}
    <div class="max-w-[1580px] w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-2xs">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                    <span>{{ session('success') }}</span>
                </span>
                @if(session('new_member_whatsapp'))
                    <a href="{{ session('new_member_whatsapp') }}" target="_blank" class="px-3 py-1 rounded-lg bg-[#006c49] text-white text-xs font-bold hover:bg-[#005a3c] transition shadow-xs">
                        Kirim Kartu ke WA Sekarang →
                    </a>
                @endif
            </div>
        @endif
        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-2 shadow-2xs">
                <span class="material-symbols-outlined text-rose-600 text-[18px]">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    {{-- Main Container --}}
    <main class="max-w-[1580px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-5 flex-1">

        {{-- Statistics Row (3 Clean Cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Stat 1: Total Member --}}
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Total Member Terdaftar</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                        {{ number_format($stats['total_members']) }}
                    </div>
                    <span class="text-[11px] text-emerald-600 font-medium mt-0.5 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Semua kartu aktif
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] flex items-center justify-center shrink-0 border border-emerald-100">
                    <span class="material-symbols-outlined text-2xl">groups</span>
                </div>
            </div>

            {{-- Stat 2: Total Poin --}}
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Total Poin Member</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 mt-1">
                        {{ number_format($stats['total_points']) }}
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium mt-0.5 block">
                        ≈ Rp {{ number_format($stats['total_points'] * 100, 0, ',', '.') }} potongan belanja
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <span class="material-symbols-outlined text-2xl">monetization_on</span>
                </div>
            </div>

            {{-- Stat 3: Format Kartu Digital --}}
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Format Kartu Digital</span>
                    <div class="text-base font-bold text-slate-900 mt-1 tracking-wide">
                        NAMA • NO MEMBER • QR
                    </div>
                    <span class="text-[11px] text-[#006c49] font-medium mt-0.5 block">
                        Desain Mecha Metallic Berkilau (3D Tilt)
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
                    <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                </div>
            </div>
        </div>

        {{-- SCANNER & QUICK SEARCH STATION --}}
        <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-2xs">
            <div class="max-w-2xl mx-auto text-center space-y-2.5">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[#006c49] border border-emerald-200 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-[#006c49] animate-ping"></span>
                    SCANNER KASIR AKTIF
                </div>
                <h2 class="text-xl font-bold text-slate-900">Scan Barcode / QR Pelanggan</h2>
                <p class="text-xs text-slate-500">Arahkan scanner kasir langsung ke QR Code di HP pelanggan atau ketik Nomor WhatsApp / Kode Member.</p>
                
                <div class="relative flex items-center mt-4">
                    <span class="material-symbols-outlined absolute left-4 text-slate-400 text-xl pointer-events-none">qr_code_scanner</span>
                    <input type="text" id="live_scanner_input" autofocus placeholder="Scan QR atau ketik: VP-XXXXX / 08123..."
                        class="w-full pl-11 pr-24 py-3 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 text-sm rounded-xl transition-all font-mono text-slate-800 placeholder:text-slate-400 shadow-inner"
                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); doLiveSearch(); }" />
                    <button type="button" onclick="doLiveSearch()" class="absolute right-2 px-4 py-2 bg-[#006c49] hover:bg-[#005a3c] text-white font-bold text-xs rounded-lg transition shadow-xs cursor-pointer">
                        CARI
                    </button>
                </div>
            </div>

            {{-- Live Result Box (AJAX Member Card Preview) --}}
            <div id="scan_result_box" class="hidden mt-6 pt-5 border-t border-slate-200 max-w-xl mx-auto">
                <div class="p-5 rounded-xl bg-slate-50 border border-emerald-300 relative shadow-xs">
                    <button onclick="closeScanResult()" class="absolute right-3 top-3 text-slate-400 hover:text-slate-700 p-1 rounded-md hover:bg-slate-200/60 transition cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-[#006c49] bg-emerald-100/70 px-2 py-0.5 rounded border border-emerald-200" id="res_member_code"></span>
                            <h3 class="text-base font-bold text-slate-900 mt-1.5" id="res_name"></h3>
                            <p class="text-xs text-slate-500 font-mono" id="res_phone"></p>
                        </div>
                        <span id="res_tier_badge" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"></span>
                    </div>

                    <div class="my-3.5 bg-white p-3 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-500 uppercase font-semibold block">Poin Belanja</span>
                            <span class="text-xl font-bold text-amber-600 font-mono" id="res_points"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-500 uppercase font-semibold block">Nilai Diskon</span>
                            <span class="text-sm font-bold text-slate-700 font-mono" id="res_points_rp"></span>
                        </div>
                    </div>

                    {{-- Fast Action Buttons in Scan Result --}}
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-200">
                        <button onclick="promptAddPoints()" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
                            ⭐ + Poin Belanja
                        </button>

                        <button onclick="promptRedeemPoints()" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
                            ✂️ Tukar Poin Diskon
                        </button>

                        <a id="res_card_url" href="#" target="_blank" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold flex items-center gap-1 transition no-underline">
                            👁️ Buka Kartu Digital
                        </a>

                        <a id="res_wa_url" href="#" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-[#006c49] border border-emerald-200 hover:bg-emerald-100 text-xs font-semibold flex items-center gap-1 transition no-underline">
                            📲 Kirim Link WA
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- CUSTOMERS TABLE --}}
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200/80">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Member Loyalty</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Total {{ $customers->total() }} member aktif</p>
                </div>

                {{-- Search Filter --}}
                <form action="{{ route('loyalty.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / no WA / kode..."
                        class="px-3.5 py-1.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#006c49] focus:ring-1 focus:ring-emerald-200 transition" />
                    <button type="submit" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg transition cursor-pointer">
                        Filter
                    </button>
                    @if($search)
                        <a href="{{ route('loyalty.index') }}" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition no-underline">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase text-[11px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Nama Pelanggan</th>
                            <th class="py-3 px-4">No. Member</th>
                            <th class="py-3 px-4">Level Tier</th>
                            <th class="py-3 px-4">Poin Belanja</th>
                            <th class="py-3 px-4">Rasa Favorit</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($customers as $c)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $c->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $c->phone }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono font-bold text-[#006c49] text-xs px-2 py-0.5 bg-emerald-50 border border-emerald-200 rounded tracking-wider">
                                        {{ $c->member_code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($c->tier === 'Master')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            ★ MASTER
                                        </span>
                                    @elseif($c->tier === 'Cloud Chaser')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                            ⚡ CLOUD CHASER
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-[#006c49] border border-emerald-200">
                                            🌿 ROOKIE
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono font-bold text-amber-600 text-sm">{{ number_format($c->points) }}</span>
                                    <span class="text-[11px] text-slate-400 block">≈ Rp {{ number_format($c->points * 100, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $c->favorite_flavor ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- WhatsApp Share --}}
                                        <a href="{{ $c->whatsapp_share_url }}" target="_blank" title="Kirim Link Kartu ke WA" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#006c49] border border-emerald-200 hover:bg-emerald-100 font-semibold text-xs transition flex items-center gap-1 no-underline">
                                            <span class="material-symbols-outlined text-[14px]">send</span>
                                            <span>Kirim WA</span>
                                        </a>

                                        {{-- View Card --}}
                                        <a href="{{ $c->card_url }}" target="_blank" title="Buka Kartu Digital" class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 font-semibold text-xs transition flex items-center gap-1 no-underline">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            <span>Buka Kartu</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    Belum ada data pelanggan. Klik "+ DAFTAR MEMBER BARU" untuk mendaftarkan member pertama!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
                <div class="p-3.5 border-t border-slate-200 bg-slate-50/50">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

    </main>

    {{-- MODAL DAFTAR MEMBER BARU (Clean InvenTrack Design) --}}
    <div id="registerModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">person_add</span>
                    Daftar Member Baru
                </h3>
                <button type="button" onclick="closeRegisterModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form action="{{ route('loyalty.register') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso"
                        class="w-full px-3.5 py-2.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 rounded-xl text-sm text-slate-900 transition font-medium placeholder:text-slate-400" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                        Nomor WhatsApp <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" name="phone" required placeholder="Contoh: 081234567890"
                        class="w-full px-3.5 py-2.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 rounded-xl text-sm text-slate-900 transition font-mono placeholder:text-slate-400" />
                    <p class="text-[11px] text-slate-400 mt-1">Kartu digital member & link akses akan otomatis dikirimkan ke nomor ini.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                        Rasa / Liquid Favorit (Opsional)
                    </label>
                    <input type="text" name="favorite_flavor" placeholder="Contoh: Fruity dingin 3mg / Creamy"
                        class="w-full px-3.5 py-2.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 rounded-xl text-sm text-slate-900 transition placeholder:text-slate-400" />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeRegisterModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-[#006c49] hover:bg-[#005a3c] text-white rounded-xl text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                        Simpan &amp; Buat Kartu
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- JAVASCRIPT LOGIC --}}
    <script>
        let currentScannedCustomerId = null;

        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
        }
        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
        }
        function closeScanResult() {
            document.getElementById('scan_result_box').classList.add('hidden');
            document.getElementById('live_scanner_input').value = '';
            document.getElementById('live_scanner_input').focus();
        }

        function doLiveSearch() {
            const query = document.getElementById('live_scanner_input').value.trim();
            if (!query) return;

            fetch(`{{ route('loyalty.search') }}?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.found) {
                        renderScanResult(data.customer);
                    } else {
                        if (confirm('Member tidak ditemukan. Daftarkan sebagai member baru sekarang?')) {
                            openRegisterModal();
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Gagal mencari member.');
                });
        }

        function renderScanResult(c) {
            currentScannedCustomerId = c.id;
            document.getElementById('scan_result_box').classList.remove('hidden');

            document.getElementById('res_member_code').innerText = c.member_code;
            document.getElementById('res_name').innerText = c.name;
            document.getElementById('res_phone').innerText = c.phone;
            document.getElementById('res_points').innerText = `${c.points.toLocaleString()} Poin`;
            document.getElementById('res_points_rp').innerText = `Rp ${(c.points * 100).toLocaleString('id-ID')}`;

            const badge = document.getElementById('res_tier_badge');
            badge.innerText = c.tier;
            if (c.tier === 'Master') {
                badge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200';
            } else if (c.tier === 'Cloud Chaser') {
                badge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200';
            } else {
                badge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-[#006c49] border border-emerald-200';
            }

            document.getElementById('res_card_url').href = c.card_url;
            document.getElementById('res_wa_url').href = c.whatsapp_url;
        }

        function promptAddPoints() {
            if (!currentScannedCustomerId) return;
            const points = prompt('Masukkan jumlah poin yang ingin ditambahkan:', '20');
            if (points && parseInt(points) > 0) {
                postAction(currentScannedCustomerId, 'add_points', { points: parseInt(points) });
            }
        }

        function promptRedeemPoints() {
            if (!currentScannedCustomerId) return;
            const points = prompt('Masukkan jumlah poin yang ingin ditukar:', '50');
            if (points && parseInt(points) > 0) {
                postAction(currentScannedCustomerId, 'redeem_points', { points: parseInt(points) });
            }
        }

        function postAction(customerId, action, extra = {}) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/customers/${customerId}/action`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const act = document.createElement('input');
            act.type = 'hidden';
            act.name = 'action';
            act.value = action;
            form.appendChild(act);

            for (const key in extra) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = key;
                inp.value = extra[key];
                form.appendChild(inp);
            }

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
