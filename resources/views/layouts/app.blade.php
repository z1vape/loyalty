<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Z1 VapeOS | Retail & Inventory Dashboard')</title>
    
    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    
    <!-- Prevent FOUC (Flash of Unstyled Content) -->
    <style>
        html { visibility: hidden; opacity: 0; }
    </style>
    <style type="text/tailwindcss">
        html { visibility: visible; opacity: 1; transition: opacity 0.3s ease-in; }
    </style>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "background": "#f8f9ff",
                    "tertiary-fixed": "#ffdbca",
                    "surface-container-high": "#dce9ff",
                    "surface": "#f8f9ff",
                    "secondary-container": "#6cf8bb",
                    "primary-fixed-dim": "#bec6e0",
                    "surface-variant": "#d3e4fe",
                    "secondary-fixed-dim": "#4edea3",
                    "on-secondary-fixed": "#002113",
                    "primary-container": "#131b2e",
                    "on-secondary-fixed-variant": "#005236",
                    "surface-dim": "#cbdbf5",
                    "on-secondary-container": "#00714d",
                    "inverse-surface": "#213145",
                    "on-primary-container": "#7c839b",
                    "on-error": "#ffffff",
                    "on-secondary": "#ffffff",
                    "on-tertiary-container": "#d95f00",
                    "on-background": "#0b1c30",
                    "outline": "#76777d",
                    "error": "#ba1a1a",
                    "on-tertiary": "#ffffff",
                    "tertiary-fixed-dim": "#ffb690",
                    "outline-variant": "#c6c6cd",
                    "surface-container": "#e5eeff",
                    "surface-bright": "#f8f9ff",
                    "error-container": "#ffdad6",
                    "on-primary": "#ffffff",
                    "on-tertiary-fixed-variant": "#783200",
                    "inverse-primary": "#bec6e0",
                    "on-surface-variant": "#45464d",
                    "secondary-fixed": "#6ffbbe",
                    "on-surface": "#0b1c30",
                    "surface-container-highest": "#d3e4fe",
                    "on-tertiary-fixed": "#341100",
                    "primary": "#006c49",
                    "on-error-container": "#93000a",
                    "on-primary-fixed-variant": "#3f465c",
                    "on-primary-fixed": "#131b2e",
                    "surface-tint": "#565e74",
                    "inverse-on-surface": "#eaf1ff",
                    "tertiary-container": "#341100",
                    "secondary": "#006c49",
                    "surface-container-low": "#eff4ff",
                    "surface-container-lowest": "#ffffff",
                    "primary-fixed": "#dae2fd"
            },
            "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
            },
            "spacing": {
                    "stack-lg": "2rem",
                    "stack-md": "1rem",
                    "container-max": "1440px",
                    "stack-sm": "0.5rem",
                    "gutter": "1.5rem",
                    "margin-x": "2rem"
            },
            "fontFamily": {
                    "headline-lg-mobile": ["Inter"],
                    "table-data": ["Inter"],
                    "headline-md": ["Inter"],
                    "headline-lg": ["Inter"],
                    "body-md": ["Inter"],
                    "label-sm": ["JetBrains Mono"],
                    "body-lg": ["Inter"]
            },
            "fontSize": {
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "table-data": ["14px", {"lineHeight": "20px", "fontWeight": "500"}],
                    "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "headline-lg": ["30px", {"lineHeight": "38px", "letterSpacing": "-0.02em", "fontWeight": "600"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "letterSpacing": "0.02em", "fontWeight": "500"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
            }
          }
        }
      }
    </script>
    <script>
    // Profile Dropdown Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const profileTrigger = document.getElementById('profile-trigger');
        const profileDropdown = document.getElementById('profile-dropdown');

        if(profileTrigger && profileDropdown) {
            profileTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileDropdown.contains(e.target) && !profileTrigger.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
    });

    // Sidebar active state logic
    const path = window.location.pathname;
    </script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: 'liga';
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Custom slim scrollbar */
        .custom-slim-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(118, 119, 125, 0.25) transparent;
        }
        .custom-slim-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .custom-slim-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-slim-scroll::-webkit-scrollbar-thumb {
            background: rgba(118, 119, 125, 0.25);
            border-radius: 9999px;
        }
        .custom-slim-scroll::-webkit-scrollbar-thumb:hover {
            background: #006c49;
        }

        /* Top Nav Dropdown animation */
        .top-nav-menu:not(.hidden) {
            transform-origin: top;
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
        /* Global Clean Material TomSelect Customization */
        .ts-wrapper {
            width: 100% !important;
        }
        .ts-wrapper.single .ts-control {
            border-radius: 0.75rem !important;
            border: 1px solid #c6c6cd !important;
            padding: 0.45rem 2rem 0.45rem 0.75rem !important;
            background-color: #f0f3fa !important;
            min-height: 38px !important;
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            color: #0b1c30 !important;
            box-shadow: none !important;
            transition: all 0.15s ease-in-out !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.65rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.15em 1.15em !important;
            cursor: pointer !important;
        }
        .ts-wrapper.single.focus .ts-control {
            background-color: #ffffff !important;
            border-color: #006c49 !important;
            box-shadow: 0 0 0 2px rgba(0, 108, 73, 0.2) !important;
        }
        .ts-wrapper.single .ts-control input {
            font-size: 0.75rem !important;
            color: #0b1c30 !important;
            cursor: pointer !important;
        }
        .ts-wrapper.single .ts-control .item {
            font-size: 0.75rem !important;
            color: #0b1c30 !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 90% !important;
        }
        .ts-wrapper .ts-dropdown {
            border-radius: 0.75rem !important;
            border: 1px solid #c6c6cd !important;
            background-color: #ffffff !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            z-index: 100 !important;
            font-size: 0.75rem !important;
            overflow: hidden !important;
            margin-top: 4px !important;
        }
        .ts-wrapper .ts-dropdown .ts-dropdown-content {
            max-height: 240px !important;
            overflow-y: auto !important;
        }
        .ts-wrapper .ts-dropdown .option {
            padding: 0.55rem 0.85rem !important;
            font-size: 0.75rem !important;
            color: #0b1c30 !important;
            transition: background-color 0.1s ease !important;
            cursor: pointer !important;
        }
        .ts-wrapper .ts-dropdown .option.active,
        .ts-wrapper .ts-dropdown .option:hover {
            background-color: #e5eeff !important;
            color: #006c49 !important;
            font-weight: 600 !important;
        }
        .ts-wrapper .ts-dropdown .option.selected {
            background-color: #dce9ff !important;
            color: #006c49 !important;
            font-weight: 700 !important;
        }

        /* Global Print Stylesheet */
        @media print {
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: auto;
                margin: 10mm 15mm 10mm 15mm;
            }

            html, body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #0b1c30 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            header,
            nav,
            aside,
            footer,
            #mobile-drawer,
            #mobile-drawer-overlay,
            #flash-success-modal,
            .print\:hidden {
                display: none !important;
            }

            div:has(> main),
            .min-h-\[calc\(100vh-4rem\)\] {
                min-height: auto !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .printable-report {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            tr, .divide-y > div {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        /* HR Sidebar Minimized (Rail / Icon-Only) Mode */
        #hr-sidebar.is-minimized {
            width: 4.25rem !important; /* 68px */
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        #hr-header-brand.is-minimized {
            width: 4.25rem !important; /* 68px */
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            align-items: center !important;
            justify-content: center !important;
        }

        #hr-header-brand.is-minimized .hr-brand-text {
            display: none !important;
        }

        #hr-header-brand.is-minimized .hr-mini-text {
            display: block !important;
        }

        #hr-sidebar.is-minimized .hr-nav-text,
        #hr-sidebar.is-minimized .hr-section-title,
        #hr-sidebar.is-minimized .hr-toggle-label {
            display: none !important;
        }

        #hr-sidebar.is-minimized .hr-section-divider {
            display: block !important;
        }

        #hr-sidebar.is-minimized .hr-nav-item {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 2.75rem !important;
            height: 2.5rem !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        #hr-sidebar.is-minimized .hr-footer-box {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        #hr-sidebar.is-minimized .hr-toggle-icon {
            transform: rotate(180deg);
        }

        #hr-sidebar:not(.is-minimized) .hr-section-divider {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background text-on-surface font-body-md overflow-x-hidden pb-16 md:pb-0 print:bg-white print:p-0 print:m-0">

