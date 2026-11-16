@extends('layouts.app')

@section('title', 'Commercial - Monitoring CCTV Toko')

@section('content')
<div class="space-y-6">
    <!-- Header & Sub-Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-secondary text-on-secondary flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">videocam</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Commercial</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Monitoring Fasilitas</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Monitoring CCTV Toko</h1>
                <p class="text-xs text-on-surface-variant">Pantau rekaman live & playback CCTV Bardi Smart Home langsung di sistem Z1.</p>
            </div>
        </div>

        <!-- Mode Toggle & Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- View Selector: Grid vs Bardi Portal Embed -->
            <div class="flex items-center bg-surface-container-high p-1 rounded-xl border border-outline-variant/60">
                <button 
                    type="button" 
                    id="tab-btn-grid" 
                    onclick="switchMainTab('grid')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-secondary text-on-secondary shadow-xs cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">grid_view</span>
                    <span>Grid Kamera</span>
                </button>
                <button 
                    type="button" 
                    id="tab-btn-portal" 
                    onclick="switchMainTab('portal')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-on-surface-variant hover:text-on-surface cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">smart_display</span>
                    <span>Bardi IPC Web (Live & Playback)</span>
                </button>
            </div>

            <a 
                href="https://ipc.bardi.co.id/playback" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-surface-container-high text-on-surface hover:bg-surface-container-highest border border-outline-variant/60 transition-all cursor-pointer shadow-xs active:scale-95 no-underline"
                title="Buka Web Portal Resmi Bardi di Tab Baru"
            >
                <span class="material-symbols-outlined text-[17px] text-[#006c49]">open_in_new</span>
                <span>Tab Baru</span>
            </a>

            <button 
                type="button" 
                onclick="openCameraModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>Tambah Kamera</span>
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">error</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    @endif

    <!-- TAB 1: GRID KAMERA CCTV -->
    <div id="section-grid" class="space-y-6">
        <!-- Store Filter Tabs & Grid View Control -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low p-2.5 rounded-2xl border border-outline-variant/60">
            <!-- Store Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <a 
                    href="{{ route('commercial.monitoring.index', ['store_id' => 'all', 'view_mode' => $viewMode]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline shrink-0 {{ $currentStoreId === null ? 'bg-secondary text-on-secondary shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}"
                >
                    Semua Cabang
                </a>
                @foreach($stores as $st)
                <a 
                    href="{{ route('commercial.monitoring.index', ['store_id' => $st->id, 'view_mode' => $viewMode]) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all no-underline shrink-0 {{ $currentStoreId === $st->id ? 'bg-secondary text-on-secondary shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}"
                >
                    {{ $st->name }}
                </a>
                @endforeach
            </div>

            <!-- View Controls (Grid Mode) -->
            <div class="flex items-center gap-1 shrink-0 self-end sm:self-auto border-t sm:border-t-0 pt-2 sm:pt-0 border-outline-variant/40">
                <span class="text-[11px] font-bold text-on-surface-variant mr-1">Tampilan:</span>
                <button 
                    type="button" 
                    onclick="setGridCols('grid-1')" 
                    id="btn-grid-1"
                    class="p-1.5 rounded-lg border text-on-surface-variant hover:bg-surface-container-high transition-colors {{ $viewMode === 'grid-1' ? 'bg-surface-container-high border-secondary text-secondary font-bold' : 'border-outline-variant/60' }}"
                    title="1 Kamera Penuh"
                >
                    <span class="material-symbols-outlined text-[18px]">rectangle</span>
                </button>
                <button 
                    type="button" 
                    onclick="setGridCols('grid-2')" 
                    id="btn-grid-2"
                    class="p-1.5 rounded-lg border text-on-surface-variant hover:bg-surface-container-high transition-colors {{ $viewMode === 'grid-2' ? 'bg-surface-container-high border-secondary text-secondary font-bold' : 'border-outline-variant/60' }}"
                    title="Grid 2 Kamera"
                >
                    <span class="material-symbols-outlined text-[18px]">view_agenda</span>
                </button>
                <button 
                    type="button" 
                    onclick="setGridCols('grid-4')" 
                    id="btn-grid-4"
                    class="p-1.5 rounded-lg border text-on-surface-variant hover:bg-surface-container-high transition-colors {{ $viewMode === 'grid-4' ? 'bg-surface-container-high border-secondary text-secondary font-bold' : 'border-outline-variant/60' }}"
                    title="Grid 4 Kamera"
                >
                    <span class="material-symbols-outlined text-[18px]">grid_view</span>
                </button>
            </div>
        </div>

        <!-- CCTV Camera Grid -->
        @if($cameras->isNotEmpty())
        <div id="cctv-grid" class="grid gap-5 transition-all duration-300 {{ $viewMode === 'grid-1' ? 'grid-cols-1 max-w-4xl mx-auto' : ($viewMode === 'grid-4' ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4' : 'grid-cols-1 md:grid-cols-2') }}">
            @foreach($cameras as $camera)
            <div class="cctv-card bg-surface-container-lowest rounded-2xl border border-outline-variant/80 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col">
                <!-- Camera Header -->
                <div class="px-4 py-3 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[18px]">videocam</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xs sm:text-sm font-extrabold text-on-surface truncate">{{ $camera->name }}</h2>
                            <div class="flex items-center gap-2 text-[10px] text-on-surface-variant">
                                <span class="font-bold text-secondary">{{ $camera->store?->name ?? 'Semua Toko' }}</span>
                                @if($camera->location_tag)
                                <span>•</span>
                                <span class="px-1.5 py-0.5 rounded bg-surface-container-high font-medium">{{ $camera->location_tag }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status & Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/15 text-emerald-700 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            LIVE
                        </span>

                        <button 
                            type="button" 
                            onclick="editCamera({{ json_encode($camera) }})" 
                            class="p-1 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors"
                            title="Edit Pengaturan Kamera"
                        >
                            <span class="material-symbols-outlined text-[17px]">settings</span>
                        </button>

                        <form action="{{ route('commercial.monitoring.cameras.destroy', $camera->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kamera {{ $camera->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus Kamera">
                                <span class="material-symbols-outlined text-[17px]">delete</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Video Screen Container -->
                <div class="relative bg-slate-950 aspect-video flex items-center justify-center overflow-hidden group">
                    @if(($camera->stream_type === 'webrtc' || $camera->stream_type === 'hls') && !empty($camera->stream_url))
                        @if(str_contains($camera->stream_url, 'stream.html') || str_contains($camera->stream_url, 'http'))
                        <iframe 
                            id="video-{{ $camera->id }}" 
                            class="w-full h-full border-0 bg-black" 
                            src="{{ $camera->stream_url }}" 
                            allow="autoplay; fullscreen"
                        ></iframe>
                        @else
                        <video 
                            id="video-{{ $camera->id }}" 
                            class="w-full h-full object-cover" 
                            src="{{ $camera->stream_url }}" 
                            autoplay 
                            muted 
                            playsinline 
                            controls
                        ></video>
                        @endif
                    @elseif($camera->stream_type === 'rtsp')
                        <!-- Direct RTSP / ONVIF Card with One-Click Realtime WebRTC Play -->
                        <div id="rtsp-screen-{{ $camera->id }}" class="w-full h-full flex flex-col justify-between p-4 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 text-slate-200">
                            <div class="flex items-center justify-between text-xs">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-mono text-[11px] font-bold">BARDI ONVIF (PORT {{ $camera->port }})</span>
                                <span class="font-mono text-[11px] text-slate-400" id="clock-{{ $camera->id }}">{{ now()->format('H:i:s') }}</span>
                            </div>

                            <div class="my-auto text-center py-2">
                                <div class="w-11 h-11 rounded-2xl bg-slate-800/80 border border-slate-700/80 text-emerald-400 flex items-center justify-center mx-auto mb-2 shadow-inner">
                                    <span class="material-symbols-outlined text-2xl">videocam</span>
                                </div>
                                <h3 class="text-xs font-bold text-slate-100">Kamera Bardi Terkonfigurasi (IP {{ $camera->ip_address }})</h3>
                                <p class="text-[10px] text-slate-400 mt-0.5 max-w-xs mx-auto">
                                    Bridge WebRTC lokal sudah aktif di port 1984. Klik tombol di bawah untuk memutar live:
                                </p>

                                <!-- Action Buttons: Realtime WebRTC vs Bardi Portal -->
                                <div class="flex flex-wrap items-center justify-center gap-2 mt-2.5">
                                    <button 
                                        type="button" 
                                        onclick="playWebRTC('{{ $camera->id }}', 'http://localhost:1984/stream.html?src=cctv_vol2')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-all cursor-pointer active:scale-95"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">play_circle</span>
                                        <span>Putar Realtime (WebRTC)</span>
                                    </button>

                                    <button 
                                        type="button" 
                                        onclick="switchMainTab('portal')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-all cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">smart_display</span>
                                        <span>Bardi Web Portal</span>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-2 border-t border-slate-800/80">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span>RTSP Stream URI (VLC Player):</span>
                                    <button 
                                        type="button" 
                                        onclick="copyToClipboard('{{ $camera->rtsp_url }}', 'RTSP URI berhasil disalin! Buka di VLC Player (Media -> Open Network Stream)')" 
                                        class="text-emerald-400 hover:text-emerald-300 font-bold inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[13px]">content_copy</span>
                                        <span>Salin RTSP</span>
                                    </button>
                                </div>
                                <div class="bg-black/60 rounded-lg p-2 font-mono text-[10px] text-slate-300 truncate select-all border border-slate-800">
                                    {{ $camera->rtsp_url ?: 'Lengkapi IP dan Password kamera di pengaturan' }}
                                </div>
                            </div>
                        </div>

                        <!-- Embedded WebRTC Player Frame (Hidden until play is clicked) -->
                        <iframe 
                            id="iframe-player-{{ $camera->id }}" 
                            src="" 
                            class="w-full h-full border-0 bg-black hidden" 
                            allow="autoplay; fullscreen"
                        ></iframe>
                    @else
                        <!-- Direct Portal Mode -->
                        <div class="text-center p-6 text-slate-300">
                            <span class="material-symbols-outlined text-4xl text-secondary mb-2">smart_display</span>
                            <h4 class="text-xs font-bold">Bardi IPC Portal</h4>
                            <p class="text-[11px] text-slate-400 mt-1 mb-3">Tonton via portal resmi Bardi</p>
                            <button 
                                type="button" 
                                onclick="switchMainTab('portal')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-secondary text-white text-xs font-bold hover:bg-[#00553a] transition-all cursor-pointer"
                            >
                                <span>Buka Player di Sistem</span>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Camera Footer Info -->
                <div class="px-4 py-2.5 bg-surface-container-lowest border-t border-outline-variant/60 flex items-center justify-between text-xs text-on-surface-variant">
                    <div class="flex items-center gap-2 truncate">
                        <span class="material-symbols-outlined text-[16px] text-secondary">memory</span>
                        <span class="font-medium truncate font-mono text-[11px]">
                            {{ strtoupper($camera->stream_type) }} 
                            @if($camera->ip_address) • {{ $camera->ip_address }}:{{ $camera->port }} @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <span class="text-[10px] text-slate-500">{{ $camera->notes ?: 'Toko ' . ($camera->store?->name ?? 'Cabang') }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/80 p-8 sm:p-12 text-center max-w-2xl mx-auto shadow-xs">
            <div class="w-16 h-16 rounded-3xl bg-secondary-container/50 text-secondary flex items-center justify-center mx-auto mb-4 border border-secondary/20">
                <span class="material-symbols-outlined text-3xl">videocam</span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-on-surface">Belum Ada Kamera CCTV untuk Cabang Ini</h3>
            <p class="text-xs text-on-surface-variant mt-1.5 max-w-md mx-auto">
                Tambahkan kamera CCTV Bardi Anda atau tonton langsung lewat Bardi IPC Web Portal resmi.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                <button 
                    type="button" 
                    onclick="openCameraModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs cursor-pointer active:scale-95"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Tambah Kamera Bardi</span>
                </button>

                <button 
                    type="button" 
                    onclick="switchMainTab('portal')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-secondary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
                >
                    <span class="material-symbols-outlined text-[18px]">smart_display</span>
                    <span>Buka Bardi IPC Player</span>
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- TAB 2: BARDI OFFICIAL IPC WEB PORTAL (EMBEDDED) -->
    <div id="section-portal" class="hidden space-y-4">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/80 overflow-hidden shadow-lg flex flex-col">
            <!-- Portal Screen Bar -->
            <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-extrabold text-slate-100 font-mono tracking-wide">BARDI OFFICIAL IPC WEB PORTAL</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">LIVE & PLAYBACK</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Scan QR dengan aplikasi Bardi di HP untuk login dan melihat seluruh kamera CCTV.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="reloadBardiIframe()"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                        title="Muat Ulang Halaman Bardi"
                    >
                        <span class="material-symbols-outlined text-[16px]">refresh</span>
                        <span>Reload</span>
                    </button>
                    <button 
                        type="button" 
                        onclick="toggleFullScreen('bardi-ipc-container')"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                        title="Tampilan Layar Penuh"
                    >
                        <span class="material-symbols-outlined text-[16px]">fullscreen</span>
                        <span>Layar Penuh</span>
                    </button>
                    <a 
                        href="https://ipc.bardi.co.id/playback" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="px-3.5 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-bold flex items-center gap-1.5 transition-colors no-underline shadow-xs"
                    >
                        <span>Buka di Tab Baru</span>
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                </div>
            </div>

            <!-- Iframe Container -->
            <div id="bardi-ipc-container" class="relative w-full bg-slate-950 flex flex-col" style="min-height: 720px;">
                <iframe 
                    id="bardi-ipc-iframe"
                    src="https://ipc.bardi.co.id/playback" 
                    class="w-full flex-1 border-0" 
                    style="min-height: 720px; height: 80vh;"
                    allow="fullscreen; camera; microphone; autoplay"
                    sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals"
                ></iframe>
            </div>

            <!-- Fallback Info Footer -->
            <div class="px-4 py-2.5 bg-surface-container-low border-t border-outline-variant/60 flex flex-wrap items-center justify-between text-xs text-on-surface-variant gap-2">
                <span class="text-[11px] font-medium">Jika halaman Bardi di atas tidak muncul karena proteksi keamanan browser, klik tombol <strong>Buka di Tab Baru</strong>.</span>
                <span class="font-mono text-[11px] text-secondary font-bold">https://ipc.bardi.co.id/playback</span>
            </div>
        </div>
    </div>

    <!-- Panduan Integrasi Section -->
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant/60 p-5 sm:p-6">
        <div class="flex items-center gap-2.5 mb-3">
            <span class="material-symbols-outlined text-secondary text-[22px]">help</span>
            <h3 class="text-sm font-extrabold text-on-surface">Panduan Menonton CCTV Bardi di Sistem Z1</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-on-surface-variant">
            <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/60 space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-on-surface">
                    <span class="w-5 h-5 rounded-full bg-secondary-container text-secondary flex items-center justify-center text-[11px]">1</span>
                    <span>Bardi IPC Web Portal (Mudah)</span>
                </div>
                <p class="text-[11px] text-on-surface-variant leading-relaxed">
                    Klik tab <strong>Bardi IPC Web (Live & Playback)</strong> di atas. Buka Bardi App di HP $\rightarrow$ Profil (Saya) $\rightarrow$ Scan QR di layar laptop $\rightarrow$ Konfirmasi login. Kamera langsung aktif!
                </p>
            </div>

            <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/60 space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-on-surface">
                    <span class="w-5 h-5 rounded-full bg-secondary-container text-secondary flex items-center justify-center text-[11px]">2</span>
                    <span>Streaming RTSP (VLC Player)</span>
                </div>
                <p class="text-[11px] text-on-surface-variant leading-relaxed">
                    Salin alamat RTSP dari kartu kamera Anda (contoh `rtsp://admin:admin@192.168.100.12:554/live/ch0`). Buka di VLC Media Player (Media $\rightarrow$ Open Network Stream) untuk streaming lokal tanpa internet.
                </p>
            </div>

            <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/60 space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-on-surface">
                    <span class="w-5 h-5 rounded-full bg-secondary-container text-secondary flex items-center justify-center text-[11px]">3</span>
                    <span>Rekaman & Playback</span>
                </div>
                <p class="text-[11px] text-on-surface-variant leading-relaxed">
                    Lewat portal <code>ipc.bardi.co.id/playback</code>, Anda dapat memutar ulang rekaman kejadian kemarin, memeriksa jam kasir, dan mengunduh video rekaman ke laptop.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Kamera CCTV -->
<div id="cameraModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/80 max-w-lg w-full overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-secondary-container text-secondary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">videocam</span>
                </div>
                <div>
                    <h3 id="modalTitle" class="text-sm font-extrabold text-on-surface">Tambah Kamera CCTV Baru</h3>
                    <p class="text-[11px] text-on-surface-variant">Konfigurasi alamat RTSP / ONVIF Bardi Smart Home</p>
                </div>
            </div>
            <button type="button" onclick="closeCameraModal()" class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[19px]">close</span>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="cameraForm" action="{{ route('commercial.monitoring.cameras.store') }}" method="POST" class="overflow-y-auto p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <!-- Nama Kamera -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Nama Kamera <span class="text-rose-500">*</span></label>
                <input 
                    type="text" 
                    name="name" 
                    id="cameraName" 
                    placeholder="Contoh: CCTV Kasir Vol 2, CCTV Display Depan" 
                    required 
                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                >
            </div>

            <!-- Cabang Toko & Tag Lokasi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Cabang Toko <span class="text-rose-500">*</span></label>
                    <select 
                        name="store_id" 
                        id="cameraStoreId" 
                        required 
                        class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $currentStoreId == $st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tag Posisi</label>
                    <input 
                        type="text" 
                        name="location_tag" 
                        id="cameraLocationTag" 
                        placeholder="Kasir, Etalase, Gudang, Depan" 
                        class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                </div>
            </div>

            <!-- Tipe Stream -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Tipe Integrasi Stream <span class="text-rose-500">*</span></label>
                <select 
                    name="stream_type" 
                    id="cameraStreamType" 
                    onchange="handleStreamTypeChange(this.value)"
                    class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="rtsp">ONVIF / RTSP Bardi (Direct IP & Port 554)</option>
                    <option value="webrtc">WebRTC Stream Player (Video Langsung di Browser)</option>
                    <option value="hls">HLS / HTTP Live Streaming</option>
                    <option value="web_portal">Bardi IPC Portal (ipc.bardi.co.id)</option>
                </select>
            </div>

            <!-- Konfigurasi ONVIF / RTSP -->
            <div id="rtspConfigSection" class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/60 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-secondary">
                    <span class="material-symbols-outlined text-[17px]">settings_ethernet</span>
                    <span>Parameter ONVIF / PC View Bardi</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">IP Address Kamera</label>
                        <input 
                            type="text" 
                            name="ip_address" 
                            id="cameraIp" 
                            placeholder="Contoh: 192.168.100.12" 
                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Port</label>
                        <input 
                            type="number" 
                            name="port" 
                            id="cameraPort" 
                            value="554" 
                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Username ONVIF</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="cameraUsername" 
                            value="admin" 
                            placeholder="admin" 
                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Password ONVIF</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="cameraPassword" 
                            placeholder="Password di Bardi App" 
                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Channel Kualitas</label>
                    <select 
                        name="channel" 
                        id="cameraChannel" 
                        class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="ch0">ch0 - Kualitas HD (High Definition)</option>
                        <option value="ch1">ch1 - Kualitas SD (Standard / Lebih Ringan)</option>
                    </select>
                </div>
            </div>

            <!-- Direct Stream URL (Opsional) -->
            <div id="directStreamSection">
                <label class="block text-xs font-bold text-on-surface mb-1">Direct Stream / WebRTC URL (Opsional)</label>
                <input 
                    type="text" 
                    name="stream_url" 
                    id="cameraStreamUrl" 
                    placeholder="https://... atau http://localhost:1984/stream.html?src=cctv1" 
                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                >
                <p class="text-[10px] text-on-surface-variant mt-1">Kosongkan jika menggunakan ONVIF RTSP bawaan Bardi.</p>
            </div>

            <!-- Catatan -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan Tambahan</label>
                <textarea 
                    name="notes" 
                    id="cameraNotes" 
                    rows="2" 
                    placeholder="Contoh: Mengarah ke laci kasir dan meja etalase Vol 2." 
                    class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                ></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                <button 
                    type="button" 
                    onclick="closeCameraModal()" 
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-high text-on-surface hover:bg-surface-container-highest transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs cursor-pointer active:scale-95"
                >
                    Simpan Kamera
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchMainTab(tab) {
        const secGrid = document.getElementById('section-grid');
        const secPortal = document.getElementById('section-portal');
        const btnGrid = document.getElementById('tab-btn-grid');
        const btnPortal = document.getElementById('tab-btn-portal');

        if (tab === 'portal') {
            secGrid.classList.add('hidden');
            secPortal.classList.remove('hidden');

            btnPortal.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-secondary text-on-secondary shadow-xs cursor-pointer';
            btnGrid.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-on-surface-variant hover:text-on-surface cursor-pointer';
        } else {
            secPortal.classList.add('hidden');
            secGrid.classList.remove('hidden');

            btnGrid.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-secondary text-on-secondary shadow-xs cursor-pointer';
            btnPortal.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-on-surface-variant hover:text-on-surface cursor-pointer';
        }
    }

    function reloadBardiIframe() {
        const frame = document.getElementById('bardi-ipc-iframe');
        if (frame) {
            frame.src = 'https://ipc.bardi.co.id/playback?t=' + new Date().getTime();
        }
    }

    function setGridCols(mode) {
        const grid = document.getElementById('cctv-grid');
        if (!grid) return;

        ['btn-grid-1', 'btn-grid-2', 'btn-grid-4'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.classList.remove('bg-surface-container-high', 'border-secondary', 'text-secondary', 'font-bold');
                btn.classList.add('border-outline-variant/60');
            }
        });

        const activeBtn = document.getElementById(`btn-${mode}`);
        if (activeBtn) {
            activeBtn.classList.add('bg-surface-container-high', 'border-secondary', 'text-secondary', 'font-bold');
            activeBtn.classList.remove('border-outline-variant/60');
        }

        grid.className = 'grid gap-5 transition-all duration-300 ';
        if (mode === 'grid-1') {
            grid.className += 'grid-cols-1 max-w-4xl mx-auto';
        } else if (mode === 'grid-4') {
            grid.className += 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4';
        } else {
            grid.className += 'grid-cols-1 md:grid-cols-2';
        }
    }

    function openCameraModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Kamera CCTV Baru';
        document.getElementById('cameraForm').action = "{{ route('commercial.monitoring.cameras.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('cameraName').value = '';
        document.getElementById('cameraLocationTag').value = '';
        document.getElementById('cameraStreamType').value = 'rtsp';
        document.getElementById('cameraIp').value = '';
        document.getElementById('cameraPort').value = '554';
        document.getElementById('cameraChannel').value = 'ch0';
        document.getElementById('cameraUsername').value = 'admin';
        document.getElementById('cameraPassword').value = '';
        document.getElementById('cameraStreamUrl').value = '';
        document.getElementById('cameraNotes').value = '';

        document.getElementById('cameraModal').classList.remove('hidden');
    }

    function editCamera(camera) {
        document.getElementById('modalTitle').textContent = 'Edit Pengaturan Kamera: ' + camera.name;
        document.getElementById('cameraForm').action = `/commercial/monitoring/cameras/${camera.id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('cameraName').value = camera.name || '';
        document.getElementById('cameraStoreId').value = camera.store_id || '';
        document.getElementById('cameraLocationTag').value = camera.location_tag || '';
        document.getElementById('cameraStreamType').value = camera.stream_type || 'rtsp';
        document.getElementById('cameraIp').value = camera.ip_address || '';
        document.getElementById('cameraPort').value = camera.port || 554;
        document.getElementById('cameraChannel').value = camera.channel || 'ch0';
        document.getElementById('cameraUsername').value = camera.username || 'admin';
        document.getElementById('cameraPassword').value = camera.password || '';
        document.getElementById('cameraStreamUrl').value = camera.stream_url || '';
        document.getElementById('cameraNotes').value = camera.notes || '';

        document.getElementById('cameraModal').classList.remove('hidden');
    }

    function closeCameraModal() {
        document.getElementById('cameraModal').classList.add('hidden');
    }

    function copyToClipboard(text, message) {
        if (!text) {
            alert('Alamat RTSP belum lengkap. Silakan lengkapi IP dan password kamera terlebih dahulu.');
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            alert(message || 'Berhasil disalin ke clipboard!');
        }).catch(err => {
            prompt('Salin URL ini secara manual:', text);
        });
    }

    function toggleFullScreen(elementId) {
        const elem = document.getElementById(elementId);
        if (!elem) return;
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                alert(`Error: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    function playWebRTC(cameraId, streamUrl) {
        const screen = document.getElementById('rtsp-screen-' + cameraId);
        const iframe = document.getElementById('iframe-player-' + cameraId);
        if (screen && iframe) {
            screen.classList.add('hidden');
            iframe.src = streamUrl;
            iframe.classList.remove('hidden');
        }
    }

    function handleStreamTypeChange(type) {
        const rtspSec = document.getElementById('rtspConfigSection');
        if (type === 'rtsp') {
            rtspSec.classList.remove('hidden');
        } else {
            rtspSec.classList.add('hidden');
        }
    }
</script>
@endsection
