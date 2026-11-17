<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z1 Vape Loyalty - Manajemen Member & Kasir</title>
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

        /* Sidebar Minimized Mode Styling (Persis Human Resources) */
        #loyalty-sidebar.is-minimized {
            width: 4.75rem !important; /* 76px */
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        #loyalty-sidebar.is-minimized .sidebar-brand-text,
        #loyalty-sidebar.is-minimized .loy-section-title,
        #loyalty-sidebar.is-minimized .loy-nav-text,
        #loyalty-sidebar.is-minimized .loy-toggle-label,
        #loyalty-sidebar.is-minimized .loy-portal-label,
        #loyalty-sidebar.is-minimized .loy-badge-pill {
            display: none !important;
        }
        #loyalty-sidebar.is-minimized .sidebar-mini-text {
            display: block !important;
        }
        #loyalty-sidebar.is-minimized .loy-section-divider {
            display: block !important;
        }
        #loyalty-sidebar.is-minimized .loy-nav-item {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #loyalty-sidebar.is-minimized .loy-toggle-icon {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans min-h-screen flex antialiased selection:bg-brand-100 selection:text-brand-900">

    {{-- SIDEBAR KIRI (Persis Human Resources) --}}
    <aside id="loyalty-sidebar" class="w-64 xl:w-72 bg-white border-r border-slate-200/90 shrink-0 sticky top-0 h-screen flex flex-col justify-between p-4 z-30 shadow-xs transition-all duration-200">
        <div class="space-y-4">
            {{-- Header Brand Box (Clean text-only persis Human Resources) --}}
            <div id="sidebar-brand-box" class="px-3 pt-1 pb-3.5 border-b border-slate-100">
                <div class="sidebar-brand-text">
                    <h1 class="text-xs font-black text-slate-900 uppercase tracking-wider leading-tight">MEMBER LOYALTY</h1>
                    <p class="text-[10px] text-slate-500 font-medium leading-tight mt-0.5">Z1 Digital Membership</p>
                </div>
                <div class="sidebar-mini-text font-black text-xs text-[#006c49] hidden text-center">LOY</div>
            </div>

            {{-- Navigation Menu Items --}}
            <nav class="space-y-1 text-xs" id="sidebarMenu">
                {{-- 1. Dashboard Kasir (Top item aktif style persis Dashboard HR) --}}
                <button type="button" onclick="switchTab('dashboard')" id="nav-btn-dashboard" title="Dashboard Kasir"
                    class="loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer {{ $activeTab === 'dashboard' ? 'bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">dashboard</span>
                    <span class="truncate loy-nav-text">Dashboard Kasir</span>
                </button>

                {{-- Section 1: MANAJEMEN MEMBER --}}
                <div class="loy-section-title pt-3 pb-1 px-3 text-[10px] font-bold text-slate-700 uppercase tracking-wider">Manajemen Member</div>
                <div class="loy-section-divider my-2 border-t border-slate-200/60 hidden"></div>

                {{-- Member Loyalty (Daftar) --}}
                <button type="button" onclick="switchTab('members')" id="nav-btn-members" title="Daftar Member"
                    class="loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer {{ $activeTab === 'members' ? 'bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[19px] shrink-0">how_to_reg</span>
                        <span class="truncate loy-nav-text">Daftar Member</span>
                    </span>
                    <span class="loy-badge-pill px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        {{ $totalCustomers }}
                    </span>
                </button>

                {{-- Registrasi Member Baru (Modal Quick Access) --}}
                <button type="button" onclick="openRegisterModal()" title="Registrasi Member Baru"
                    class="loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium">
                    <span class="material-symbols-outlined text-[19px] shrink-0">person_add</span>
                    <span class="truncate loy-nav-text">Registrasi Member</span>
                </button>

                {{-- Section 2: PENGATURAN & TIER --}}
                <div class="loy-section-title pt-3 pb-1 px-3 text-[10px] font-bold text-slate-700 uppercase tracking-wider">Pengaturan &amp; Tier</div>
                <div class="loy-section-divider my-2 border-t border-slate-200/60 hidden"></div>

                {{-- Tier & Poin --}}
                <button type="button" onclick="switchTab('tiers')" id="nav-btn-tiers" title="Pengaturan Tier & Poin"
                    class="loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer {{ $activeTab === 'tiers' ? 'bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[19px] shrink-0">military_tech</span>
                        <span class="truncate loy-nav-text">Tier &amp; Poin</span>
                    </span>
                    <span class="loy-badge-pill px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $tiers->count() }} Tier
                    </span>
                </button>
            </nav>
        </div>

        {{-- Footer Sidebar: Toggle Minimize / Expand (Persis seperti Human Resources) --}}
        <div class="loy-footer-box mt-auto pt-3 border-t border-slate-200/70 space-y-2">
            <button type="button" onclick="toggleLoyaltySidebar()" title="Minimize / Perluas Sidebar"
                class="loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl bg-[#eff4fe] hover:bg-[#e4edfd] text-slate-700 transition-all cursor-pointer border border-blue-100 shadow-2xs">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] shrink-0">dock_to_left</span>
                    <span class="truncate loy-toggle-label text-[11px] font-bold">Minimize Sidebar</span>
                </span>
                <span class="material-symbols-outlined text-[18px] shrink-0 loy-toggle-icon">chevron_left</span>
            </button>

            <a href="{{ route('portal') }}" title="Kembali ke Portal" class="loy-nav-item w-full flex items-center gap-2 px-3 py-1.5 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 text-xs font-semibold transition-all no-underline">
                <span class="material-symbols-outlined text-[18px] shrink-0">apps</span>
                <span class="truncate loy-portal-label">Kembali ke Portal</span>
            </a>
        </div>
    </aside>

    {{-- KONTEN UTAMA --}}
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">

        {{-- Top Bar Header --}}
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20 shadow-xs px-6 py-3.5 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900" id="topbar-title">
                    {{ $activeTab === 'tiers' ? 'Pengaturan Tier & Poin Member' : ($activeTab === 'members' ? 'Daftar Member Loyalty' : 'Dashboard Scanner Kasir') }}
                </h2>
                <p class="text-xs text-slate-500" id="topbar-subtitle">
                    {{ $activeTab === 'tiers' ? 'Kelola level tier, aturan perolehan poin per kategori barang, dan diskon belanja member' : ($activeTab === 'members' ? 'Kelola data pelanggan, pantau level tier, riwayat kartu, dan poin belanja' : 'Scan barcode / QR Code pelanggan atau cari berdasarkan nomor WhatsApp') }}
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="button" onclick="openRegisterModal()" class="px-4 py-2 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    <span>+ DAFTAR MEMBER BARU</span>
                </button>
            </div>
        </header>

        {{-- Flash Notifications --}}
        <div class="px-6 mt-4">
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
            @if(session('info'))
                <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 text-xs font-medium flex items-center justify-between shadow-2xs">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sky-600 text-[18px]">info</span>
                        <span>{{ session('info') }}</span>
                    </span>
                    @if(session('new_member_whatsapp'))
                        <a href="{{ session('new_member_whatsapp') }}" target="_blank" class="px-3 py-1 rounded-lg bg-sky-700 text-white text-xs font-bold hover:bg-sky-800 transition shadow-xs">
                            Kirim Kartu ke WA →
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Main View Body --}}
        <main class="p-6 flex-1 space-y-6">

            {{-- ==================== TAB 1: DASHBOARD (SCANNER) ==================== --}}
            <section id="tab-dashboard" class="tab-pane {{ $activeTab === 'dashboard' ? '' : 'hidden' }}">
                <div class="bg-white rounded-2xl border border-slate-200/90 p-8 shadow-xs max-w-3xl mx-auto">
                    <div class="text-center space-y-3">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[#006c49] border border-emerald-200 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-[#006c49] animate-ping"></span>
                            SCANNER KASIR AKTIF
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900">Scan Barcode / QR Pelanggan</h2>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                            Arahkan scanner kasir langsung ke QR Code di HP pelanggan atau ketik Nomor WhatsApp / Kode Member pelanggan.
                        </p>
                        
                        <div class="relative flex items-center mt-5 max-w-xl mx-auto">
                            <span class="material-symbols-outlined absolute left-4 text-slate-400 text-xl pointer-events-none">qr_code_scanner</span>
                            <input type="text" id="live_scanner_input" autofocus placeholder="Scan QR atau ketik: VP-XXXXX / 08123..."
                                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); doLiveSearch(); }"
                                class="w-full pl-12 pr-28 py-3.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border-2 border-emerald-300 focus:border-[#006c49] focus:ring-4 focus:ring-emerald-100 rounded-2xl text-sm font-mono text-slate-900 transition tracking-wide placeholder:text-slate-400" />
                            <button type="button" onclick="doLiveSearch()" class="absolute right-2 px-5 py-2 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                CARI
                            </button>
                        </div>
                    </div>

                    {{-- Dynamic Live Scanned Customer Box --}}
                    <div id="scan_result_box" class="mt-8 pt-6 border-t border-slate-200 hidden">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 shadow-2xs">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#006c49] flex items-center justify-center font-bold text-lg font-mono" id="res_avatar">
                                        ★
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-extrabold text-base text-slate-900" id="res_name"></h3>
                                            <span id="res_tier_badge" class="px-2.5 py-0.5 rounded text-[10px] font-bold"></span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-mono mt-0.5">
                                            <span id="res_member_code" class="text-[#006c49] font-bold"></span> • <span id="res_phone"></span>
                                        </p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeScanResult()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t border-slate-200 text-xs">
                                <div class="bg-white p-3 rounded-xl border border-slate-200/70">
                                    <span class="text-[11px] text-slate-500 font-medium block">Saldo Poin Belanja</span>
                                    <span class="text-lg font-extrabold text-amber-600 font-mono" id="res_points">0 Poin</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5" id="res_points_rp">≈ Rp 0</span>
                                </div>
                                <div class="bg-white p-3 rounded-xl border border-slate-200/70">
                                    <span class="text-[11px] text-slate-500 font-medium block">Aksi Cepat Kasir</span>
                                    <div class="flex gap-2 mt-1">
                                        <button type="button" onclick="promptAddPoints()" class="px-2.5 py-1 rounded-lg bg-emerald-50 text-[#006c49] font-bold text-xs border border-emerald-200 hover:bg-emerald-100 transition">
                                            + Poin
                                        </button>
                                        <button type="button" onclick="promptRedeemPoints()" class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 font-bold text-xs border border-rose-200 hover:bg-rose-100 transition">
                                            - Tukar Poin
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                                <a id="res_card_url" href="#" target="_blank" class="text-[#006c49] hover:underline font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">open_in_new</span> Buka Kartu Digital
                                </a>
                                <a id="res_wa_url" href="#" target="_blank" class="text-emerald-600 hover:underline font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">send</span> Kirim Kartu ke WA
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ==================== TAB 2: DAFTAR MEMBER ==================== --}}
            <section id="tab-members" class="tab-pane {{ $activeTab === 'members' ? '' : 'hidden' }} space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                    {{-- Header & Search Bar --}}
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Daftar Member Loyalty Terdaftar</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Total {{ $customers->total() }} pelanggan aktif memiliki kartu digital</p>
                        </div>
                        <form method="GET" action="{{ route('loyalty.index') }}" class="flex items-center gap-2">
                            <input type="hidden" name="tab" value="members">
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / no WA / kode..."
                                    class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-[#006c49] rounded-xl text-xs text-slate-800 w-56 transition font-medium" />
                            </div>
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                Filter
                            </button>
                        </form>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200 text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">Nama Pelanggan</th>
                                    <th class="py-3.5 px-4">No. Member</th>
                                    <th class="py-3.5 px-4">Level Tier</th>
                                    <th class="py-3.5 px-4">Total Belanja</th>
                                    <th class="py-3.5 px-4">Poin Belanja</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                @forelse($customers as $c)
                                    @php
                                        $tierModel = $c->getTierModel();
                                        $badgeColor = $tierModel?->badge_color ?? 'slate';
                                        $colorClasses = [
                                            'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
                                            'emerald' => 'bg-emerald-50 text-[#006c49] border-emerald-200',
                                            'cyan' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        ];
                                        $badgeClass = $colorClasses[$badgeColor] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        $pointRate = $tierModel ? $tierModel->point_value_rp : 100;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900 text-sm">{{ $c->name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $c->phone }}</div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-[#006c49] text-xs px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-lg tracking-wider">
                                                {{ $c->member_code }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                                ★ {{ strtoupper($c->tier) }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-slate-800 text-xs">Rp {{ number_format($c->total_spent ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-amber-600 text-sm">{{ number_format($c->points) }} Pts</span>
                                            <span class="text-[10px] text-slate-400 block">≈ Rp {{ number_format($c->points * $pointRate, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ $c->whatsapp_share_url }}" target="_blank" title="Kirim Link Kartu ke WA" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#006c49] border border-emerald-200 hover:bg-emerald-100 font-semibold text-xs transition flex items-center gap-1 no-underline">
                                                    <span class="material-symbols-outlined text-[14px]">send</span>
                                                    <span>Kirim WA</span>
                                                </a>

                                                <a href="{{ $c->card_url }}" target="_blank" title="Buka Kartu Digital" class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 font-semibold text-xs transition flex items-center gap-1 no-underline">
                                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                                    <span>Buka Kartu</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-400">
                                            Belum ada data pelanggan yang cocok. Klik "+ DAFTAR MEMBER BARU" untuk mendaftarkan member baru!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($customers->hasPages())
                        <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                            {{ $customers->links() }}
                        </div>
                    @endif
                </div>
            </section>

            {{-- ==================== TAB 3: TIER (PENGATURAN TIER & POIN) ==================== --}}
            <section id="tab-tiers" class="tab-pane {{ $activeTab === 'tiers' ? '' : 'hidden' }} space-y-6">

                {{-- SECTION 1: DAFTAR TIER (LIST COLUMN TABLE DENGAN CRUD) --}}
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                    {{-- Header Bar --}}
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#006c49] text-[22px]">military_tech</span>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Daftar &amp; Level Tier Member</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    {{ $tiers->count() }} Tier Terdaftar
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Kelola level tier pembelanjaan, multiplier poin, potongan belanja member, dan benefit. Kenaikan tier member dihitung otomatis saat transaksi POS selesai.
                            </p>
                        </div>
                        <div>
                            <button type="button" onclick="openCreateTierModal()" class="px-4 py-2 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>+ TAMBAH TIER BARU</span>
                            </button>
                        </div>
                    </div>

                    {{-- Table List Column --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200 text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4 w-16 text-center">Level</th>
                                    <th class="py-3.5 px-4">Nama &amp; Badge Tier</th>
                                    <th class="py-3.5 px-4">Min. Belanja</th>
                                    <th class="py-3.5 px-4">Multiplier Poin</th>
                                    <th class="py-3.5 px-4">Diskon Belanja</th>
                                    <th class="py-3.5 px-4">Nilai Tukar Poin</th>
                                    <th class="py-3.5 px-4">Total Member</th>
                                    <th class="py-3.5 px-4 text-right w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                @forelse($tiers as $tier)
                                    @php
                                        $colorMap = [
                                            'slate' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'emerald' => 'bg-emerald-50 text-[#006c49] border-emerald-300',
                                            'cyan' => 'bg-sky-50 text-sky-700 border-sky-300',
                                            'amber' => 'bg-amber-50 text-amber-700 border-amber-300',
                                            'purple' => 'bg-purple-50 text-purple-700 border-purple-300',
                                            'rose' => 'bg-rose-50 text-rose-700 border-rose-300',
                                        ];
                                        $badgeColorClass = $colorMap[$tier->badge_color] ?? $colorMap['slate'];
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-bold font-mono text-xs border border-slate-200">
                                                {{ $tier->level }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeColorClass }}">
                                                    ★ {{ strtoupper($tier->name) }}
                                                </span>
                                                @if($tier->is_default)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-[#004d34] border border-emerald-200">
                                                        Default
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-slate-900 text-xs">
                                                Rp {{ number_format($tier->min_spend, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 block">Akumulasi belanja</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-extrabold text-[#006c49] text-xs px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200">
                                                {{ $tier->point_multiplier }}x
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @if(($tier->discount_nominal ?? 0) > 0)
                                                <span class="font-mono font-bold text-indigo-700 text-xs px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200">
                                                    Rp {{ number_format($tier->discount_nominal, 0, ',', '.') }}
                                                </span>
                                            @elseif(($tier->discount_percent ?? 0) > 0)
                                                <span class="font-mono font-bold text-indigo-700 text-xs px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200">
                                                    {{ $tier->discount_percent }}%
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-xs font-mono">Rp 0</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-semibold text-slate-800 text-xs">
                                                Rp {{ number_format($tier->point_value_rp, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 block">per 1 Poin</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-bold text-slate-900 font-mono text-xs">
                                                {{ number_format($tier->customers_count ?? 0) }} Member
                                            </span>
                                            <span class="text-[10px] text-slate-400 block">
                                                {{ round((($tier->customers_count ?? 0) / max($totalCustomers, 1)) * 100) }}% total
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" onclick="openEditTierModal({{ json_encode($tier) }})" title="Edit Tier"
                                                    class="p-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer">
                                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                                </button>

                                                @if(!$tier->is_default)
                                                    <form action="{{ route('loyalty.tiers.destroy', $tier->id) }}" method="POST" class="inline"
                                                        onsubmit="return confirm('Hapus tier {{ $tier->name }}? Member pada tier ini akan dialihkan ke tier default.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="Hapus Tier"
                                                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer">
                                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="p-1.5 text-slate-300 cursor-not-allowed" title="Tier default tidak dapat dihapus">
                                                        <span class="material-symbols-outlined text-[16px]">lock</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 text-center text-slate-400">
                                            Belum ada tier yang dibuat. Klik "+ TAMBAH TIER BARU" untuk membuat tier pertama.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECTION 2: ATURAN POIN BERDASARKAN KATEGORI BARANG --}}
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
                    <form action="{{ route('loyalty.category-rules.update') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#006c49]">category</span>
                                    Aturan Perolehan Poin per Kategori Barang
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Tentukan jumlah poin yang langsung didapat pelanggan untuk setiap pembelian 1 pcs / botol barang pada kategori terkait.
                                </p>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                                    Simpan Semua Aturan
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 text-[11px] uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-4 w-20 text-center">Status</th>
                                        <th class="py-3 px-4">Kategori Produk</th>
                                        <th class="py-3 px-4 w-52">Poin per Item / Satuan</th>
                                        <th class="py-3 px-4">Simulasi Perolehan Poin Kasir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                    @php
                                        $ruleMap = $categoryRules->keyBy(fn($r) => strtolower(trim($r->category_name)));
                                    @endphp
                                    @foreach($allCategories as $cat)
                                        @php
                                            $catKey = strtolower(trim($cat));
                                            $rule = $ruleMap->get($catKey);
                                            $isActive = $rule ? (bool)$rule->is_active : true;
                                            $pointsPerItem = $rule ? ($rule->points_per_item ?? 1) : 1;
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="py-3 px-4 text-center">
                                                <input type="checkbox" name="rules[{{ $cat }}][is_active]" value="1" {{ $isActive ? 'checked' : '' }}
                                                    class="rounded text-[#006c49] focus:ring-emerald-200 border-slate-300 cursor-pointer h-4 w-4" />
                                            </td>
                                            <td class="py-3 px-4 font-bold text-slate-900">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                                    <span class="text-sm">{{ $cat }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-1.5">
                                                    <input type="number" step="1" min="0" max="1000" name="rules[{{ $cat }}][points_per_item]" value="{{ $pointsPerItem }}"
                                                        class="w-20 px-3 py-1.5 bg-slate-50 focus:bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-800 focus:border-[#006c49] text-center" />
                                                    <span class="text-xs text-slate-600 font-bold">Poin / pcs</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                                Beli 1 pcs = <strong class="text-emerald-700 font-bold">+{{ $pointsPerItem }} Poin</strong>
                                                <span class="text-slate-400 ml-1.5">(Beli 2 pcs = +{{ $pointsPerItem * 2 }} Poin)</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Quick Add Custom Category Rule --}}
                        <div class="pt-4 border-t border-slate-100 bg-slate-50/60 p-4 rounded-xl flex flex-col sm:flex-row items-center gap-3">
                            <span class="text-xs font-bold text-slate-700 shrink-0">+ Tambah Kategori Lain:</span>
                            <input type="text" name="new_category_name" placeholder="Nama Kategori..."
                                class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-[#006c49]" />
                            <div class="flex items-center gap-1.5 w-44">
                                <input type="number" step="1" min="0" name="new_points_per_item" placeholder="1"
                                    class="w-20 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49] text-center font-bold" />
                                <span class="text-xs text-slate-600 font-semibold">Poin / pcs</span>
                            </div>
                        </div>

                        {{-- Welcome Bonus Point Setting --}}
                        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-xs uppercase tracking-wide text-slate-800">Welcome Bonus Poin Baru</h4>
                                <p class="text-xs text-slate-500">Poin selamat datang yang langsung diberikan saat pelanggan pertama kali mendaftar sebagai member.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" name="welcome_bonus" value="{{ $welcomeBonus }}" min="0" max="1000"
                                    class="w-28 px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-800 focus:border-[#006c49] text-center font-bold" />
                                <span class="text-xs font-semibold text-slate-600">Poin</span>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-100">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                                Simpan Semua Pengaturan Kategori &amp; Poin
                            </button>
                        </div>
                    </form>
                </div>
            </section>

        </main>
    </div>

    {{-- MODAL TAMBAH TIER BARU --}}
    <div id="createTierModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">military_tech</span>
                    Tambah Tier Baru
                </h3>
                <button type="button" onclick="closeCreateTierModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form action="{{ route('loyalty.tiers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Nama Tier <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="Contoh: Diamond"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-medium text-slate-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Level Urutan <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="level" required min="1" max="20" value="{{ $tiers->count() + 1 }}"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Min. Belanja Kumulatif (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="100000" min="0" name="min_spend" required placeholder="10000000"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Multiplier Poin <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.05" min="0.1" max="10" name="point_multiplier" required value="1.0"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Diskon Belanja Member (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="500" min="0" name="discount_nominal" required value="0" placeholder="0"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                        <span class="text-[10px] text-slate-400">Nominal potongan langsung saat transaksi kasir</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Nilai Tukar Poin (Rp/pt) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="10" min="1" name="point_value_rp" required value="100"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                        <span class="text-[10px] text-slate-400">1 Poin = Potongan Rp ...</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                        Warna Badge
                    </label>
                    <select name="badge_color" class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-medium text-slate-900">
                        <option value="slate">Slate / Neutral (Starter)</option>
                        <option value="emerald">Emerald / Hijau Z1</option>
                        <option value="cyan">Cyan / Silver Sky</option>
                        <option value="amber">Amber / Gold</option>
                        <option value="purple">Purple / Prestige VIP</option>
                        <option value="rose">Rose / Ruby</option>
                    </select>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_default" value="1" class="rounded text-[#006c49] focus:ring-emerald-200 border-slate-300">
                        <span>Jadikan sebagai Tier Default untuk Member Baru</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateTierModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#006c49] hover:bg-[#005a3c] text-white rounded-xl text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                        Simpan Tier Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT TIER --}}
    <div id="editTierModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">edit</span>
                    Edit Tier: <span id="edit-tier-title-name" class="text-[#006c49]"></span>
                </h3>
                <button type="button" onclick="closeEditTierModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form id="editTierForm" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Nama Tier <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="edit_name" name="name" required
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-medium text-slate-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Level Urutan <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="edit_level" name="level" required min="1" max="20"
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Min. Belanja Kumulatif (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="100000" min="0" id="edit_min_spend" name="min_spend" required
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Multiplier Poin <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.05" min="0.1" max="10" id="edit_point_multiplier" name="point_multiplier" required
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Diskon Belanja Member (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="500" min="0" id="edit_discount_nominal" name="discount_nominal" required
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                        <span class="text-[10px] text-slate-400">Nominal potongan langsung saat transaksi kasir</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                            Nilai Tukar Poin (Rp/pt) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="10" min="1" id="edit_point_value_rp" name="point_value_rp" required
                            class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-mono text-slate-900" />
                        <span class="text-[10px] text-slate-400">1 Poin = Potongan Rp ...</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">
                        Warna Badge
                    </label>
                    <select id="edit_badge_color" name="badge_color" class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#006c49] rounded-xl text-xs font-medium text-slate-900">
                        <option value="slate">Slate / Neutral (Starter)</option>
                        <option value="emerald">Emerald / Hijau Z1</option>
                        <option value="cyan">Cyan / Silver Sky</option>
                        <option value="amber">Amber / Gold</option>
                        <option value="purple">Purple / Prestige VIP</option>
                        <option value="rose">Rose / Ruby</option>
                    </select>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" id="edit_is_default" name="is_default" value="1" class="rounded text-[#006c49] focus:ring-emerald-200 border-slate-300">
                        <span>Jadikan sebagai Tier Default untuk Member Baru</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditTierModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#006c49] hover:bg-[#005a3c] text-white rounded-xl text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                        Perbarui Tier
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DAFTAR MEMBER BARU (Tanpa Rasa Favorit) --}}
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
                    <p class="text-[11px] text-slate-400 mt-1">Kartu digital member & link akses akan otomatis dikirimkan ke nomor WA ini.</p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
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

        const activeNavClass = 'loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs';
        const inactiveNavClass = 'loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium';

        // Switch Sidebar Tab
        function switchTab(tab) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            const targetPane = document.getElementById('tab-' + tab);
            if (targetPane) targetPane.classList.remove('hidden');

            // Update sidebar buttons style
            ['dashboard', 'members', 'tiers'].forEach(t => {
                const btn = document.getElementById('nav-btn-' + t);
                if (btn) {
                    if (t === tab) {
                        btn.className = 'loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs';
                    } else {
                        btn.className = 'loy-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all cursor-pointer text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium';
                    }
                }
            });

            // Update Header Title
            const titles = {
                'dashboard': ['Dashboard Scanner Kasir', 'Scan barcode / QR Code pelanggan atau cari berdasarkan nomor WhatsApp'],
                'members': ['Daftar Member Loyalty', 'Kelola data pelanggan, pantau level tier, riwayat kartu, dan poin belanja'],
                'tiers': ['Pengaturan Tier & Poin Member', 'Kelola level tier, aturan perolehan poin per kategori barang, dan diskon belanja member']
            };
            if (titles[tab]) {
                document.getElementById('topbar-title').innerText = titles[tab][0];
                document.getElementById('topbar-subtitle').innerText = titles[tab][1];
            }

            // Focus on scanner if dashboard
            if (tab === 'dashboard') {
                setTimeout(() => {
                    const inp = document.getElementById('live_scanner_input');
                    if (inp) inp.focus();
                }, 100);
            }

            // Update browser URL query param cleanly without reloading
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }

        // Toggle Minimize / Expand Sidebar (Persis seperti Human Resources)
        function toggleLoyaltySidebar() {
            const sidebar = document.getElementById('loyalty-sidebar');
            if (!sidebar) return;
            const isMin = sidebar.classList.contains('is-minimized');
            if (isMin) {
                sidebar.classList.remove('is-minimized');
                localStorage.setItem('z1_loyalty_sidebar_minimized', 'false');
            } else {
                sidebar.classList.add('is-minimized');
                localStorage.setItem('z1_loyalty_sidebar_minimized', 'true');
            }
        }

        // Apply initial state from localStorage
        if (localStorage.getItem('z1_loyalty_sidebar_minimized') === 'true') {
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('loyalty-sidebar');
                if (sidebar) sidebar.classList.add('is-minimized');
            });
        }

        // Modal Handlers
        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
        }
        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
        }

        function openCreateTierModal() {
            document.getElementById('createTierModal').classList.remove('hidden');
        }
        function closeCreateTierModal() {
            document.getElementById('createTierModal').classList.add('hidden');
        }

        function openEditTierModal(tier) {
            document.getElementById('edit-tier-title-name').innerText = tier.name;
            document.getElementById('editTierForm').action = `/loyalty/tiers/${tier.id}`;
            document.getElementById('edit_name').value = tier.name;
            document.getElementById('edit_level').value = tier.level;
            document.getElementById('edit_min_spend').value = tier.min_spend;
            document.getElementById('edit_point_multiplier').value = tier.point_multiplier;
            document.getElementById('edit_discount_nominal').value = tier.discount_nominal || 0;
            document.getElementById('edit_point_value_rp').value = tier.point_value_rp;
            document.getElementById('edit_badge_color').value = tier.badge_color || 'slate';
            document.getElementById('edit_is_default').checked = Boolean(tier.is_default);
            document.getElementById('editTierModal').classList.remove('hidden');
        }
        function closeEditTierModal() {
            document.getElementById('editTierModal').classList.add('hidden');
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
            document.getElementById('res_points_rp').innerText = `Rp ${(c.points_value_rp ?? (c.points * 100)).toLocaleString('id-ID')}`;

            const badge = document.getElementById('res_tier_badge');
            badge.innerText = `★ ${c.tier.toUpperCase()}`;
            const color = c.tier_badge_color || 'slate';
            const colorClasses = {
                'slate': 'bg-slate-100 text-slate-700 border border-slate-300',
                'emerald': 'bg-emerald-50 text-[#006c49] border border-emerald-300',
                'cyan': 'bg-sky-50 text-sky-700 border border-sky-300',
                'amber': 'bg-amber-50 text-amber-700 border border-amber-300',
                'purple': 'bg-purple-50 text-purple-700 border border-purple-300',
                'rose': 'bg-rose-50 text-rose-700 border border-rose-300',
            };
            badge.className = `px-2.5 py-0.5 rounded text-[10px] font-bold ${colorClasses[color] || colorClasses['slate']}`;

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