@auth
@php
    $currentUser = auth()->user();
    $activeStoreId = session('active_store_id');
    $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;
    $userStores = $currentUser->hasRole('Super Admin') 
        ? \App\Models\Store::all() 
        : $currentUser->stores;

    $canOperation = $currentUser->can('Dashboard List') 
        || $currentUser->can('Inventory List') 
        || $currentUser->can('Stock Out Create') 
        || $currentUser->can('Sales List');

    $canPurchasing = $currentUser->can('Stock In Create') 
        || $currentUser->can('Movement List') 
        || $currentUser->can('Accounting Payable Manage')
        || $currentUser->can('Z1 App Purchase Request');

    $canFinance = $currentUser->can('Income List') 
        || $currentUser->can('Expense List');

    $canAccounting = $currentUser->can('Accounting CoA List') 
        || $currentUser->can('Accounting Journal List') 
        || $currentUser->can('Accounting Ledger View') 
        || $currentUser->can('Accounting Transfer Manage') 
        || $currentUser->can('Accounting Period Manage');

    $canReports = $currentUser->can('Report List') 
        || $currentUser->can('Report SKU') 
        || $currentUser->can('Report Category') 
        || $currentUser->can('Report Staff') 
        || $currentUser->can('Sales List')
        || $currentUser->can('Accounting Report View');

    $canCommercial = $currentUser->can('Attendance List')
        || $currentUser->can('Payroll List');

    $canMaster = $currentUser->can('Store List') 
        || $currentUser->can('User List') 
        || $currentUser->can('Role List') 
        || $currentUser->can('Setting List')
        || $currentUser->can('Shift List')
        || $currentUser->can('Attendance Status List')
        || $currentUser->can('User Schedule List')
        || $currentUser->can('Shift Pattern List');

    $isOpActive = request()->routeIs('dashboard') || request()->is('/') 
        || (request()->is('inventory*') && !request()->is('inventory/*/stock-card*')) 
        || request()->is('stock-card*') 
        || request()->is('stock-adjustments*') 
        || request()->routeIs('stock-transfers.*') 
        || (request()->is('movements*') && request('type') === 'out') 
        || request()->routeIs('sales.*');

    $isComActive = request()->routeIs('commercial.attendances.*')
        || request()->routeIs('commercial.payrolls.*')
        || request()->routeIs('commercial.monitoring.*');

    $isHrModule = request()->routeIs('commercial.hr*')
        || request()->routeIs('commercial.attendances.*')
        || request()->routeIs('commercial.payrolls.*')
        || request()->routeIs('commercial.shifts.*')
        || request()->routeIs('commercial.attendance-statuses.*')
        || request()->routeIs('commercial.schedules.*')
        || request()->routeIs('commercial.shift-patterns.*');

    $isPurActive = request()->routeIs('purchasing.*')
        || (request()->is('movements*') && request('type') === 'in') 
        || request()->is('movement-dates*') 
        || request()->is('accounting/payables*');

    $isFinActive = request()->routeIs('finance.dashboard')
        || request()->is('incomes*') 
        || request()->is('expenses*');

    $isAccActive = request()->routeIs('accounting.dashboard')
        || request()->is('accounting/accounts*') 
        || request()->is('accounting/journals*') 
        || request()->is('accounting/ledger*') 
        || request()->is('accounting/transfers*') 
        || request()->is('accounting/periods*');

    $isRepActive = request()->routeIs('reports.*')
        || request()->is('reports*') 
        || request()->routeIs('accounting.reports.*')
        || request()->is('accounting/reports*');

    $isMasterActive = request()->routeIs('master-data.*')
        || request()->is('master-data*')
        || request()->is('stores*') 
        || request()->is('users*') 
        || request()->is('roles*') 
        || request()->is('settings*');
@endphp
@endauth

