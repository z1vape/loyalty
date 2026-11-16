<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z1 Vape Loyalty - Kasir & Manajemen Member</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #07090e;
            color: #f1f5f9;
        }
        .font-orbitron {
            font-family: 'Orbitron', sans-serif;
        }
        .font-mono-card {
            font-family: 'Space Grotesk', monospace;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    {{-- Top Navbar --}}
    <header class="glass-panel sticky top-0 z-40 border-b border-slate-800 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-200 to-slate-400 flex items-center justify-center shadow-lg shadow-white/10">
                <span class="font-orbitron font-black text-slate-950 text-base">Z1</span>
            </div>
            <div>
                <h1 class="text-base font-black tracking-wider text-white font-orbitron uppercase">
                    Z1 VAPE LOYALTY CARD
                </h1>
                <p class="text-xs text-slate-400">Portal Kasir & Data Member Digital</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('portal') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold flex items-center gap-1.5 transition border border-slate-700 no-underline">
                <span class="material-symbols-outlined text-base">apps</span>
                Portal
            </a>
            <button onclick="openRegisterModal()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 text-xs font-black hover:opacity-90 shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition">
                <span class="material-symbols-outlined text-base">person_add</span>
                + DAFTAR MEMBER BARU
            </button>
        </div>
    </header>

    {{-- Flash Messages --}}
    <div class="max-w-7xl w-full mx-auto px-6 mt-4">
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    {{ session('success') }}
                </span>
                @if(session('new_member_whatsapp'))
                    <a href="{{ session('new_member_whatsapp') }}" target="_blank" class="px-3 py-1 rounded-lg bg-emerald-500 text-slate-950 text-xs font-bold hover:bg-emerald-400 transition">
                        Kirim Kartu ke WA Sekarang →
                    </a>
                @endif
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-sm font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Main Container --}}
    <main class="max-w-7xl w-full mx-auto px-6 py-6 space-y-6 flex-1">

        {{-- Statistics Row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Total Member Terdaftar</span>
                    <span class="material-symbols-outlined text-cyan-400 text-lg">groups</span>
                </div>
                <div class="text-3xl font-extrabold text-white mt-2 font-mono-card">
                    {{ number_format($stats['total_members']) }}
                </div>
                <span class="text-[11px] text-slate-400 mt-1 block">Semua kartu aktif</span>
            </div>

            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Total Poin Member</span>
                    <span class="material-symbols-outlined text-amber-400 text-lg">monetization_on</span>
                </div>
                <div class="text-3xl font-extrabold text-amber-400 mt-2 font-mono-card">
                    {{ number_format($stats['total_points']) }}
                </div>
                <span class="text-[11px] text-slate-400 mt-1 block">≈ Rp {{ number_format($stats['total_points'] * 100, 0, ',', '.') }} potongan belanja</span>
            </div>

            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Format Kartu Digital</span>
                    <span class="material-symbols-outlined text-emerald-400 text-lg">qr_code_2</span>
                </div>
                <div class="text-lg font-bold text-slate-200 mt-2 font-orbitron">
                    NAMA • NO MEMBER • QR
                </div>
                <span class="text-[11px] text-emerald-400 mt-1 block">Desain Mecha Metallic Berkilau</span>
            </div>
        </div>

        {{-- SCANNER & QUICK SEARCH STATION --}}
        <div class="glass-panel p-6 rounded-3xl border border-slate-700 bg-gradient-to-b from-slate-900/90 to-slate-900/60 shadow-xl">
            <div class="max-w-2xl mx-auto text-center space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    SCANNER KASIR AKTIF
                </div>
                <h2 class="text-xl font-extrabold text-white">Scan Barcode / QR Pelanggan</h2>
                <p class="text-xs text-slate-400">Arahkan scanner kasir langsung ke QR Code di HP pelanggan atau ketik Nomor WhatsApp / Kode Member.</p>
                
                <div class="relative flex items-center mt-4">
                    <span class="material-symbols-outlined absolute left-4 text-slate-400 text-2xl">qr_code_scanner</span>
                    <input type="text" id="live_scanner_input" autofocus placeholder="Scan QR atau ketik: VP-XXXXX / 08123..."
                        class="w-full pl-12 pr-28 py-3.5 bg-slate-950/80 border border-slate-700 rounded-2xl text-base text-white focus:outline-none focus:border-emerald-500 transition font-mono shadow-inner"
                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); doLiveSearch(); }" />
                    <button type="button" onclick="doLiveSearch()" class="absolute right-2 px-5 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs rounded-xl transition">
                        CARI
                    </button>
                </div>
            </div>

            {{-- Live Result Box (AJAX Modal Card) --}}
            <div id="scan_result_box" class="hidden mt-6 pt-6 border-t border-slate-800 max-w-xl mx-auto">
                <div class="p-5 rounded-2xl bg-slate-950/90 border border-emerald-500/40 relative">
                    <button onclick="closeScanResult()" class="absolute right-3 top-3 text-slate-400 hover:text-white p-1">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-emerald-400" id="res_member_code"></span>
                            <h3 class="text-lg font-black text-white mt-0.5" id="res_name"></h3>
                            <p class="text-xs text-slate-400 font-mono" id="res_phone"></p>
                        </div>
                        <span id="res_tier_badge" class="px-3 py-1 rounded-full text-xs font-bold uppercase font-orbitron"></span>
                    </div>

                    <div class="my-4 bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Poin Belanja</span>
                            <span class="text-2xl font-black text-amber-400 font-mono-card" id="res_points"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Nilai Diskon</span>
                            <span class="text-sm font-bold text-slate-200 font-mono-card" id="res_points_rp"></span>
                        </div>
                    </div>

                    {{-- Fast Action Buttons in Scan Result --}}
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-800/80">
                        <button onclick="promptAddPoints()" class="px-3 py-2 rounded-xl bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 text-xs font-bold flex items-center gap-1 transition">
                            ⭐ + Poin Belanja
                        </button>

                        <button onclick="promptRedeemPoints()" class="px-3 py-2 rounded-xl bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 text-xs font-bold flex items-center gap-1 transition">
                            ✂️ Tukar Poin Diskon
                        </button>

                        <a id="res_card_url" href="#" target="_blank" class="px-3 py-2 rounded-xl bg-slate-800 text-slate-200 hover:bg-slate-700 text-xs font-bold flex items-center gap-1 transition">
                            👁️ Buka Kartu Digital
                        </a>

                        <a id="res_wa_url" href="#" target="_blank" class="px-3 py-2 rounded-xl bg-emerald-600/30 text-emerald-300 hover:bg-emerald-600/40 text-xs font-bold flex items-center gap-1 transition">
                            📲 Kirim Link WA
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- CUSTOMERS TABLE --}}
        <div class="glass-panel rounded-3xl overflow-hidden border border-slate-800">
            <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80">
                <div>
                    <h3 class="text-base font-bold text-white font-orbitron">Daftar Member Loyalty</h3>
                    <p class="text-xs text-slate-400">Total {{ $customers->total() }} member aktif</p>
                </div>

                {{-- Search Filter --}}
                <form action="{{ route('loyalty.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / no WA / kode..."
                        class="px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500" />
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white rounded-xl transition">
                        Filter
                    </button>
                    @if($search)
                        <a href="{{ route('loyalty.index') }}" class="px-3 py-2 bg-slate-800 hover:bg-rose-900 text-slate-400 hover:text-white text-xs font-bold rounded-xl transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/50 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Nama Pelanggan</th>
                            <th class="py-3.5 px-4">No. Member</th>
                            <th class="py-3.5 px-4">Level Tier</th>
                            <th class="py-3.5 px-4">Poin Belanja</th>
                            <th class="py-3.5 px-4">Rasa Favorit</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($customers as $c)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-sm">{{ $c->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $c->phone }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono-card font-bold text-emerald-400 text-sm tracking-wider">{{ $c->member_code }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($c->tier === 'Master')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/15 text-purple-400 border border-purple-500/30 font-orbitron">★ MASTER</span>
                                    @elseif($c->tier === 'Cloud Chaser')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/15 text-cyan-400 border border-cyan-500/30 font-orbitron">⚡ CLOUD CHASER</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-700/40 text-slate-300 border border-slate-600/50 font-orbitron">🌿 ROOKIE</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono-card font-bold text-amber-400 text-sm">{{ number_format($c->points) }}</span>
                                    <span class="text-[10px] text-slate-400 block">≈ Rp {{ number_format($c->points * 100, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-300">
                                    {{ $c->favorite_flavor ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- WhatsApp Share --}}
                                        <a href="{{ $c->whatsapp_share_url }}" target="_blank" title="Kirim Link Kartu ke WA" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 font-bold transition flex items-center gap-1">
                                            <span>📲</span> Kirim WA
                                        </a>

                                        {{-- View Card --}}
                                        <a href="{{ $c->card_url }}" target="_blank" title="Buka Kartu Digital" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 font-bold transition flex items-center gap-1">
                                            <span>👁️</span> Buka Kartu
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    Belum ada data pelanggan. Klik "+ DAFTAR MEMBER BARU" untuk mulai!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

    </main>

    {{-- MODAL DAFTAR MEMBER BARU --}}
    <div id="registerModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="glass-panel bg-slate-900 border border-slate-700 rounded-3xl w-full max-w-md p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2 font-orbitron">
                    <span class="material-symbols-outlined text-emerald-400">person_add</span>
                    Daftar Member Baru
                </h3>
                <button type="button" onclick="closeRegisterModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form action="{{ route('loyalty.register') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">
                        Nama Lengkap <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500 transition font-medium" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">
                        Nomor WhatsApp <span class="text-rose-400">*</span>
                    </label>
                    <input type="tel" name="phone" required placeholder="Contoh: 081234567890"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500 transition font-medium" />
                    <p class="text-[11px] text-slate-400 mt-1">Kartu digital member akan dikirimkan ke nomor WA ini.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">
                        Rasa / Liquid Favorit (Opsional)
                    </label>
                    <input type="text" name="favorite_flavor" placeholder="Contoh: Fruity dingin 3mg / Creamy"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500 transition" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeRegisterModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:bg-slate-800 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-xl text-xs font-black shadow-md transition font-orbitron">
                        Simpan & Buat Kartu
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
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase bg-purple-500/20 text-purple-400 border border-purple-500/40 font-orbitron';
            } else if (c.tier === 'Cloud Chaser') {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase bg-cyan-500/20 text-cyan-400 border border-cyan-500/40 font-orbitron';
            } else {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase bg-slate-700/40 text-slate-300 border border-slate-600/50 font-orbitron';
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
