<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Z1 VapeOS Enterprise Portal - Workspace Directory</title>
    <!-- Google Fonts: Inter & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <!-- Tailwind CSS v3 CDN with Plugins -->
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
                    }
                }
            }
        }
    </script>
    <!-- BEGIN: Custom Styles -->
    <style data-purpose="custom-scrollbars">
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
    <style data-purpose="interactive-animations">
        .app-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .app-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.12), 0 4px 6px -4px rgba(15, 23, 42, 0.05);
        }
    </style>
    <!-- END: Custom Styles -->
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans min-h-screen flex flex-col antialiased selection:bg-brand-100 selection:text-brand-900">

    <!-- BEGIN: MainHeader -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs" data-purpose="primary-navbar">
        <div class="max-w-[1580px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- Logo & Organization Identification -->
                <div class="flex items-center gap-3 shrink-0">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-[#006c49] via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                            <rect height="7" rx="1.5" width="7" x="3" y="3"></rect>
                            <rect height="7" rx="1.5" width="7" x="14" y="3"></rect>
                            <rect height="7" rx="1.5" width="7" x="14" y="14"></rect>
                            <rect height="7" rx="1.5" width="7" x="3" y="14"></rect>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-slate-900 tracking-tight leading-none">PT Zona Global Group Portal</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 max-w-xl hidden md:block">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                        <input class="w-full pl-10 pr-10 py-2 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-[#006c49] focus:ring-2 focus:ring-emerald-100 text-sm rounded-lg transition-all placeholder:text-slate-400" id="appSearchInput" placeholder="Cari aplikasi, modul, atau layanan (misal: HR, Toko, CCTV)..." type="text"/>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 border border-slate-200 text-[10px] font-mono font-medium text-slate-400 bg-white rounded">⌘K</kbd>
                        </div>
                    </div>
                </div>

                <!-- Actions & User Profile -->
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div id="profile-trigger" class="flex items-center gap-2.5 cursor-pointer hover:bg-slate-100 p-1.5 rounded-xl transition-all select-none">
                            @if($user->photo)
                                <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-slate-200 object-cover shadow-xs" src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}"/>
                            @else
                                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm font-bold text-slate-800 hidden sm:inline">{{ $user->name }}</span>
                            <span class="material-symbols-outlined text-[18px] text-slate-400">expand_more</span>
                        </div>

                        <!-- Dropdown -->
                        <div id="profile-dropdown" class="absolute right-0 mt-2 w-64 bg-white border border-slate-200/90 rounded-xl shadow-xl overflow-hidden hidden z-50">
                            <div class="bg-[#f4f7fc] px-4 py-3 border-b border-slate-200/80">
                                <p class="text-sm font-bold text-[#0b1c30]">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500 truncate mt-0.5">{{ $user->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.show') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline">
                                    <span class="material-symbols-outlined text-[19px] text-slate-600">person</span>
                                    <span>My Profile</span>
                                </a>
                                <div class="my-1 border-t border-slate-200/80"></div>
                                <form id="dropdown-logout-form-portal" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                                <button onclick="document.getElementById('dropdown-logout-form-portal').submit();" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50/70 transition-colors flex items-center gap-3 font-medium cursor-pointer">
                                    <span class="material-symbols-outlined text-[19px] text-red-600">logout</span>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
    <!-- END: MainHeader -->

    <!-- BEGIN: MainContent -->
    <main class="flex-1 max-w-[1580px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div>
            <!-- BEGIN: FilterAndControlBar -->
            <div class="flex items-center justify-between gap-4 mb-6 pb-2 border-b border-slate-200/70" data-purpose="filter-bar">
                
                <!-- Category Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-sm" id="categoryTabGroup">
                    <button type="button" data-filter="all" class="px-3.5 py-1.5 rounded-lg bg-slate-900 text-white font-medium shadow-xs transition-colors shrink-0">
                        Semua Portal (4)
                    </button>
                    <button type="button" data-filter="hr" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition-colors shrink-0">
                        Human Resource
                    </button>
                    <button type="button" data-filter="security" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition-colors shrink-0">
                        Security &amp; Surveillance
                    </button>
                    <button type="button" data-filter="store" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition-colors shrink-0">
                        Store Management
                    </button>
                    <button type="button" data-filter="report" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium transition-colors shrink-0">
                        Report
                    </button>
                </div>

            </div>
            <!-- END: FilterAndControlBar -->

            <!-- BEGIN: AppCardGrid (Kotak-Kotak Kecil Saja Seperti Template) -->
            <div id="appsGridContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 lg:gap-5 max-w-5xl" data-purpose="apps-grid">

                <!-- Card 1: Human Resources (Direct to HR Dashboard) -->
                <a href="{{ route('commercial.hr.dashboard') }}" class="app-card bg-white rounded-xl border border-slate-200/90 overflow-hidden flex flex-col justify-between cursor-pointer group shadow-2xs no-underline text-inherit" data-category="hr" data-name="Human Resource HR Dashboard Payroll Daftar Kehadiran Shift Kerja Status Kehadiran Jadwal Kerja Shift Pattern">
                    <!-- Top Half: Colored Header (h-28) -->
                    <div class="h-28 bg-[#10b981] flex flex-col items-center justify-center p-3 relative transition-opacity group-hover:opacity-95 text-white">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <span class="text-white font-bold text-xs uppercase tracking-wider mt-1">HR &amp; Talent HQ</span>
                    </div>
                    <!-- Bottom Half: Details & Actions -->
                    <div class="p-3 bg-white flex flex-col justify-between flex-1 gap-2.5">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-sm font-semibold text-slate-800 truncate group-hover:text-emerald-600" title="Human Resource">Human Resource</span>
                            <span class="bg-emerald-600 text-white text-[9px] font-medium px-1.5 py-0.5 rounded leading-none shrink-0">Dashboard</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                            <div class="flex items-center -space-x-1">
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-emerald-100 text-emerald-700 rounded-full border border-white">HR</span>
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-slate-100 text-slate-600 rounded-full border border-white">PAY</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-600 p-0.5" title="Buka Dashboard Human Resource">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Card 2: Security & Surveillance (Kotak Kecil) -->
                <a href="{{ route('commercial.monitoring.index') }}" class="app-card bg-white rounded-xl border border-slate-200/90 overflow-hidden flex flex-col justify-between cursor-pointer group shadow-2xs no-underline text-inherit" data-category="security" data-name="Security Surveillance CCTV Monitoring Keamanan Multi-View">
                    <!-- Top Half: Colored Header (h-28) -->
                    <div class="h-28 bg-[#4f46e5] flex flex-col items-center justify-center p-3 relative transition-opacity group-hover:opacity-95 text-white">
                        <div class="relative">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-400 rounded-full animate-pulse"></span>
                        </div>
                        <span class="text-white font-bold text-xs uppercase tracking-wider mt-1">CCTV &amp; IoT Hub</span>
                    </div>
                    <!-- Bottom Half: Details & Actions -->
                    <div class="p-3 bg-white flex flex-col justify-between flex-1 gap-2.5">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-sm font-semibold text-slate-800 truncate group-hover:text-indigo-600" title="Security & Surveillance">Security &amp; Surveillance</span>
                            <span class="bg-indigo-600 text-white text-[9px] font-medium px-1.5 py-0.5 rounded leading-none shrink-0">Live</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                            <div class="flex items-center -space-x-1">
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-indigo-100 text-indigo-700 rounded-full border border-white">SEC</span>
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-slate-100 text-slate-600 rounded-full border border-white">24/7</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-indigo-600 p-0.5" title="Buka Monitoring CCTV">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Card 3: Store Management (Kotak Kecil - DIRECT TO DASHBOARD) -->
                <a href="{{ route('dashboard') }}" class="app-card bg-white rounded-xl border border-slate-200/90 overflow-hidden flex flex-col justify-between cursor-pointer group shadow-2xs no-underline text-inherit" data-category="store" data-name="Store Management Dashboard Toko Cabang Retail POS Buka Dashboard Toko">
                    <!-- Top Half: Colored Header (h-28) -->
                    <div class="h-28 bg-[#f97316] flex flex-col items-center justify-center p-3 relative transition-opacity group-hover:opacity-95 text-white">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <span class="text-white font-bold text-xs uppercase tracking-wider mt-1">Retail &amp; POS</span>
                    </div>
                    <!-- Bottom Half: Details & Actions -->
                    <div class="p-3 bg-white flex flex-col justify-between flex-1 gap-2.5">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-sm font-semibold text-slate-800 truncate group-hover:text-orange-600" title="Store Management">Store Management</span>
                            <span class="bg-amber-600 text-white text-[9px] font-medium px-1.5 py-0.5 rounded leading-none shrink-0" title="Buka Dashboard Toko">Direct</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                            <div class="flex items-center -space-x-1">
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-orange-100 text-orange-700 rounded-full border border-white">OPS</span>
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-slate-100 text-slate-600 rounded-full border border-white">POS</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-orange-600 p-0.5" title="Buka Dashboard Toko">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Card 4: Section Report (Reports & Analytics) -->
                <a href="{{ route('reports.index') }}" class="app-card bg-white rounded-xl border border-slate-200/90 overflow-hidden flex flex-col justify-between cursor-pointer group shadow-2xs no-underline text-inherit" data-category="report" data-name="Report Reports Laporan Analytics BI Store Performance Penjualan SKU Rekap Keuangan">
                    <!-- Top Half: Colored Header (h-28) -->
                    <div class="h-28 bg-[#0284c7] flex flex-col items-center justify-center p-3 relative transition-opacity group-hover:opacity-95 text-white">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <span class="text-white font-bold text-xs uppercase tracking-wider mt-1">Analytics &amp; BI</span>
                    </div>
                    <!-- Bottom Half: Details & Actions -->
                    <div class="p-3 bg-white flex flex-col justify-between flex-1 gap-2.5">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-sm font-semibold text-slate-800 truncate group-hover:text-sky-600" title="Report">Report</span>
                            <span class="bg-sky-600 text-white text-[9px] font-medium px-1.5 py-0.5 rounded leading-none shrink-0" title="Pusat Laporan & Analitik">Hub</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                            <div class="flex items-center -space-x-1">
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-sky-100 text-sky-700 rounded-full border border-white">BI</span>
                                <span class="inline-flex items-center justify-center h-5 px-1.5 text-[9px] font-semibold bg-slate-100 text-slate-600 rounded-full border border-white">RPT</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-sky-600 p-0.5" title="Buka Pusat Laporan">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                            </span>
                        </div>
                    </div>
                </a>

            </div>
            <!-- END: AppCardGrid -->

        </div>

    </main>
    <!-- END: MainContent -->

    <!-- BEGIN: Search & Filter Interaction Script -->
    <script data-purpose="search-and-filtering">
        document.addEventListener('DOMContentLoaded', () => {
            // Profile Dropdown Toggle
            const profileTrigger = document.getElementById('profile-trigger');
            const profileDropdown = document.getElementById('profile-dropdown');

            if (profileTrigger && profileDropdown) {
                profileTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', function(e) {
                    if (!profileDropdown.contains(e.target) && !profileTrigger.contains(e.target)) {
                        profileDropdown.classList.add('hidden');
                    }
                });
            }

            const searchInput = document.getElementById('appSearchInput');
            const cards = Array.from(document.querySelectorAll('.app-card'));
            const filterTabs = document.querySelectorAll('#categoryTabGroup button[data-filter]');

            let currentCategory = 'all';

            function filterCards() {
                const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

                cards.forEach(card => {
                    const cardText = card.innerText.toLowerCase();
                    const cardName = (card.getAttribute('data-name') || '').toLowerCase();
                    const cardCategory = card.getAttribute('data-category');

                    const matchesSearch = !query || cardText.includes(query) || cardName.includes(query);
                    const matchesCategory = (currentCategory === 'all') || (cardCategory === currentCategory);

                    if (matchesSearch && matchesCategory) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Search input live filtering
            if (searchInput) {
                searchInput.addEventListener('input', filterCards);

                // Cmd+K / Ctrl+K keyboard shortcut
                window.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.select();
                    }
                });
            }

            // Category tabs switching
            filterTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    filterTabs.forEach(t => {
                        t.classList.remove('bg-slate-900', 'text-white', 'shadow-xs');
                        t.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
                    });
                    tab.classList.add('bg-slate-900', 'text-white', 'shadow-xs');
                    tab.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');

                    currentCategory = tab.getAttribute('data-filter') || 'all';
                    filterCards();
                });
            });
        });
    </script>
    <!-- END: Search & Filter Interaction Script -->

</body>
</html>