<!-- TopNavBar (Option A: Single-Row Header) -->
<header class="flex justify-between items-center px-3 sm:px-4 {{ $isHrModule ? 'lg:px-0 lg:pr-6' : 'lg:px-6' }} sticky top-0 z-40 bg-surface-container-lowest border-b border-outline-variant w-full h-16 shadow-xs print:hidden">
    <div class="flex items-center {{ $isHrModule ? 'h-16' : 'gap-2 sm:gap-3 lg:gap-4' }} shrink-0">
        <!-- Mobile/Tablet Hamburger Toggle Button (lg:hidden) -->
        <button type="button" id="mobile-nav-toggle-btn" class="lg:hidden p-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all cursor-pointer focus:outline-none flex items-center justify-center active:scale-95" title="Menu Navigasi">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>

        <!-- Brand Logo -->
        @if($isHrModule ?? false)
        {{-- Mobile HR Title (Clean text, no badge icon, no dock_to_left) --}}
        <div class="lg:hidden flex flex-col justify-center ml-1">
            <h1 class="text-xs sm:text-sm font-black text-on-surface uppercase tracking-wider leading-tight">Human Resources</h1>
            <p class="text-[9px] sm:text-[10px] text-on-surface-variant font-medium leading-tight">People & Operations</p>
        </div>

        {{-- Desktop HR Header Brand Box: Aligned with sidebar width and line, Clean text only (No badge icon, No dock_to_left) --}}
        <div id="hr-header-brand" class="hidden lg:flex flex-col justify-center w-64 xl:w-72 h-16 px-4 xl:px-5 border-r border-outline-variant bg-surface-container-lowest shrink-0 transition-all duration-200">
            <div class="hr-brand-text">
                <h1 class="text-xs font-black text-on-surface uppercase tracking-wider leading-tight">Human Resources</h1>
                <p class="text-[10px] text-on-surface-variant font-medium leading-tight mt-0.5">People & Operations</p>
            </div>
            <div class="hr-mini-text font-black text-xs text-primary hidden">HR</div>
        </div>
        @else
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 sm:gap-2.5 font-headline-md text-base sm:text-headline-md text-on-surface font-extrabold tracking-tight shrink-0 mr-1 xl:mr-3">
            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#006c49] to-[#005236] text-white flex items-center justify-center shadow-sm shrink-0">
                <span class="material-symbols-outlined text-[19px]">inventory_2</span>
            </span>
            <span class="font-extrabold hidden sm:inline-block">Z1 VapeOS</span>
        </a>
        @endif

        <!-- Desktop Core Top Navigation (Visible on lg+) -->
        @auth
        @if($isMasterActive)
        <div class="hidden lg:flex items-center gap-2 pl-3 border-l border-outline-variant/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all no-underline">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Dashboard Utama</span>
            </a>
            <span class="text-outline-variant/80">/</span>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-secondary-container/50 text-on-secondary-container font-extrabold text-xs border border-secondary/30">
                <span class="material-symbols-outlined text-[17px]">dataset</span>
                <span>Master Data</span>
            </div>
        </div>
        @elseif($isRepActive)
        <div class="hidden lg:flex items-center gap-2 pl-3 border-l border-outline-variant/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all no-underline">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Dashboard Utama</span>
            </a>
            <span class="text-outline-variant/80">/</span>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-secondary-container/50 text-on-secondary-container font-extrabold text-xs border border-secondary/30">
                <span class="material-symbols-outlined text-[17px]">analytics</span>
                <span>Reports & Analitik</span>
            </div>
        </div>
        @elseif($isHrModule)
        <div class="hidden lg:flex items-center gap-2 pl-4 lg:pl-6">
            <a href="{{ route('portal') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all no-underline">
                <span class="material-symbols-outlined text-[18px]">apps</span>
                <span>Portal Direktori</span>
            </a>
            <span class="text-outline-variant/80">/</span>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-secondary-container/50 text-on-secondary-container font-extrabold text-xs border border-secondary/30">
                <span class="material-symbols-outlined text-[17px]">badge</span>
                <span>Human Resources</span>
            </div>
        </div>
        @elseif(request()->routeIs('commercial.monitoring.*'))
        <div class="hidden lg:flex items-center gap-2 pl-3 border-l border-outline-variant/60">
            <a href="{{ route('portal') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all no-underline">
                <span class="material-symbols-outlined text-[18px]">apps</span>
                <span>Portal Direktori</span>
            </a>
            <span class="text-outline-variant/80">/</span>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-secondary-container/50 text-on-secondary-container font-extrabold text-xs border border-secondary/30">
                <span class="material-symbols-outlined text-[17px]">videocam</span>
                <span>Security &amp; Surveillance</span>
            </div>
        </div>
        @else
        <nav class="hidden lg:flex items-center gap-1 xl:gap-1.5">
            {{-- 1. Operation Core Menu --}}
            @if($canOperation)
            <div class="relative top-nav-dropdown-wrapper">
                <button type="button" data-dropdown-trigger="dropdown-operation" 
                    class="top-nav-btn flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {{ $isOpActive ? 'bg-secondary-container text-on-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">precision_manufacturing</span>
                    <span>Operation</span>
                    <span class="material-symbols-outlined text-[16px] transition-transform duration-200 dropdown-arrow">expand_more</span>
                </button>
                <div id="dropdown-operation" class="top-nav-menu absolute left-0 top-full mt-2 w-64 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 hidden z-50">
                    @can('Dashboard List')
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('dashboard') || request()->is('/') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">dashboard</span>
                        <span class="truncate">Dashboard Operasional</span>
                    </a>
                    @endcan
                    @can('Inventory List')
                    <a href="{{ route('inventory.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('inventory*') && !request()->is('inventory/*/stock-card*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">inventory_2</span>
                        <span class="truncate">Inventory List</span>
                    </a>
                    <a href="{{ route('stock-card.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('stock-card*') || request()->is('inventory/*/stock-card*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">history_edu</span>
                        <span class="truncate">Kartu Stok</span>
                    </a>
                    <a href="{{ route('stock-adjustments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('stock-adjustments*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">tune</span>
                        <span class="truncate">Penyesuaian Stok (Opname)</span>
                    </a>
                    <a href="{{ route('stock-transfers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('stock-transfers.*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">local_shipping</span>
                        <span class="truncate">Mutasi Antar Cabang</span>
                    </a>
                    @endcan
                    @can('Stock Out Create')
                    <a href="{{ route('movements.index', ['type' => 'out']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'out' ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">outbound</span>
                        <span class="truncate">Stock Out (Mutasi Keluar)</span>
                    </a>
                    @endcan
                    @can('Sales List')
                    <a href="{{ route('sales.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('sales.*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">receipt_long</span>
                        <span class="truncate">POS Sales History</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 2. Purchasing Core Menu --}}
            @if($canPurchasing)
            <div class="relative top-nav-dropdown-wrapper">
                <button type="button" data-dropdown-trigger="dropdown-purchasing" 
                    class="top-nav-btn flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {{ $isPurActive ? 'bg-secondary-container text-on-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">shopping_bag</span>
                    <span>Purchasing</span>
                    <span class="material-symbols-outlined text-[16px] transition-transform duration-200 dropdown-arrow">expand_more</span>
                </button>
                <div id="dropdown-purchasing" class="top-nav-menu absolute left-0 top-full mt-2 w-64 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 hidden z-50">
                    <a href="{{ route('purchasing.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('purchasing.dashboard') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">dashboard</span>
                        <span class="truncate">Dashboard Purchasing</span>
                    </a>
                    <a href="{{ route('purchasing.orders.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('purchasing.orders.*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">assignment</span>
                        <span class="truncate">Order Barang (Purchase Request)</span>
                    </a>
                    <a href="{{ route('purchasing.purchase-orders.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('purchasing.purchase-orders.*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">shopping_bag</span>
                        <span class="truncate">Purchase Order (PO Supplier)</span>
                    </a>
                    @can('Stock In Create')
                    <a href="{{ route('movements.index', ['type' => 'in']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'in' ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">add_box</span>
                        <span class="truncate">Stock In (Penerimaan Barang)</span>
                    </a>
                    @endcan
                    @can('Accounting Payable Manage')
                    <a href="{{ route('accounting.payables.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/payables*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">pending_actions</span>
                        <span class="truncate">Hutang Supplier</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 3. Finance Core Menu --}}
            @if($canFinance)
            <div class="relative top-nav-dropdown-wrapper">
                <button type="button" data-dropdown-trigger="dropdown-finance" 
                    class="top-nav-btn flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {{ $isFinActive ? 'bg-secondary-container text-on-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">account_balance_wallet</span>
                    <span>Finance</span>
                    <span class="material-symbols-outlined text-[16px] transition-transform duration-200 dropdown-arrow">expand_more</span>
                </button>
                <div id="dropdown-finance" class="top-nav-menu absolute left-0 top-full mt-2 w-64 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 hidden z-50">
                    <a href="{{ route('finance.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('finance.dashboard') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">dashboard</span>
                        <span class="truncate">Dashboard Finance</span>
                    </a>
                    @can('Income List')
                    <a href="{{ route('incomes.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('incomes*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">savings</span>
                        <span class="truncate">Pemasukan Toko</span>
                    </a>
                    @endcan
                    @can('Expense List')
                    <a href="{{ route('expenses.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('expenses*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">payments</span>
                        <span class="truncate">Pengeluaran Toko</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- 4. Accounting Core Menu --}}
            @if($canAccounting)
            <div class="relative top-nav-dropdown-wrapper">
                <button type="button" data-dropdown-trigger="dropdown-accounting" 
                    class="top-nav-btn flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {{ $isAccActive ? 'bg-secondary-container text-on-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[19px] shrink-0">account_balance</span>
                    <span>Accounting</span>
                    <span class="material-symbols-outlined text-[16px] transition-transform duration-200 dropdown-arrow">expand_more</span>
                </button>
                <div id="dropdown-accounting" class="top-nav-menu absolute left-0 top-full mt-2 w-64 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 hidden z-50">
                    <a href="{{ route('accounting.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('accounting.dashboard') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">dashboard</span>
                        <span class="truncate">Dashboard Accounting</span>
                    </a>
                    @can('Accounting CoA List')
                    <a href="{{ route('accounting.accounts.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/accounts*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">account_tree</span>
                        <span class="truncate">Bagan Akun (CoA)</span>
                    </a>
                    @endcan
                    @can('Accounting Journal List')
                    <a href="{{ route('accounting.journals.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/journals*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">receipt_long</span>
                        <span class="truncate">Jurnal Transaksi</span>
                    </a>
                    @endcan
                    @can('Accounting Ledger View')
                    <a href="{{ route('accounting.journals.ledger') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/ledger*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">menu_book</span>
                        <span class="truncate">Buku Besar (Ledger)</span>
                    </a>
                    @endcan
                    @can('Accounting Transfer Manage')
                    <a href="{{ route('accounting.transfers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/transfers*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">swap_horiz</span>
                        <span class="truncate">Transfer Kas/Bank</span>
                    </a>
                    @endcan
                    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('Accounting Journal Create') || auth()->user()->can('Accounting Transfer Manage'))
                    <a href="{{ route('accounting.reconciliations.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/reconciliations*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">account_balance_wallet</span>
                        <span class="truncate">Rekonsiliasi Bank</span>
                    </a>
                    @endif
                    @can('Accounting Period Manage')
                    <a href="{{ route('accounting.periods.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->is('accounting/periods*') ? 'bg-secondary-container/60 text-on-secondary-container font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">lock_clock</span>
                        <span class="truncate">Tutup Buku</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif
        </nav>
        @endif
        @endauth
    </div>

    <!-- Right Header Controls (Store Switcher, Notifications, Profile) -->
    <div class="flex items-center gap-2 md:gap-3">
        @auth

            @if(!$isHrModule && $activeStore)
                @if($userStores->count() > 1)
                    <form action="{{ route('switch-store') }}" method="POST" id="store-switcher-form" class="relative flex items-center mr-1 sm:mr-2">
                        @csrf
                        <input type="hidden" name="store_id" id="selected-store-id-input" value="{{ $activeStoreId }}">
                        
                        <!-- Custom Combobox Trigger Button -->
                        <button type="button" id="store-combobox-trigger" class="flex items-center gap-1.5 bg-surface-container-low hover:bg-surface-container-high text-on-surface text-xs font-semibold px-2 py-1.5 sm:pl-3 sm:pr-2.5 sm:py-1.5 rounded-xl border border-outline-variant transition-all cursor-pointer shadow-xs">
                            <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                            <span class="truncate max-w-[80px] sm:max-w-[120px] md:max-w-none">{{ $activeStore->name }}</span>
                            <span class="material-symbols-outlined text-sm text-on-surface-variant transition-transform duration-200" id="store-combobox-arrow">expand_more</span>
                        </button>

                        <!-- Custom Combobox Dropdown Menu -->
                        <div id="store-combobox-menu" class="absolute right-0 top-full mt-2 w-56 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xl py-1.5 hidden z-50 transition-all">
                            @if($userStores->count() > 3)
                                <div class="px-2 pb-1.5 mb-1 border-b border-outline-variant/60">
                                    <input type="text" id="store-search-input" placeholder="Cari toko..." class="w-full px-2.5 py-1 text-xs bg-surface-container-low rounded-lg border border-outline-variant focus:outline-none focus:ring-1 focus:ring-secondary">
                                </div>
                            @endif
                            <div class="max-h-56 overflow-y-auto no-scrollbar space-y-0.5 px-1" id="store-options-list">
                                @foreach($userStores as $store)
                                    <button type="button" onclick="selectStore('{{ $store->id }}')" class="store-option-item w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ $store->id == $activeStoreId ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface hover:bg-surface-container-high' }}">
                                        <span class="truncate">{{ $store->name }}</span>
                                        @if($store->id == $activeStoreId)
                                            <span class="material-symbols-outlined text-sm text-on-secondary-container">check</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const trigger = document.getElementById('store-combobox-trigger');
                            const menu = document.getElementById('store-combobox-menu');
                            const arrow = document.getElementById('store-combobox-arrow');
                            const searchInput = document.getElementById('store-search-input');

                            if (trigger && menu) {
                                trigger.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    const isHidden = menu.classList.contains('hidden');
                                    menu.classList.toggle('hidden');
                                    if (arrow) arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                                    if (isHidden && searchInput) searchInput.focus();
                                });

                                document.addEventListener('click', function(e) {
                                    if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                                        menu.classList.add('hidden');
                                        if (arrow) arrow.style.transform = 'rotate(0deg)';
                                    }
                                });

                                if (searchInput) {
                                    searchInput.addEventListener('input', function(e) {
                                        const query = e.target.value.toLowerCase();
                                        document.querySelectorAll('.store-option-item').forEach(item => {
                                            const text = item.textContent.toLowerCase();
                                            item.style.display = text.includes(query) ? 'flex' : 'none';
                                        });
                                    });
                                }
                            }
                        });

                        function selectStore(storeId) {
                            document.getElementById('selected-store-id-input').value = storeId;
                            document.getElementById('store-switcher-form').submit();
                        }
                    </script>
                @else
                    <div class="flex items-center gap-1.5 px-2 py-1.5 sm:px-3 sm:py-1.5 bg-surface-container-low text-on-surface text-xs font-semibold rounded-xl border border-outline-variant mr-1 sm:mr-2">
                        <span class="material-symbols-outlined text-sm text-on-surface-variant">storefront</span>
                        <span class="truncate max-w-[80px] sm:max-w-[120px] md:max-w-none">{{ $activeStore->name }}</span>
                    </div>
                @endif
            @endif
        @endauth

        <div class="flex items-center gap-1.5 md:gap-stack-md text-on-surface-variant font-medium">
            @if(!$isHrModule)
            <span class="material-symbols-outlined cursor-pointer hover:bg-surface-container-low p-2 rounded-full transition-all">notifications</span>
            @endif
            @auth
            <div class="relative">
                <div id="profile-trigger" class="flex items-center gap-2.5 cursor-pointer hover:bg-surface-container-low p-1.5 rounded-xl transition-all select-none">
                    @if(auth()->user()->photo)
                        <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-outline-variant object-cover" src="{{ Storage::url(auth()->user()->photo) }}" alt="{{ auth()->user()->name }}"/>
                    @else
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="text-sm font-bold text-on-surface hidden sm:inline">{{ auth()->user()->name }}</span>
                </div>

                <!-- Dropdown -->
                <div id="profile-dropdown" class="absolute right-0 mt-2 w-64 bg-white border border-slate-200/90 rounded-xl shadow-xl overflow-hidden hidden z-50">
                    <div class="bg-[#f4f7fc] px-4 py-3 border-b border-slate-200/80">
                        <p class="text-sm font-bold text-[#0b1c30]">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="py-1">
                        @if($isHrModule)
                        <a href="{{ route('profile.show') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline">
                            <span class="material-symbols-outlined text-[19px] text-slate-600">person</span>
                            <span>My Profile</span>
                        </a>
                        <div class="my-1 border-t border-slate-200/80"></div>
                        <form id="dropdown-logout-form-hr" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                        <button onclick="document.getElementById('dropdown-logout-form-hr').submit();" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50/70 transition-colors flex items-center gap-3 font-medium cursor-pointer">
                            <span class="material-symbols-outlined text-[19px] text-red-600">logout</span>
                            <span>Logout</span>
                        </button>
                        @else
                        <a href="{{ route('portal') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline group">
                            <span class="material-symbols-outlined text-[19px] text-[#006c49]">apps</span>
                            <span class="group-hover:text-[#006c49] transition-colors">Portal Direktori</span>
                        </a>
                        <a href="{{ route('profile.show') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline">
                            <span class="material-symbols-outlined text-[19px] text-slate-600">person</span>
                            <span>My Profile</span>
                        </a>
                        @can('Z1 App Access')
                        <a href="{{ route('z1-app.index') }}" target="_blank" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between font-medium no-underline group">
                            <span class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[19px] text-[#006c49]">badge</span>
                                <span class="group-hover:text-[#006c49] transition-colors">Z1 App</span>
                            </span>
                            <span class="material-symbols-outlined text-[17px] text-slate-500 group-hover:text-[#006c49] transition-colors">open_in_new</span>
                        </a>
                        @endcan
                        @if($canMaster)
                        <a href="{{ route('master-data.index') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline group {{ $isMasterActive ? 'bg-secondary-container/30 text-[#006c49] font-bold' : '' }}">
                            <span class="material-symbols-outlined text-[19px] text-[#006c49]">dataset</span>
                            <span class="group-hover:text-[#006c49] transition-colors">Master Data</span>
                        </a>
                        @endif
                        @if($canReports)
                        <a href="{{ route('reports.by-sku') }}" class="w-full px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-3 font-medium no-underline group {{ $isRepActive ? 'bg-secondary-container/30 text-[#006c49] font-bold' : '' }}">
                            <span class="material-symbols-outlined text-[19px] text-[#006c49]">analytics</span>
                            <span class="group-hover:text-[#006c49] transition-colors">Reports</span>
                        </a>
                        @endif
                        <div class="my-1 border-t border-slate-200/80"></div>
                        <form id="dropdown-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                        <button onclick="document.getElementById('dropdown-logout-form').submit();" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50/70 transition-colors flex items-center gap-3 font-medium cursor-pointer">
                            <span class="material-symbols-outlined text-[19px] text-red-600">logout</span>
                            <span>Logout</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endauth
        </div>
    </div>
</header>

<!-- Mobile Drawer Backdrop Overlay -->
<div id="mobile-drawer-overlay" class="fixed inset-0 bg-black/40 z-50 hidden lg:hidden backdrop-blur-xs transition-opacity duration-300 print:hidden"></div>

<!-- Mobile Drawer (Offcanvas Menu for < lg) -->
@auth
<aside id="mobile-drawer" class="fixed inset-y-0 left-0 z-50 flex flex-col h-[100dvh] w-72 bg-surface-container-low border-r border-outline-variant shrink-0 -translate-x-full lg:hidden transition-all duration-300 ease-in-out shadow-2xl print:hidden">
    <!-- Mobile Drawer Header -->
    <div class="flex items-center justify-between p-4 border-b border-outline-variant/60 bg-surface-container-lowest">
        <div class="flex items-center gap-2.5">
            @if($isHrModule ?? false)
            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#006c49] to-[#005236] text-white flex items-center justify-center shadow-xs">
                <span class="material-symbols-outlined text-[17px]">badge</span>
            </span>
            <span class="font-extrabold text-sm text-on-surface">Human Resources</span>
            @else
            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#006c49] to-[#005236] text-white flex items-center justify-center shadow-xs">
                <span class="material-symbols-outlined text-[17px]">inventory_2</span>
            </span>
            <span class="font-extrabold text-sm text-on-surface">Z1 VapeOS</span>
            @endif
        </div>
        <button type="button" id="mobile-drawer-close-btn" class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low">
            <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
    </div>

    <!-- Scrollable Menu Groups -->
    <div class="flex-1 overflow-y-auto custom-slim-scroll p-3 space-y-3.5 text-xs">
        @if(!$isHrModule)
        <!-- Z1 APP Mobile Link -->
        @can('Z1 App Access')
        <a href="{{ route('z1-app.index') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-secondary-container text-on-secondary-container font-extrabold text-xs shadow-xs no-underline active:scale-95 transition-all border border-secondary/30">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">badge</span>
                <span>Z1 App (Karyawan)</span>
            </div>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
        @endcan

        <div class="grid grid-cols-2 gap-2">
            @if($canMaster)
            <a href="{{ route('master-data.index') }}" class="flex items-center gap-2 p-2 rounded-xl {{ $isMasterActive ? 'bg-secondary-container text-on-secondary-container font-extrabold border border-secondary/30' : 'bg-surface-container text-on-surface hover:bg-surface-container-high' }} text-xs no-underline transition-all">
                <span class="material-symbols-outlined text-[18px] text-[#006c49]">dataset</span>
                <span class="truncate">Master Data</span>
            </a>
            @endif
            @if($canReports)
            <a href="{{ route('reports.by-sku') }}" class="flex items-center gap-2 p-2 rounded-xl {{ $isRepActive ? 'bg-secondary-container text-on-secondary-container font-extrabold border border-secondary/30' : 'bg-surface-container text-on-surface hover:bg-surface-container-high' }} text-xs no-underline transition-all">
                <span class="material-symbols-outlined text-[18px] text-[#006c49]">analytics</span>
                <span class="truncate">Reports</span>
            </a>
            @endif
        </div>
        @endif

        {{-- Human Resources Navigation (Visible when inside HR module) --}}
        @if($isHrModule)
        <div>
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">badge</span>
                <span>Human Resources</span>
            </div>
            <div class="space-y-0.5">
                <a href="{{ route('commercial.hr.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.hr.dashboard') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    <span>Dashboard HR</span>
                </a>
                @can('Attendance List')
                <a href="{{ route('commercial.attendances.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.attendances.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                    <span>Daftar Kehadiran</span>
                </a>
                @endcan
                @can('Payroll List')
                <a href="{{ route('commercial.payrolls.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.payrolls.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                    <span>Payroll Employee</span>
                </a>
                @endcan
                @can('Shift List')
                <a href="{{ route('commercial.shifts.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.shifts.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                    <span>Shift Kerja</span>
                </a>
                @endcan
                @can('Attendance Status List')
                <a href="{{ route('commercial.attendance-statuses.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.attendance-statuses.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">label</span>
                    <span>Status Kehadiran</span>
                </a>
                @endcan
                @can('User Schedule List')
                <a href="{{ route('commercial.schedules.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.schedules.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                    <span>Jadwal Kerja</span>
                </a>
                @endcan
                @can('Shift Pattern List')
                <a href="{{ route('commercial.shift-patterns.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('commercial.shift-patterns.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                    <span>Shift Pattern</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        @if(!$isHrModule)

        {{-- 1. Operation --}}
        @if($canOperation)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">precision_manufacturing</span>
                <span>Operation</span>
            </div>
            <div class="space-y-0.5">
                @can('Dashboard List')
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('dashboard') || request()->is('/') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    <span>Dashboard Operasional</span>
                </a>
                @endcan
                @can('Inventory List')
                <a href="{{ route('inventory.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('inventory*') && !request()->is('inventory/*/stock-card*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    <span>Inventory List</span>
                </a>
                <a href="{{ route('stock-card.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('stock-card*') || request()->is('inventory/*/stock-card*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">history_edu</span>
                    <span>Kartu Stok</span>
                </a>
                <a href="{{ route('stock-adjustments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('stock-adjustments*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">tune</span>
                    <span>Penyesuaian Stok</span>
                </a>
                <a href="{{ route('stock-transfers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('stock-transfers.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                    <span>Mutasi Antar Cabang</span>
                </a>
                @endcan
                @can('Stock Out Create')
                <a href="{{ route('movements.index', ['type' => 'out']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'out' ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">outbound</span>
                    <span>Stock Out</span>
                </a>
                @endcan
                @can('Sales List')
                <a href="{{ route('sales.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('sales.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>Sales History</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- 2. Purchasing --}}
        @if($canPurchasing)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">shopping_bag</span>
                <span>Purchasing</span>
            </div>
            <div class="space-y-0.5">
                <a href="{{ route('purchasing.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('purchasing.dashboard') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    <span>Dashboard Purchasing</span>
                </a>
                <a href="{{ route('purchasing.orders.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('purchasing.orders.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">assignment</span>
                    <span>Order Barang (Purchase Request)</span>
                </a>
                <a href="{{ route('purchasing.purchase-orders.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('purchasing.purchase-orders.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                    <span>Purchase Order (PO Supplier)</span>
                </a>
                @can('Stock In Create')
                <a href="{{ route('movements.index', ['type' => 'in']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'in' ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">add_box</span>
                    <span>Stock In (Barang Masuk)</span>
                </a>
                @endcan
                @can('Accounting Payable Manage')
                <a href="{{ route('accounting.payables.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/payables*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">pending_actions</span>
                    <span>Hutang Supplier</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- 3. Finance --}}
        @if($canFinance)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">account_balance_wallet</span>
                <span>Finance</span>
            </div>
            <div class="space-y-0.5">
                <a href="{{ route('finance.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('finance.dashboard') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    <span>Dashboard Finance</span>
                </a>
                @can('Income List')
                <a href="{{ route('incomes.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('incomes*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                    <span>Pemasukan Toko</span>
                </a>
                @endcan
                @can('Expense List')
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('expenses*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                    <span>Pengeluaran Toko</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- 4. Accounting --}}
        @if($canAccounting)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">account_balance</span>
                <span>Accounting</span>
            </div>
            <div class="space-y-0.5">
                <a href="{{ route('accounting.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('accounting.dashboard') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    <span>Dashboard Accounting</span>
                </a>
                @can('Accounting CoA List')
                <a href="{{ route('accounting.accounts.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/accounts*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">account_tree</span>
                    <span>Bagan Akun (CoA)</span>
                </a>
                @endcan
                @can('Accounting Journal List')
                <a href="{{ route('accounting.journals.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/journals*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>Jurnal Transaksi</span>
                </a>
                @endcan
                @can('Accounting Ledger View')
                <a href="{{ route('accounting.journals.ledger') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/ledger*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">menu_book</span>
                    <span>Buku Besar (Ledger)</span>
                </a>
                @endcan
                @can('Accounting Transfer Manage')
                <a href="{{ route('accounting.transfers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/transfers*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                    <span>Transfer Kas/Bank</span>
                </a>
                @endcan
                @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('Accounting Journal Create') || auth()->user()->can('Accounting Transfer Manage'))
                <a href="{{ route('accounting.reconciliations.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/reconciliations*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    <span>Rekonsiliasi Bank</span>
                </a>
                @endif
                @can('Accounting Period Manage')
                <a href="{{ route('accounting.periods.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('accounting/periods*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">lock_clock</span>
                    <span>Tutup Buku</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- 4. Reports --}}
        @if($canReports)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">analytics</span>
                <span>Reports</span>
            </div>
            <div class="space-y-0.5">
                <a href="{{ route('reports.store-performance') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.store-performance*') ? 'bg-[#006c49] text-white font-bold' : 'text-emerald-900 bg-emerald-50/70 font-semibold' }}">
                    <span class="material-symbols-outlined text-[18px]">monitoring</span>
                    <span>Performa Toko (BI)</span>
                </a>
                @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
                <a href="{{ route('reports.by-sku') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.by-sku') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                    <span>Penjualan per SKU</span>
                </a>
                @endif
                @if(auth()->user()->can('Report Category') || auth()->user()->can('Report List'))
                <a href="{{ route('reports.by-category') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.by-category') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">donut_large</span>
                    <span>Penjualan per Kategori</span>
                </a>
                @endif
                @if(auth()->user()->can('Report Staff') || auth()->user()->can('Report List'))
                <a href="{{ route('reports.by-staff') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.by-staff') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                    <span>Penjualan per Staff</span>
                </a>
                @endif
                @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
                <a href="{{ route('reports.top-suppliers') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.top-suppliers*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                    <span>Rekap Belanja Supplier</span>
                </a>
                @endif
                @can('Inventory List')
                <a href="{{ route('reports.low-stock') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('reports.low-stock') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                    <span>Stok Kritis</span>
                </a>
                @endcan

                @can('Accounting Report View')
                <div class="border-t border-outline-variant/60 my-1 pt-1"></div>
                <a href="{{ route('accounting.reports.profit-loss') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('accounting.reports.profit*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">trending_up</span>
                    <span>Laba Rugi (P&L)</span>
                </a>
                <a href="{{ route('accounting.reports.balance-sheet') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    <span>Neraca Keuangan</span>
                </a>
                <a href="{{ route('accounting.reports.trial-balance') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('accounting.reports.trial-balance') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">balance</span>
                    <span>Neraca Saldo</span>
                </a>
                <a href="{{ route('accounting.reports.cash-flow') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                    <span>Arus Kas (Cash Flow)</span>
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- 5. Master Data --}}
        @if($canMaster)
        <div class="pt-2 border-t border-outline-variant/60">
            <div class="px-2 pb-1.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-secondary">dataset</span>
                <span>Master Data</span>
            </div>
            <div class="space-y-0.5">
                @can('Store List')
                <a href="{{ route('stores.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('stores*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">storefront</span>
                    <span>Toko / Cabang</span>
                </a>
                @endcan
                @can('User List')
                <a href="{{ route('users.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('users*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">group</span>
                    <span>User & Karyawan</span>
                </a>
                @endcan
                @can('Role List')
                <a href="{{ route('roles.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('roles*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                    <span>Role & Permission</span>
                </a>
                @endcan
                @can('Setting List')
                <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->is('settings*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">settings</span>
                    <span>Pengaturan Sistem</span>
                </a>
                @endcan
                @if($currentUser->hasRole('Super Admin') || $currentUser->can('Role List') || $currentUser->can('Setting List'))
                <a href="{{ route('master-data.approval-workflows.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl {{ request()->routeIs('master-data.approval-workflows.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                    <span>Approval Workflow</span>
                </a>
                @endif
            </div>
        </div>
        @endif
        @endif
    </div>

    <!-- Mobile Drawer Footer: Quick Logout -->
    <div class="p-3 border-t border-outline-variant/60 bg-surface-container-low shrink-0">
        <form id="mobile-drawer-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
        <button type="button" onclick="document.getElementById('mobile-drawer-logout-form').submit();" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold text-error hover:bg-error-container/20 transition-all cursor-pointer">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span>Logout</span>
        </button>
    </div>
</aside>
@endauth

<!-- Main Page Layout Container -->
<div class="min-h-[calc(100vh-4rem)] bg-background flex flex-col lg:flex-row print:min-h-0 print:bg-white print:p-0 print:m-0">
    @auth
    @if($isMasterActive)
    {{-- Left Sidebar: Master Data --}}
    <aside class="hidden lg:flex flex-col w-64 xl:w-72 bg-surface-container-lowest border-r border-outline-variant shrink-0 sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto custom-slim-scroll p-4 space-y-4 print:hidden">
        {{-- Header Sidebar --}}
        <div class="px-2 py-1.5 flex items-center justify-between border-b border-outline-variant/60 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold shadow-2xs">
                    <span class="material-symbols-outlined text-[19px]">dataset</span>
                </span>
                <div>
                    <h3 class="text-xs font-black text-on-surface uppercase tracking-wider">Master Data</h3>
                    <p class="text-[10px] text-on-surface-variant font-medium">Pengaturan & Roster</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" title="Kembali ke Dashboard Utama" class="p-1 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </a>
        </div>

        {{-- Navigation Menu --}}
        <nav class="space-y-1 text-xs">
            <a href="{{ route('master-data.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('master-data.index') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[19px]">dashboard_customize</span>
                <span>Master Data Hub</span>
            </a>

            <div class="pt-2.5 pb-1 px-3 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Cabang & Pengguna</div>
            @can('Store List')
            <a href="{{ route('stores.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->is('stores*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                <span class="truncate">Toko / Cabang</span>
            </a>
            @endcan
            @can('User List')
            <a href="{{ route('users.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->is('users*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span class="truncate">User & Karyawan</span>
            </a>
            @endcan
            @can('Role List')
            <a href="{{ route('roles.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->is('roles*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                <span class="truncate">Role & Permission</span>
            </a>
            @endcan
            @can('Setting List')
            <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->is('settings*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">settings</span>
                <span class="truncate">Pengaturan Sistem</span>
            </a>
            @endcan
            @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('Role List') || auth()->user()->can('Setting List'))
            <a href="{{ route('master-data.approval-workflows.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('master-data.approval-workflows.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">verified_user</span>
                <span class="truncate">Approval Workflow</span>
            </a>
            @endif
        </nav>

        {{-- Footer Sidebar --}}
        <div class="mt-auto pt-4 border-t border-outline-variant/60">
            <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-bold text-xs transition-colors border border-outline-variant/60 no-underline shadow-2xs">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Dashboard Utama</span>
            </a>
        </div>
    </aside>
    @elseif($isRepActive)
    {{-- Left Sidebar: Reports --}}
    <aside class="hidden lg:flex flex-col w-64 xl:w-72 bg-surface-container-lowest border-r border-outline-variant shrink-0 sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto custom-slim-scroll p-4 space-y-4 print:hidden">
        {{-- Navigation Menu --}}
        <nav class="space-y-1 text-xs">
            <div class="pt-1 pb-1 px-3 text-[10px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[14px]">query_stats</span>
                <span>Executive & BI</span>
            </div>
            <a href="{{ route('reports.store-performance') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.store-performance*') ? 'bg-[#006c49] text-white font-extrabold shadow-sm' : 'text-emerald-900 bg-emerald-50/60 hover:bg-emerald-100/60 font-bold border border-emerald-200/50' }}">
                <span class="material-symbols-outlined text-[18px]">monitoring</span>
                <span class="truncate">Performa Toko (BI)</span>
            </a>

            <div class="my-2 border-t border-outline-variant/50"></div>

            <div class="pt-1 pb-1 px-3 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Penjualan & Stok</div>
            @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-sku') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.by-sku*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                <span class="truncate">Penjualan per SKU</span>
            </a>
            @endif
            @if(auth()->user()->can('Report Category') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-category') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.by-category') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">donut_large</span>
                <span class="truncate">Penjualan per Kategori</span>
            </a>
            @endif
            @if(auth()->user()->can('Report Staff') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-staff') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.by-staff') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">badge</span>
                <span class="truncate">Penjualan per Staff</span>
            </a>
            @endif
            @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.top-suppliers') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.top-suppliers*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                <span class="truncate">Rekap Belanja Supplier</span>
            </a>
            @endif
            @can('Inventory List')
            <a href="{{ route('reports.low-stock') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('reports.low-stock') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">warning</span>
                <span class="truncate">Stok Kritis (Low Stock)</span>
            </a>
            @endcan

            @can('Accounting Report View')
            <div class="pt-3 pb-1 px-3 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Laporan Keuangan</div>
            <a href="{{ route('accounting.reports.profit-loss') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('accounting.reports.profit*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">trending_up</span>
                <span class="truncate">Laba Rugi (P&L)</span>
            </a>
            <a href="{{ route('accounting.reports.balance-sheet') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                <span class="truncate">Neraca Keuangan</span>
            </a>
            <a href="{{ route('accounting.reports.trial-balance') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('accounting.reports.trial-balance') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">balance</span>
                <span class="truncate">Neraca Saldo</span>
            </a>
            <a href="{{ route('accounting.reports.cash-flow') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                <span class="truncate">Arus Kas (Cash Flow)</span>
            </a>
            @endcan
        </nav>
    </aside>
    @elseif($isHrModule)
    {{-- Left Sidebar: Human Resources --}}
    <aside id="hr-sidebar" class="hidden lg:flex flex-col w-64 xl:w-72 bg-surface-container-lowest border-r border-outline-variant shrink-0 sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto custom-slim-scroll p-4 space-y-4 print:hidden transition-all duration-200">
        {{-- Navigation Menu --}}
        <nav class="space-y-1 text-xs flex-1">
            <a href="{{ route('commercial.hr.dashboard') }}" title="Dashboard HR" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.hr.dashboard') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">dashboard</span>
                <span class="truncate hr-nav-text">Dashboard HR</span>
            </a>

            <div class="hr-section-title pt-2 pb-1 px-3 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Presensi & Penggajian</div>
            <div class="hr-section-divider my-2 border-t border-outline-variant/60"></div>

            @can('Attendance List')
            <a href="{{ route('commercial.attendances.index') }}" title="Daftar Kehadiran" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.attendances.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">how_to_reg</span>
                <span class="truncate hr-nav-text">Daftar Kehadiran</span>
            </a>
            @endcan

            @can('Payroll List')
            <a href="{{ route('commercial.payrolls.index') }}" title="Payroll Employee" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.payrolls.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">payments</span>
                <span class="truncate hr-nav-text">Payroll Employee</span>
            </a>
            @endcan

            <div class="hr-section-title pt-3 pb-1 px-3 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Manajemen Shift & Roster</div>
            <div class="hr-section-divider my-2 border-t border-outline-variant/60"></div>

            @can('Shift List')
            <a href="{{ route('commercial.shifts.index') }}" title="Shift Kerja" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.shifts.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">schedule</span>
                <span class="truncate hr-nav-text">Shift Kerja</span>
            </a>
            @endcan

            @can('Attendance Status List')
            <a href="{{ route('commercial.attendance-statuses.index') }}" title="Status Kehadiran" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.attendance-statuses.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">fact_check</span>
                <span class="truncate hr-nav-text">Status Kehadiran</span>
            </a>
            @endcan

            @can('User Schedule List')
            <a href="{{ route('commercial.schedules.index') }}" title="Jadwal Kerja (Roster)" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.schedules.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">calendar_month</span>
                <span class="truncate hr-nav-text">Jadwal Kerja (Roster)</span>
            </a>
            @endcan

            @can('Shift Pattern List')
            <a href="{{ route('commercial.shift-patterns.index') }}" title="Shift Pattern" class="hr-nav-item flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('commercial.shift-patterns.*') ? 'bg-secondary-container text-on-secondary-container font-extrabold shadow-2xs' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-medium' }}">
                <span class="material-symbols-outlined text-[18px] shrink-0">published_with_changes</span>
                <span class="truncate hr-nav-text">Shift Pattern</span>
            </a>
            @endcan
        </nav>

        {{-- Footer Sidebar: Toggle Minimize / Expand --}}
        <div class="hr-footer-box mt-auto pt-3 border-t border-outline-variant/60">
            <button type="button" onclick="toggleHrSidebar()" title="Minimize / Perluas Sidebar" class="hr-nav-item w-full flex items-center justify-between px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface transition-all cursor-pointer border border-outline-variant/60 shadow-2xs">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] shrink-0">dock_to_left</span>
                    <span class="truncate hr-toggle-label text-[11px] font-bold">Minimize Sidebar</span>
                </span>
                <span class="material-symbols-outlined text-[18px] shrink-0 hr-toggle-icon">chevron_left</span>
            </button>
        </div>
    </aside>
    @endif
    @endauth

    <main class="flex-1 min-w-0 p-4 md:p-6 lg:p-8 {{ $isMasterActive || $isRepActive || $isHrModule ? 'max-w-none' : 'max-w-[1600px] mx-auto' }} w-full print:p-0 print:m-0 print:max-w-full">
        <!-- Centered Success Modal Popup (Auto-dismiss & Center Dialog) -->
        @if(session('success'))
            <div id="flash-success-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs transition-opacity duration-300">
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl p-6 sm:p-7 max-w-sm w-full text-center flex flex-col items-center transform transition-all duration-200 scale-100 animate-in fade-in zoom-in-95">
                    <!-- Icon circle -->
                    <div class="w-14 h-14 rounded-full bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center mb-3.5 shadow-sm">
                        <span class="material-symbols-outlined text-3xl font-bold">check_circle</span>
                    </div>

                    <!-- Title & Message -->
                    <h3 class="text-lg font-extrabold text-on-surface mb-1">Berhasil!</h3>
                    <p class="text-xs text-on-surface-variant leading-relaxed mb-5">
                        {{ session('success') }}
                    </p>

                    <!-- Dismiss Button -->
                    <button type="button" onclick="closeSuccessModal()" class="w-full py-2.5 px-4 rounded-xl bg-[#006c49] text-white hover:bg-[#00553a] font-bold text-xs uppercase tracking-wider transition-all active:scale-95 shadow-sm cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>

            <script>
                function closeSuccessModal() {
                    const modal = document.getElementById('flash-success-modal');
                    if (modal) {
                        modal.style.opacity = '0';
                        modal.style.transition = 'opacity 0.25s ease-out';
                        setTimeout(() => modal.remove(), 250);
                    }
                }

                // Auto dismiss after 2.5 seconds
                setTimeout(() => {
                    closeSuccessModal();
                }, 2500);

                // Close on backdrop click or Escape key
                document.addEventListener('DOMContentLoaded', () => {
                    const modal = document.getElementById('flash-success-modal');
                    if (modal) {
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) closeSuccessModal();
                        });
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeSuccessModal();
                });
            </script>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mb-stack-md p-4 bg-error-container text-error rounded-xl flex flex-col gap-2 border border-error/20 shadow-sm transition-all duration-300">
                <div class="flex items-center gap-stack-md">
                    <span class="material-symbols-outlined">error</span>
                    <div class="font-bold flex-1">Please correct the errors below:</div>
                </div>
                <ul class="list-disc pl-10 text-body-md font-medium text-error">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

