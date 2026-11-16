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

        /* HR-style Minimized Sidebar (Rail Mode) */
        #loyalty-sidebar.is-minimized {
            width: 4.25rem !important; /* 68px */
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        #loyalty-sidebar.is-minimized #sidebar-brand-box {
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: center !important;
        }

        #loyalty-sidebar.is-minimized .sidebar-brand-text {
            display: none !important;
        }

        #loyalty-sidebar.is-minimized .sidebar-mini-text {
            display: block !important;
        }

        #loyalty-sidebar.is-minimized .loy-nav-text,
        #loyalty-sidebar.is-minimized .loy-section-title,
        #loyalty-sidebar.is-minimized .loy-toggle-label,
        #loyalty-sidebar.is-minimized .loy-portal-label {
            display: none !important;
        }

        #loyalty-sidebar.is-minimized .loy-section-divider {
            display: block !important;
        }

        #loyalty-sidebar.is-minimized .loy-nav-item {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 2.75rem !important;
            height: 2.5rem !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        #loyalty-sidebar.is-minimized .loy-footer-box {
            padding-left: 0 !important;
            padding-right: 0 !important;
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
                    class="loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer {{ $activeTab === 'members' ? 'bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">how_to_reg</span>
                    <span class="truncate loy-nav-text">Daftar Member</span>
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
                    class="loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer {{ $activeTab === 'tiers' ? 'bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">military_tech</span>
                    <span class="truncate loy-nav-text">Tier &amp; Poin</span>
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
                    {{ $activeTab === 'tiers' ? 'Kelola ambang batas pembelanjaan, perolehan poin, dan benefit setiap tier member' : ($activeTab === 'members' ? 'Kelola data pelanggan, pantau level tier, riwayat kartu, dan poin belanja' : 'Scan barcode / QR Code pelanggan atau cari berdasarkan nomor WhatsApp') }}
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
                                class="w-full pl-11 pr-24 py-3.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 text-sm rounded-xl transition-all font-mono text-slate-800 placeholder:text-slate-400 shadow-inner"
                                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); doLiveSearch(); }" />
                            <button type="button" onclick="doLiveSearch()" class="absolute right-2 px-5 py-2 bg-[#006c49] hover:bg-[#005a3c] text-white font-bold text-xs rounded-lg transition shadow-xs cursor-pointer">
                                CARI
                            </button>
                        </div>
                    </div>

                    {{-- Live Result Box (AJAX Member Card Preview) --}}
                    <div id="scan_result_box" class="hidden mt-8 pt-6 border-t border-slate-200 max-w-xl mx-auto">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-emerald-300 relative shadow-xs">
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

                            <div class="my-3.5 bg-white p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
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
            </section>

            {{-- ==================== TAB 2: MEMBER LOYALTY (DAFTAR & CREATE) ==================== --}}
            <section id="tab-members" class="tab-pane {{ $activeTab === 'members' ? '' : 'hidden' }}">
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200/80">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Daftar Member Loyalty</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Total {{ $customers->total() }} pelanggan terdaftar</p>
                        </div>

                        {{-- Search Filter & Actions --}}
                        <div class="flex items-center gap-2">
                            <form action="{{ route('loyalty.index') }}" method="GET" class="flex gap-2">
                                <input type="hidden" name="tab" value="members" />
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-2 text-slate-400 text-sm">search</span>
                                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / no WA / kode..."
                                        class="pl-9 pr-3.5 py-1.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-[#006c49] focus:ring-1 focus:ring-emerald-200 transition" />
                                </div>
                                <button type="submit" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-semibold rounded-xl transition cursor-pointer">
                                    Filter
                                </button>
                                @if($search)
                                    <a href="{{ route('loyalty.index', ['tab' => 'members']) }}" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-xl transition no-underline">
                                        Reset
                                    </a>
                                @endif
                            </form>

                            <button onclick="openRegisterModal()" class="px-3.5 py-1.5 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs flex items-center gap-1 transition cursor-pointer">
                                <span class="material-symbols-outlined text-[16px]">person_add</span>
                                <span>+ Member Baru</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase text-[11px] tracking-wider border-b border-slate-200">
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
                                            @if($c->tier === 'Master')
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                    ★ MASTER
                                                </span>
                                            @elseif($c->tier === 'Cloud Chaser')
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                                    ⚡ CLOUD CHASER
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-[#006c49] border border-emerald-200">
                                                    🌿 ROOKIE
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-slate-800 text-xs">Rp {{ number_format($c->total_spent ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-mono font-bold text-amber-600 text-sm">{{ number_format($c->points) }} Pts</span>
                                            <span class="text-[10px] text-slate-400 block">≈ Rp {{ number_format($c->points * ($tierConfig['rules']['point_value_rp'] ?? 100), 0, ',', '.') }}</span>
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

                {{-- Visual Cards: 3 Tiers Distribution --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- 1. Rookie --}}
                    <div class="bg-white rounded-2xl border border-emerald-200 p-5 shadow-2xs relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#006c49] border border-emerald-200">
                                🌿 ROOKIE
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">Tier 1</span>
                        </div>
                        <div class="mt-4">
                            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($tierCounts['Rookie'] ?? 0) }} Member</div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ round((($tierCounts['Rookie'] ?? 0) / $totalCustomers) * 100) }}% dari total member
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                            <div>Min. Belanja: <strong class="text-slate-900 font-mono">Rp {{ number_format($tierConfig['rookie']['min_spend'] ?? 0, 0, ',', '.') }}</strong></div>
                            <div>Multiplier Poin: <strong class="text-[#006c49] font-mono">{{ $tierConfig['rookie']['point_multiplier'] ?? 1 }}x</strong></div>
                        </div>
                    </div>

                    {{-- 2. Cloud Chaser --}}
                    <div class="bg-white rounded-2xl border border-sky-200 p-5 shadow-2xs relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                ⚡ CLOUD CHASER
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">Tier 2</span>
                        </div>
                        <div class="mt-4">
                            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($tierCounts['Cloud Chaser'] ?? 0) }} Member</div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ round((($tierCounts['Cloud Chaser'] ?? 0) / $totalCustomers) * 100) }}% dari total member
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                            <div>Min. Belanja: <strong class="text-slate-900 font-mono">Rp {{ number_format($tierConfig['cloud_chaser']['min_spend'] ?? 1500000, 0, ',', '.') }}</strong></div>
                            <div>Multiplier Poin: <strong class="text-sky-600 font-mono">{{ $tierConfig['cloud_chaser']['point_multiplier'] ?? 1.25 }}x</strong></div>
                        </div>
                    </div>

                    {{-- 3. Master --}}
                    <div class="bg-white rounded-2xl border border-purple-200 p-5 shadow-2xs relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                ★ MASTER
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">Tier 3 (VIP)</span>
                        </div>
                        <div class="mt-4">
                            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($tierCounts['Master'] ?? 0) }} Member</div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ round((($tierCounts['Master'] ?? 0) / $totalCustomers) * 100) }}% dari total member
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                            <div>Min. Belanja: <strong class="text-slate-900 font-mono">Rp {{ number_format($tierConfig['master']['min_spend'] ?? 3500000, 0, ',', '.') }}</strong></div>
                            <div>Multiplier Poin: <strong class="text-purple-600 font-mono">{{ $tierConfig['master']['point_multiplier'] ?? 1.5 }}x</strong></div>
                        </div>
                    </div>
                </div>

                {{-- Configuration Form --}}
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6">
                    <form action="{{ route('loyalty.tiers.update') }}" method="POST" class="space-y-6">
                        @csrf
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Konfigurasi Ambang Batas Pembelanjaan Tiap Tier</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Kenaikan tier pelanggan dihitung otomatis saat setiap transaksi POS selesai.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Rookie Form --}}
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                                <h4 class="font-bold text-xs uppercase tracking-wide text-emerald-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Rookie (Starter)
                                </h4>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Minimal Belanja (Rp)</label>
                                    <input type="number" name="tier_rookie_min_spend" value="{{ $tierConfig['rookie']['min_spend'] ?? 0 }}" readonly
                                        class="w-full px-3 py-2 bg-slate-200/70 border border-slate-300 rounded-lg text-xs font-mono text-slate-600 cursor-not-allowed" />
                                    <span class="text-[10px] text-slate-400">Default untuk semua member baru</span>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Multiplier Poin</label>
                                    <input type="number" step="0.05" name="tier_rookie_multiplier" value="{{ $tierConfig['rookie']['point_multiplier'] ?? 1.0 }}"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Deskripsi Benefit</label>
                                    <textarea name="tier_rookie_perks" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-[#006c49]">{{ $tierConfig['rookie']['perks'] ?? 'Akses Member, Stempel Liquid & Tukar Poin Diskon' }}</textarea>
                                </div>
                            </div>

                            {{-- Cloud Chaser Form --}}
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                                <h4 class="font-bold text-xs uppercase tracking-wide text-sky-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-sky-500"></span> Cloud Chaser
                                </h4>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Minimal Belanja (Rp)</label>
                                    <input type="number" step="100000" name="tier_cloud_min_spend" value="{{ $tierConfig['cloud_chaser']['min_spend'] ?? 1500000 }}"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                    <span class="text-[10px] text-slate-400">Akumulasi total pembelanjaan</span>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Multiplier Poin</label>
                                    <input type="number" step="0.05" name="tier_cloud_multiplier" value="{{ $tierConfig['cloud_chaser']['point_multiplier'] ?? 1.25 }}"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Deskripsi Benefit</label>
                                    <textarea name="tier_cloud_perks" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-[#006c49]">{{ $tierConfig['cloud_chaser']['perks'] ?? 'Bonus Perolehan Poin 1.25x, Diskon Promo Bulanan' }}</textarea>
                                </div>
                            </div>

                            {{-- Master Form --}}
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                                <h4 class="font-bold text-xs uppercase tracking-wide text-purple-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span> Master (VIP)
                                </h4>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Minimal Belanja (Rp)</label>
                                    <input type="number" step="100000" name="tier_master_min_spend" value="{{ $tierConfig['master']['min_spend'] ?? 3500000 }}"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                    <span class="text-[10px] text-slate-400">Akumulasi total pembelanjaan</span>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Multiplier Poin</label>
                                    <input type="number" step="0.05" name="tier_master_multiplier" value="{{ $tierConfig['master']['point_multiplier'] ?? 1.5 }}"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Deskripsi Benefit</label>
                                    <textarea name="tier_master_perks" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-[#006c49]">{{ $tierConfig['master']['perks'] ?? 'Bonus Perolehan Poin 1.5x, Antrean Kasir Prioritas & Merchandise' }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Aturan Poin --}}
                        <div class="pt-5 border-t border-slate-200">
                            <h4 class="font-bold text-xs uppercase tracking-wide text-slate-800 mb-3">Aturan Konversi &amp; Perolehan Poin Kasir</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Perolehan Poin (Rp per 1 Poin)</label>
                                    <input type="number" step="1000" name="spend_per_point" value="{{ $tierConfig['rules']['spend_per_point'] ?? 10000 }}"
                                        class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                    <span class="text-[10px] text-slate-400 mt-1 block">Contoh: Rp 10.000 belanja = 1 Poin</span>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nilai Tukar Poin (Diskon Rp per 1 Poin)</label>
                                    <input type="number" step="10" name="point_value_rp" value="{{ $tierConfig['rules']['point_value_rp'] ?? 100 }}"
                                        class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                    <span class="text-[10px] text-slate-400 mt-1 block">Contoh: 1 Poin = Potongan Rp 100</span>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Welcome Bonus Poin Baru</label>
                                    <input type="number" name="welcome_bonus" value="{{ $tierConfig['rules']['welcome_bonus'] ?? 50 }}"
                                        class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-[#006c49]" />
                                    <span class="text-[10px] text-slate-400 mt-1 block">Poin otomatis saat pertama kali daftar</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-100">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                                Simpan Pengaturan Tier &amp; Poin
                            </button>
                        </div>
                    </form>
                </div>

            </section>

        </main>
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

        const activeNavClass = 'loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer bg-[#5cf3b3] text-[#004d34] font-extrabold shadow-2xs';
        const inactiveNavClass = 'loy-nav-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all cursor-pointer text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium';

        // Switch Sidebar Tab
        function switchTab(tab) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            const targetPane = document.getElementById('tab-' + tab);
            if (targetPane) targetPane.classList.remove('hidden');

            // Update sidebar buttons style
            ['dashboard', 'members', 'tiers'].forEach(t => {
                const btn = document.getElementById('nav-btn-' + t);
                if (btn) btn.className = (t === tab) ? activeNavClass : inactiveNavClass;
            });

            // Update Header Title
            const titles = {
                'dashboard': ['Dashboard Scanner Kasir', 'Scan barcode / QR Code pelanggan atau cari berdasarkan nomor WhatsApp'],
                'members': ['Daftar Member Loyalty', 'Kelola data pelanggan, pantau level tier, riwayat kartu, dan poin belanja'],
                'tiers': ['Pengaturan Tier & Poin Member', 'Kelola ambang batas pembelanjaan, perolehan poin, dan benefit setiap tier member']
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
            document.getElementById('res_points_rp').innerText = `Rp ${(c.points_value_rp ?? (c.points * 100)).toLocaleString('id-ID')}`;

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
