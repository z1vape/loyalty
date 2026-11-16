<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Z1 App - Portal Karyawan')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "background": "#f8f9ff",
              "surface": "#f8f9ff",
              "surface-container-low": "#eff4ff",
              "surface-container": "#e5eeff",
              "surface-container-high": "#dce9ff",
              "surface-container-lowest": "#ffffff",
              "on-surface": "#0b1c30",
              "on-surface-variant": "#45464d",
              "primary": "#006c49",
              "on-primary": "#ffffff",
              "secondary": "#006c49",
              "secondary-container": "#6cf8bb",
              "on-secondary-container": "#00714d",
              "outline": "#76777d",
              "outline-variant": "#c6c6cd",
              "error": "#ba1a1a",
              "error-container": "#ffdad6",
            },
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              mono: ["JetBrains Mono", "monospace"],
            }
          }
        }
      }
    </script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 20px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        .custom-slim-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-slim-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-slim-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .custom-slim-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        #z1-profile-dropdown:not(.hidden) {
            transform-origin: top right;
            animation: dropdownFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans antialiased min-h-screen flex flex-col selection:bg-secondary-container selection:text-on-secondary-container">
@php
    $currentUser = auth()->user();
    $activeStoreId = session('active_store_id');
    $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : ($activeStore ?? null);
    $userStores = $currentUser 
        ? ($currentUser->hasRole('Super Admin') ? \App\Models\Store::all() : $currentUser->stores) 
        : collect();