@if(!$isHrModule)
<!-- Mobile Bottom Navigation -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-surface-container-lowest border-t border-outline-variant h-16 flex justify-around items-center px-2 z-50 print:hidden">
    @can('Dashboard List')
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ request()->is('/') || request()->is('dashboard*') ? 'text-primary' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[22px]">dashboard</span>
        <span class="text-[9px] font-semibold {{ request()->is('/') || request()->is('dashboard*') ? 'font-bold' : '' }}">Home</span>
    </a>
    @endcan
    @can('Stock In Create')
    <a href="{{ route('movements.index', ['type' => 'in']) }}" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'in' ? 'text-primary' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[22px]">add_box</span>
        <span class="text-[9px] font-semibold {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'in' ? 'font-bold' : '' }}">In</span>
    </a>
    @endcan
    @can('Stock Out Create')
    <a href="{{ route('movements.index', ['type' => 'out']) }}" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'out' ? 'text-primary' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[22px]">outbound</span>
        <span class="text-[9px] font-semibold {{ (request()->is('movements*') || request()->is('movement-dates*')) && request('type') === 'out' ? 'font-bold' : '' }}">Out</span>
    </a>
    @endcan
    @can('Inventory List')
    <a href="{{ route('inventory.index') }}" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ request()->is('inventory*') ? 'text-primary' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[22px]">inventory_2</span>
        <span class="text-[9px] font-semibold {{ request()->is('inventory*') ? 'font-bold' : '' }}">List</span>
    </a>
    @endcan
    @can('Movement List')
    <a href="{{ route('movements.index') }}" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ (request()->is('movements*') || request()->is('movement-dates*')) && !in_array(request('type'), ['in', 'out']) ? 'text-primary' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[22px]">receipt_long</span>
        <span class="text-[9px] font-semibold {{ (request()->is('movements*') || request()->is('movement-dates*')) && !in_array(request('type'), ['in', 'out']) ? 'font-bold' : '' }}">Records</span>
    </a>
    @endcan

    {{-- More button: tampil jika ada Reports, Akuntansi, atau Master Data --}}
    @auth
        @php
            $hasReports = auth()->user()->can('Sales List') || auth()->user()->can('Report List') || auth()->user()->can('Report SKU') || auth()->user()->can('Report Category') || auth()->user()->can('Report Staff');
            $hasAccounting = auth()->user()->can('Accounting CoA List') || auth()->user()->can('Accounting Journal List') || auth()->user()->can('Accounting Ledger View') || auth()->user()->can('Accounting Report View') || auth()->user()->can('Accounting Transfer Manage') || auth()->user()->can('Accounting Payable Manage') || auth()->user()->can('Accounting Period Manage');
            $hasMaster  = auth()->user()->can('Income List') || auth()->user()->can('Expense List') || auth()->user()->can('User List') || auth()->user()->can('Store List') || auth()->user()->can('Role List') || auth()->user()->can('Setting List');
            $isMoreActive = request()->is('sales*') || request()->is('incomes*') || request()->is('expenses*') || request()->is('reports*') || request()->is('accounting*') || request()->is('users*') || request()->is('stores*') || request()->is('roles*') || request()->is('settings*');
        @endphp
        @if($hasReports || $hasAccounting || $hasMaster)
        <button type="button" id="more-nav-btn" onclick="openMobileDrawer()"
            class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-xl transition-colors {{ $isMoreActive ? 'text-primary' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined text-[22px]">more_horiz</span>
            <span class="text-[9px] font-semibold {{ $isMoreActive ? 'font-bold' : '' }}">More</span>
        </button>
        @endif
    @endauth
</nav>
@endif

<script>
    // HR Sidebar Toggle (Minimize to icon-only / Expand)
    function toggleHrSidebar() {
        const sidebar = document.getElementById('hr-sidebar');
        const headerBrand = document.getElementById('hr-header-brand');
        if (!sidebar) return;

        const isMinimized = sidebar.classList.contains('is-minimized');
        if (isMinimized) {
            // Expand sidebar (Full text + icons)
            sidebar.classList.remove('is-minimized');
            if (headerBrand) headerBrand.classList.remove('is-minimized');
            localStorage.setItem('z1_hr_sidebar_minimized', 'false');
        } else {
            // Minimize sidebar (Only icons visible)
            sidebar.classList.add('is-minimized');
            if (headerBrand) headerBrand.classList.add('is-minimized');
            localStorage.setItem('z1_hr_sidebar_minimized', 'true');
        }
    }

    // Apply HR Sidebar initial state from localStorage
    if (localStorage.getItem('z1_hr_sidebar_minimized') === 'true') {
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('hr-sidebar');
            const headerBrand = document.getElementById('hr-header-brand');
            if (sidebar) sidebar.classList.add('is-minimized');
            if (headerBrand) headerBrand.classList.add('is-minimized');
        });
    }

    // Mobile Drawer controller
    function openMobileDrawer() {
        const mobileDrawer = document.getElementById('mobile-drawer');
        const mobileOverlay = document.getElementById('mobile-drawer-overlay');
        if (!mobileDrawer) return;
        mobileDrawer.classList.remove('-translate-x-full');
        if (mobileOverlay) mobileOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
        const mobileDrawer = document.getElementById('mobile-drawer');
        const mobileOverlay = document.getElementById('mobile-drawer-overlay');
        if (!mobileDrawer) return;
        mobileDrawer.classList.add('-translate-x-full');
        if (mobileOverlay) mobileOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('mobile-nav-toggle-btn');
        const closeBtn = document.getElementById('mobile-drawer-close-btn');
        const mobileOverlay = document.getElementById('mobile-drawer-overlay');

        if (toggleBtn) toggleBtn.addEventListener('click', openMobileDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeMobileDrawer);
        if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileDrawer);

        // Close drawer on link click
        document.querySelectorAll('#mobile-drawer a').forEach(link => {
            link.addEventListener('click', closeMobileDrawer);
        });

        // Top Navigation Dropdowns (Desktop)
        const triggers = document.querySelectorAll('[data-dropdown-trigger]');
        triggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const targetId = this.getAttribute('data-dropdown-trigger');
                const targetMenu = document.getElementById(targetId);
                const arrow = this.querySelector('.dropdown-arrow');
                
                // Close other top nav dropdowns
                document.querySelectorAll('.top-nav-menu').forEach(menu => {
                    if (menu.id !== targetId) menu.classList.add('hidden');
                });
                document.querySelectorAll('.top-nav-dropdown-wrapper .dropdown-arrow').forEach(arr => {
                    if (arr !== arrow) arr.style.transform = 'rotate(0deg)';
                });

                // Toggle current dropdown
                if (targetMenu) {
                    const isHidden = targetMenu.classList.contains('hidden');
                    targetMenu.classList.toggle('hidden');
                    if (arrow) arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
        });

        // Close dropdowns on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.top-nav-dropdown-wrapper')) {
                document.querySelectorAll('.top-nav-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
                document.querySelectorAll('.top-nav-dropdown-wrapper .dropdown-arrow').forEach(arr => {
                    arr.style.transform = 'rotate(0deg)';
                });
            }
        });

        // Close dropdowns & mobile drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.top-nav-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
                document.querySelectorAll('.top-nav-dropdown-wrapper .dropdown-arrow').forEach(arr => {
                    arr.style.transform = 'rotate(0deg)';
                });
                closeMobileDrawer();
            }
        });

        // Keyboard Shortcut: Ctrl+M or Cmd+M to toggle mobile drawer (if on small screen)
        window.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && (e.key === 'm' || e.key === 'M')) {
                e.preventDefault();
                const mobileDrawer = document.getElementById('mobile-drawer');
                if (mobileDrawer) {
                    if (mobileDrawer.classList.contains('-translate-x-full')) {
                        openMobileDrawer();
                    } else {
                        closeMobileDrawer();
                    }
                }
            }
        });
    });
</script>

<script>
    // Command+K focus simulation for search
    window.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) searchInput.focus();
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@stack('scripts')

<!-- PWA Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, err => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>
</body>
</html>
