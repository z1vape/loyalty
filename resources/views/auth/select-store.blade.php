<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pilih Toko | Z1 VapeOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>
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
        }
    </style>
</head>
<body class="bg-[#f8f9ff] text-[#0b1c30] font-sans min-h-screen flex items-center justify-center p-gutter">
    <div class="w-full max-w-md">
        <!-- Logo / Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-primary">Z1 VapeOS</h1>
            <p class="text-on-surface-variant text-sm mt-2">Retail & Inventory Management System</p>
        </div>

        <!-- Selection Card -->
        <div class="bg-white rounded-2xl border border-outline-variant p-8 shadow-md">
            <h2 class="text-xl font-bold mb-2 text-center text-slate-800">Pilih Toko</h2>
            <p class="text-slate-500 text-sm text-center mb-6">Silakan pilih toko yang ingin Anda buka saat ini.</p>

            <!-- Error Messages -->
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl flex items-center gap-2 border border-red-200">
                    <span class="material-symbols-outlined text-base">error</span>
                    <div class="font-bold flex-1">{{ session('error') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl flex flex-col gap-1 border border-red-200">
                    <ul class="list-disc pl-6 text-xs font-semibold">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($stores->isEmpty())
                <div class="text-center py-6">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">storefront</span>
                    <div class="text-slate-500 font-semibold">Tidak ada toko yang tersedia</div>
                    <p class="text-xs text-slate-400 mt-1">Anda tidak terhubung ke toko mana pun. Silakan hubungi Administrator.</p>
                </div>
            @else
                <form action="{{ url('/select-store') }}" method="POST" class="space-y-3">
                    @csrf
                    @foreach($stores as $store)
                        <button type="submit" name="store_id" value="{{ $store->id }}" class="w-full text-left p-4 rounded-xl border border-slate-200 hover:border-slate-400 hover:bg-slate-50 transition duration-200 flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-slate-800 group-hover:text-blue-600 transition">{{ $store->name }}</div>
                                @if($store->address)
                                    <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs text-slate-400">location_on</span>
                                        {{ $store->address }}
                                    </div>
                                @endif
                            </div>
                            <span class="material-symbols-outlined text-slate-300 group-hover:text-blue-600 group-hover:translate-x-1 transition duration-200 text-lg">arrow_forward_ios</span>
                        </button>
                    @endforeach
                </form>
            @endif

            <!-- Logout Link -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex justify-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-red-500 hover:text-red-700 transition flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">logout</span> Keluar dari akun
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