@endphp

    <div class="flex min-h-screen">
        <!-- Desktop Sidebar (Khusus Karyawan - Beda dengan Admin) -->
        <aside class="w-64 bg-surface-container-lowest border-r border-outline-variant/60 flex flex-col shrink-0 hidden lg:flex sticky top-0 h-screen z-30 shadow-xs">
            <!-- Brand Section -->
            <div class="p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#006c49] to-[#004d34] text-white flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-2xl">badge</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-black text-base text-on-surface tracking-tight">Z1 App</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 uppercase tracking-wider">Staff</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant leading-none mt-0.5">Portal Mandiri Karyawan</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Links: 3 Menu Utama Karyawan -->
            <div class="flex-1 overflow-y-auto custom-slim-scroll p-3 space-y-1.5">
                <div class="px-3 pt-2 pb-1 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                    Menu Karyawan
                </div>

                <!-- 1. Absensi -->
                @can('Z1 App Attendance')
                <a href="{{ route('z1-app.index', ['tab' => 'attendance']) }}" 
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ ($activeTab ?? 'attendance') === 'attendance' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[19px]">schedule</span>
                        <span>Absensi Karyawan</span>
                    </div>
                    @if(($activeTab ?? 'attendance') === 'attendance')
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    @endif
                </a>
                @endcan

                <!-- 2. Order Barang -->
                @can('Z1 App Purchase Request')
                <a href="{{ route('z1-app.index', ['tab' => 'purchase_request']) }}" 
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ ($activeTab ?? '') === 'purchase_request' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[19px]">local_shipping</span>
                        <span>Order Barang</span>
                    </div>
                    @if(($activeTab ?? '') === 'purchase_request')
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    @endif
                </a>
                @endcan

                <!-- 3. Laporan Penjualan -->
                @can('Z1 App Sales Report')
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report']) }}" 
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ ($activeTab ?? '') === 'sales_report' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[19px]">payments</span>
                        <span>Laporan Penjualan</span>
                    </div>
                    @if(($activeTab ?? '') === 'sales_report')
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    @endif
                </a>
                @endcan
            </div>
        </aside>

        <!-- Mobile Drawer Backdrop -->
        <div id="z1-mobile-overlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden backdrop-blur-xs transition-opacity duration-300"></div>

        <!-- Mobile Drawer (Khusus Karyawan) -->
        <aside id="z1-mobile-drawer" class="fixed inset-y-0 left-0 z-50 flex flex-col h-[100dvh] w-72 bg-surface-container-lowest border-r border-outline-variant -translate-x-full lg:hidden transition-all duration-300 ease-in-out shadow-2xl">
            <div class="flex items-center justify-between p-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-[#006c49] text-white flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">badge</span>
                    </span>
                    <span class="font-black text-sm text-on-surface">Z1 App</span>
                </div>
                <button type="button" id="z1-mobile-close-btn" class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-1 text-xs">
                <div class="px-3 pt-2 pb-1 text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                    Menu Karyawan
                </div>
                @can('Z1 App Attendance')
                <a href="{{ route('z1-app.index', ['tab' => 'attendance']) }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl font-bold {{ ($activeTab ?? 'attendance') === 'attendance' ? 'bg-[#006c49] text-white' : 'text-on-surface hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined text-[19px]">schedule</span>
                    <span>Absensi Karyawan</span>
                </a>
                @endcan
                @can('Z1 App Purchase Request')
                <a href="{{ route('z1-app.index', ['tab' => 'purchase_request']) }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl font-bold {{ ($activeTab ?? '') === 'purchase_request' ? 'bg-[#006c49] text-white' : 'text-on-surface hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined text-[19px]">local_shipping</span>
                    <span>Order Barang</span>
                </a>
                @endcan
                @can('Z1 App Sales Report')
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report']) }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl font-bold {{ ($activeTab ?? '') === 'sales_report' ? 'bg-[#006c49] text-white' : 'text-on-surface hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined text-[19px]">payments</span>
                    <span>Laporan Penjualan</span>
                </a>
                @endcan
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top App Bar -->
            <header class="h-16 bg-surface-container-lowest border-b border-outline-variant/60 px-4 md:px-6 flex items-center justify-between sticky top-0 z-20 shadow-xs">
                <div class="flex items-center gap-3">
                    <button type="button" id="z1-mobile-toggle-btn" class="p-1.5 rounded-xl text-on-surface hover:bg-surface-container-low lg:hidden">
                        <span class="material-symbols-outlined text-2xl">menu</span>
                    </button>
                    <div>
                        <span class="font-extrabold text-sm md:text-base text-on-surface block leading-tight">
                            @if(($activeTab ?? 'attendance') === 'attendance')
                                Absensi Karyawan
                            @elseif(($activeTab ?? '') === 'purchase_request')
                                Order Barang
                            @else
                                Laporan Penjualan
                            @endif
                        </span>
                        <span class="text-[11px] text-on-surface-variant hidden sm:inline">Portal Mandiri Karyawan</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Store Switcher Combobox (Pindah ke atas, dapat memilih jika diassign lebih dari 1 cabang) -->
                    @if($activeStore)
                        @if($userStores->count() > 1)
                            <form action="{{ route('switch-store') }}" method="POST" id="z1-store-switcher-form" class="relative flex items-center">
                                @csrf
                                <input type="hidden" name="store_id" id="z1-selected-store-id-input" value="{{ $activeStoreId }}">
                                
                                <!-- Custom Combobox Trigger Button -->
                                <button type="button" id="z1-store-combobox-trigger" class="flex items-center gap-1.5 bg-surface-container-low hover:bg-surface-container-high text-on-surface text-xs font-semibold px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-xl border border-outline-variant transition-all cursor-pointer shadow-xs select-none">
                                    <span class="material-symbols-outlined text-sm sm:text-base text-secondary">storefront</span>
                                    <span class="truncate max-w-[90px] sm:max-w-[150px] md:max-w-[200px] font-bold">{{ $activeStore->name }}</span>
                                    <span class="material-symbols-outlined text-sm text-on-surface-variant transition-transform duration-200" id="z1-store-combobox-arrow">expand_more</span>
                                </button>

                                <!-- Custom Combobox Dropdown Menu -->
                                <div id="z1-store-combobox-menu" class="absolute right-0 top-full mt-2 w-64 bg-white border border-slate-200/90 rounded-xl shadow-xl py-1.5 hidden z-50 transition-all">
                                    @if($userStores->count() > 3)
                                        <div class="px-2 pb-1.5 mb-1 border-b border-outline-variant/60">
                                            <input type="text" id="z1-store-search-input" placeholder="Cari cabang toko..." class="w-full px-2.5 py-1.5 text-xs bg-surface-container-low rounded-lg border border-outline-variant focus:outline-none focus:ring-1 focus:ring-secondary">
                                        </div>
                                    @endif
                                    <div class="max-h-56 overflow-y-auto no-scrollbar space-y-0.5 px-1" id="z1-store-options-list">
                                        @foreach($userStores as $store)
                                            <button type="button" onclick="selectZ1Store('{{ $store->id }}')" class="z1-store-option-item w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ $store->id == $activeStoreId ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface hover:bg-surface-container-high' }}">
                                                <span class="truncate">{{ $store->name }}</span>
                                                @if($store->id == $activeStoreId)
                                                    <span class="material-symbols-outlined text-sm text-on-secondary-container">check</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </form>
                        @else
                            <!-- Single Assigned Store Badge -->
                            <div class="flex items-center gap-1.5 bg-surface-container-low text-on-surface text-xs font-bold px-3 py-2 rounded-xl border border-outline-variant/80 shadow-xs">
                                <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                                <span class="truncate max-w-[120px] sm:max-w-[180px]">{{ $activeStore->name }}</span>
                            </div>
                        @endif
                    @endif

                    <!-- User Profile Dropdown (Mengarah ke Z1 Admin, My Profile, dan Logout) -->
                    <div class="relative">
                        <div id="z1-profile-trigger" class="flex items-center gap-2.5 cursor-pointer hover:bg-surface-container-low p-1.5 rounded-xl transition-all select-none">
                            @if(auth()->user()->photo)
                                <img class="w-8 h-8 rounded-full border border-outline-variant object-cover" src="{{ Storage::url(auth()->user()->photo) }}" alt="{{ auth()->user()->name }}"/>
                            @else
                                <div class="w-8 h-8 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm font-bold text-[#0b1c30] hidden sm:inline">{{ auth()->user()->name }}</span>
                        </div>

                        <!-- Dropdown Menu (Sesuai Gambar dengan opsi Z1 Admin) -->
                        <div id="z1-profile-dropdown" class="absolute right-0 mt-2 w-64 bg-white border border-slate-200/90 rounded-xl shadow-xl overflow-hidden hidden z-50">
                            <!-- User Info Header -->
                            <div class="bg-[#f4f7fc] px-4 py-3 border-b border-slate-200/80">
                                <p class="text-sm font-bold text-[#0b1c30]">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-1">
                                <!-- My Profile -->
                                <a href="{{ route('profile.show') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline">
                                    <span class="material-symbols-outlined text-[19px] text-slate-600">person</span>
                                    <span>My Profile</span>
                                </a>

                                <!-- Z1 Admin (Kembali ke Tampilan Admin) -->
                                @php
                                    $canAdmin = auth()->user()->hasRole('Super Admin') 
                                        || auth()->user()->hasRole('Manager') 
                                        || auth()->user()->can('Dashboard List') 
                                        || auth()->user()->can('Inventory List') 
                                        || auth()->user()->can('Stock In List') 
                                        || auth()->user()->can('Stock Out List') 
                                        || auth()->user()->can('Sales List') 
                                        || auth()->user()->can('Report List') 
                                        || auth()->user()->can('Attendance List') 
                                        || auth()->user()->can('User List');
                                @endphp
                                @if($canAdmin)
                                <a href="{{ route('dashboard') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between font-medium no-underline group">
                                    <span class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[19px] text-[#006c49]">badge</span>
                                        <span class="group-hover:text-[#006c49] transition-colors">Z1 Admin</span>
                                    </span>
                                    <span class="material-symbols-outlined text-[17px] text-slate-500 group-hover:text-[#006c49] transition-colors">open_in_new</span>
                                </a>
                                @endif

                                <div class="my-1 border-t border-slate-200/80"></div>

                                <!-- Logout -->
                                <form id="z1-dropdown-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                                <button type="button" onclick="document.getElementById('z1-dropdown-logout-form').submit();" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50/70 transition-colors flex items-center gap-3 font-medium cursor-pointer">
                                    <span class="material-symbols-outlined text-[19px] text-red-600">logout</span>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3.5 sm:p-5 md:p-6 lg:p-8 pb-24 lg:pb-8 max-w-[1400px] w-full mx-auto">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 text-xs font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-rose-600">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">warning</span>
                            <span>Mohon periksa kembali form berikut:</span>
                        </div>
                        <ul class="list-disc pl-6 font-medium text-[11px]">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Page Footer (Hidden on mobile to save screen space, bottom nav replaces it) -->
            <footer class="hidden lg:block py-4 text-center text-xs text-on-surface-variant border-t border-outline-variant/40 bg-surface-container-lowest">
                <span>Z1 App • Portal Karyawan & Staff Cabang • Terintegrasi dengan Inventory Stock</span>
            </footer>

            <!-- Mobile Bottom Navigation Bar (Khusus Smartphone) -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur-md border-t border-outline-variant/60 shadow-xl px-2 py-1.5 flex items-center justify-around">
                <!-- 1. Absensi -->
                @can('Z1 App Attendance')
                <a href="{{ route('z1-app.index', ['tab' => 'attendance']) }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-center transition-all {{ ($activeTab ?? 'attendance') === 'attendance' ? 'text-[#006c49] font-black' : 'text-on-surface-variant font-medium hover:text-on-surface' }}">
                    <div class="relative">
                        <span class="material-symbols-outlined text-[22px] {{ ($activeTab ?? 'attendance') === 'attendance' ? 'text-[#006c49]' : '' }}">schedule</span>
                        @if(($activeTab ?? 'attendance') === 'attendance')
                            <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#006c49]"></span>
                        @endif
                    </div>
                    <span class="text-[10px] leading-tight mt-0.5">Absensi</span>
                </a>
                @endcan

                <!-- 2. Order Barang -->
                @can('Z1 App Purchase Request')
                <a href="{{ route('z1-app.index', ['tab' => 'purchase_request']) }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-center transition-all {{ ($activeTab ?? '') === 'purchase_request' ? 'text-[#006c49] font-black' : 'text-on-surface-variant font-medium hover:text-on-surface' }}">
                    <div class="relative">
                        <span class="material-symbols-outlined text-[22px] {{ ($activeTab ?? '') === 'purchase_request' ? 'text-[#006c49]' : '' }}">local_shipping</span>
                        @if(($activeTab ?? '') === 'purchase_request')
                            <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#006c49]"></span>
                        @endif
                    </div>
                    <span class="text-[10px] leading-tight mt-0.5">Order Barang</span>
                </a>
                @endcan

                <!-- 3. Laporan Penjualan -->
                @can('Z1 App Sales Report')
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report']) }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-center transition-all {{ ($activeTab ?? '') === 'sales_report' ? 'text-[#006c49] font-black' : 'text-on-surface-variant font-medium hover:text-on-surface' }}">
                    <div class="relative">
                        <span class="material-symbols-outlined text-[22px] {{ ($activeTab ?? '') === 'sales_report' ? 'text-[#006c49]' : '' }}">payments</span>
                        @if(($activeTab ?? '') === 'sales_report')
                            <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#006c49]"></span>
                        @endif
                    </div>
                    <span class="text-[10px] leading-tight mt-0.5">Laporan</span>
                </a>
                @endcan
            </nav>
        </div>
    </div>

    <!-- Scripts for Store Switcher, Drawer & Profile -->
    <script>
        // Store Combobox Switcher
        function selectZ1Store(storeId) {
            const input = document.getElementById('z1-selected-store-id-input');
            const form = document.getElementById('z1-store-switcher-form');
            if (input && form) {
                input.value = storeId;
                form.submit();
            }
        }

        const z1StoreTrigger = document.getElementById('z1-store-combobox-trigger');
        const z1StoreMenu = document.getElementById('z1-store-combobox-menu');
        const z1StoreArrow = document.getElementById('z1-store-combobox-arrow');
        const z1StoreSearch = document.getElementById('z1-store-search-input');

        if (z1StoreTrigger && z1StoreMenu) {
            z1StoreTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = z1StoreMenu.classList.contains('hidden');
                z1StoreMenu.classList.toggle('hidden');
                if (z1StoreArrow) z1StoreArrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                if (isHidden && z1StoreSearch) z1StoreSearch.focus();
            });

            document.addEventListener('click', function(e) {
                if (!z1StoreMenu.contains(e.target) && !z1StoreTrigger.contains(e.target)) {
                    z1StoreMenu.classList.add('hidden');
                    if (z1StoreArrow) z1StoreArrow.style.transform = 'rotate(0deg)';
                }
            });

            if (z1StoreSearch) {
                z1StoreSearch.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase();
                    document.querySelectorAll('.z1-store-option-item').forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(query) ? 'flex' : 'none';
                    });
                });
            }
        }

        // Mobile Drawer toggle
        const toggleBtn = document.getElementById('z1-mobile-toggle-btn');
        const closeBtn = document.getElementById('z1-mobile-close-btn');
        const drawer = document.getElementById('z1-mobile-drawer');
        const overlay = document.getElementById('z1-mobile-overlay');

        function openDrawer() {
            if (drawer && overlay) {
                drawer.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        }
        function closeDrawer() {
            if (drawer && overlay) {
                drawer.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);

        // Profile Dropdown Toggle
        const z1ProfileTrigger = document.getElementById('z1-profile-trigger');
        const z1ProfileDropdown = document.getElementById('z1-profile-dropdown');

        if (z1ProfileTrigger && z1ProfileDropdown) {
            z1ProfileTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                z1ProfileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!z1ProfileDropdown.contains(e.target) && !z1ProfileTrigger.contains(e.target)) {
                    z1ProfileDropdown.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
