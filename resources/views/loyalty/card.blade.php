<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Z1 Loyalty Card - {{ $customer->name }}</title>
    @php
        $cardBgPath = public_path('images/cropped-card.png');
        if (!file_exists($cardBgPath)) {
            $fallbackPath = base_path('public/images/cropped-card.png');
            if (file_exists($fallbackPath)) {
                $cardBgPath = $fallbackPath;
            } elseif (file_exists(base_path('images/cropped-card.png'))) {
                $cardBgPath = base_path('images/cropped-card.png');
            }
        }
        
        $cardBgDataUri = file_exists($cardBgPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($cardBgPath))
            : asset('public/images/cropped-card.png');

        $tierColor = $currentTierModel?->badge_color ?? 'slate';
        $tierBadgeColors = [
            'slate' => 'bg-slate-800 text-slate-300 border-slate-700',
            'emerald' => 'bg-emerald-950/80 text-emerald-400 border-emerald-700/60',
            'cyan' => 'bg-sky-950/80 text-sky-400 border-sky-700/60',
            'amber' => 'bg-amber-950/80 text-amber-400 border-amber-700/60',
            'purple' => 'bg-purple-950/80 text-purple-400 border-purple-700/60',
            'rose' => 'bg-rose-950/80 text-rose-400 border-rose-700/60',
        ];
        $tierBadgeClass = $tierBadgeColors[$tierColor] ?? $tierBadgeColors['slate'];
    @endphp
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- QR Code JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
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
        .font-code {
            font-family: 'Space Grotesk', monospace;
        }

        /* 3D PERSPECTIVE CONTAINER */
        .card-perspective {
            perspective: 1400px;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
        }

        /* 3D FLIPPER CONTAINER */
        .card-flipper {
            position: relative;
            width: 100%;
            aspect-ratio: 652 / 427;
            transform-style: preserve-3d;
            transition: transform 0.7s cubic-bezier(0.2, 0.85, 0.32, 1.2), box-shadow 0.3s ease;
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.95), 0 0 35px rgba(255, 255, 255, 0.08);
        }

        .card-flipper.is-flipped {
            transform: rotateY(180deg);
        }

        /* CARD FACES */
        .card-face {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border-radius: 18px;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            overflow: hidden;
        }

        /* SISI DEPAN */
        .card-front {
            background-image: url('{{ $cardBgDataUri }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 2;
            transform: rotateY(0deg);
        }

        /* SISI BELAKANG (MATCH MECHA CYBERPUNK DESIGN SYSTEM) */
        .card-back {
            background: radial-gradient(circle at 50% 15%, #181d28 0%, #0d1017 50%, #05070a 100%);
            border: 1.5px solid rgba(255, 255, 255, 0.22);
            transform: rotateY(180deg);
            z-index: 1;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35), inset 0 0 30px rgba(0, 0, 0, 0.95);
        }

        .card-back-grid {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px),
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 16px 16px, 32px 32px, 32px 32px;
            pointer-events: none;
            opacity: 0.85;
        }

        /* Tech Corner Brackets */
        .mecha-bracket-tl {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 12px;
            height: 12px;
            border-top: 1.5px solid rgba(56, 189, 248, 0.7);
            border-left: 1.5px solid rgba(56, 189, 248, 0.7);
            pointer-events: none;
        }
        .mecha-bracket-tr {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            border-top: 1.5px solid rgba(56, 189, 248, 0.7);
            border-right: 1.5px solid rgba(56, 189, 248, 0.7);
            pointer-events: none;
        }
        .mecha-bracket-bl {
            position: absolute;
            bottom: 5px;
            left: 5px;
            width: 12px;
            height: 12px;
            border-bottom: 1.5px solid rgba(56, 189, 248, 0.7);
            border-left: 1.5px solid rgba(56, 189, 248, 0.7);
            pointer-events: none;
        }
        .mecha-bracket-br {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            border-bottom: 1.5px solid rgba(56, 189, 248, 0.7);
            border-right: 1.5px solid rgba(56, 189, 248, 0.7);
            pointer-events: none;
        }

        /* KILAU PERAK / PURE SILVER-WHITE METALLIC SHINE (Hanya di Depan) */
        .card-shine-beam {
            position: absolute;
            top: -60%;
            left: -80%;
            width: 55%;
            height: 220%;
            background: linear-gradient(
                105deg,
                transparent 20%,
                rgba(255, 255, 255, 0.02) 35%,
                rgba(255, 255, 255, 0.25) 45%,
                rgba(255, 255, 255, 0.65) 50%,
                rgba(255, 255, 255, 0.25) 55%,
                rgba(255, 255, 255, 0.02) 65%,
                transparent 80%
            );
            transform: rotate(25deg);
            pointer-events: none;
            animation: sweepKilau 4.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            mix-blend-mode: screen;
            z-index: 20;
        }

        @keyframes sweepKilau {
            0% {
                left: -120%;
                opacity: 0;
            }
            15% {
                opacity: 1;
            }
            45% {
                left: 140%;
                opacity: 1;
            }
            50%, 100% {
                left: 140%;
                opacity: 0;
            }
        }

        /* SPECULAR INTERACTIVE GLARE */
        .card-specular-glare {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            mix-blend-mode: overlay;
            z-index: 25;
        }

        /* SPARKLE STARS */
        .sparkle {
            position: absolute;
            width: 7px;
            height: 7px;
            background: #ffffff;
            clip-path: polygon(50% 0%, 65% 35%, 100% 50%, 65% 65%, 50% 100%, 35% 65%, 0% 50%, 35% 35%);
            animation: sparkleAnim 3.5s ease-in-out infinite;
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.9));
            z-index: 22;
            pointer-events: none;
        }
        @keyframes sparkleAnim {
            0%, 100% { transform: scale(0) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.3) rotate(90deg); opacity: 1; }
        }

        #qrcode img, #qrcode canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain;
            display: block;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-start p-3 sm:p-5 pb-16 antialiased">

    <div class="w-full max-w-lg mx-auto space-y-4">

        {{-- Top App Bar --}}
        <div class="flex items-center justify-between px-2 pt-1">
            <div class="flex items-center space-x-2">
                <span class="font-orbitron font-black text-sm text-white tracking-widest">Z1 VAPE STORE</span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-orbitron border {{ $tierBadgeClass }}">
                ★ {{ $customer->tier }}
            </span>
        </div>

        {{-- THE OFFICIAL 3D FLIPPABLE LOYALTY CARD --}}
        <div class="card-perspective w-full" id="cardWrapper" onclick="toggleCardFlip(event)" title="Klik untuk membalik kartu">
            <div id="cardFlipper" class="card-flipper w-full">

                {{-- ==================== SISI DEPAN (FRONT: QR CODE & IDENTITY) ==================== --}}
                <div class="card-face card-front">
                    {{-- Kilau / Shimmer Animated Beam (Pure White Metallic) --}}
                    <div class="card-shine-beam"></div>

                    {{-- Interactive Specular Light Highlight --}}
                    <div id="specularGleam" class="card-specular-glare"></div>

                    {{-- Sparkle Stars at Silver Letter Corners --}}
                    <div class="sparkle top-[28%] left-[24%]" style="animation-delay: 0.2s;"></div>
                    <div class="sparkle top-[30%] right-[22%]" style="animation-delay: 1.8s;"></div>
                    <div class="sparkle top-[10%] left-[58%]" style="animation-delay: 2.8s;"></div>

                    {{-- Flip Indicator Button (Pill di pojok kanan atas) --}}
                    <div class="absolute z-20 top-3 right-3.5 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-950/75 border border-white/20 text-[10px] font-bold text-slate-200 backdrop-blur-md shadow-lg pointer-events-none transition">
                        <span class="material-symbols-outlined text-[13px] text-amber-400">flip</span>
                        <span>Cek Poin ↻</span>
                    </div>

                    {{-- 1. DYNAMIC CUSTOMER NAME (Tepat di atas garis setelah NAME:) --}}
                    <div class="absolute z-10 flex items-center overflow-hidden pointer-events-none" 
                         style="left: 20%; top: 73.5%; height: 6%; max-width: 52%;">
                        <span class="font-bold text-xs sm:text-sm text-white tracking-wider uppercase font-orbitron truncate"
                              style="text-shadow: 0 1px 3px rgba(0,0,0,0.95), 0 0 8px rgba(0,0,0,0.85);">
                            {{ $customer->name }}
                        </span>
                    </div>

                    {{-- 2. DYNAMIC MEMBER NUMBER (Tepat di atas garis setelah MEMBER NO:) --}}
                    <div class="absolute z-10 flex items-center overflow-hidden pointer-events-none" 
                         style="left: 30%; top: 82.2%; height: 6%; max-width: 42%;">
                        <span class="font-code font-bold text-xs sm:text-sm text-white tracking-wider truncate"
                              style="text-shadow: 0 1px 3px rgba(0,0,0,0.95), 0 0 8px rgba(0,0,0,0.85);">
                            {{ $customer->member_code }}
                        </span>
                    </div>

                    {{-- 3. TERMS & CONDITIONS APPLY --}}
                    <div class="absolute z-10 flex items-center overflow-hidden pointer-events-none" 
                         style="left: 6.4%; top: 90.2%; height: 4.8%; max-width: 60%;">
                        <span class="text-[8.5px] sm:text-[10px] text-slate-400 font-medium italic tracking-wider leading-none"
                              style="text-shadow: 0 1px 3px rgba(0,0,0,0.95);">
                            Terms &amp; Conditions Apply
                        </span>
                    </div>

                    {{-- 4. KOTAK QR CODE RAPI --}}
                    <div class="absolute z-10 bg-white rounded-xl p-1.5 shadow-2xl flex items-center justify-center overflow-hidden"
                         style="left: 77%; top: 66%; width: 17.5%; aspect-ratio: 1/1;">
                        <div id="qrcode" class="w-full h-full flex items-center justify-center"></div>
                    </div>
                </div>

                {{-- ==================== SISI BELAKANG (BACK: SALDO POIN MEMBER & BENEFIT) ==================== --}}
                <div class="card-face card-back flex flex-col justify-between p-3.5 sm:p-5 relative select-none">
                    {{-- Cyber mecha background texture & grid --}}
                    <div class="card-back-grid"></div>

                    {{-- Chamfered Mecha Outer Corner Brackets --}}
                    <div class="mecha-bracket-tl"></div>
                    <div class="mecha-bracket-tr"></div>
                    <div class="mecha-bracket-bl"></div>
                    <div class="mecha-bracket-br"></div>

                    {{-- Metallic Shine Beam (Pure Silver-White Mecha Sweep) --}}
                    <div class="card-shine-beam"></div>

                    {{-- Interactive Specular Light Highlight on Back --}}
                    <div id="specularGleamBack" class="card-specular-glare"></div>

                    {{-- Sparkle Stars at Silver Highlights --}}
                    <div class="sparkle top-[24%] left-[16%]" style="animation-delay: 0.6s;"></div>
                    <div class="sparkle top-[26%] right-[18%]" style="animation-delay: 2.2s;"></div>
                    <div class="sparkle bottom-[18%] left-[28%]" style="animation-delay: 3.4s;"></div>

                    {{-- Top Obsidian Holographic Magnetic Stripe --}}
                    <div class="absolute top-0 left-0 right-0 h-8 sm:h-9 bg-gradient-to-r from-neutral-950 via-slate-900 to-neutral-950 border-b border-white/10 flex items-center justify-between px-4 sm:px-5 overflow-hidden shadow-inner">
                        <div class="absolute inset-0 opacity-20 pointer-events-none" 
                             style="background: linear-gradient(90deg, transparent 0%, rgba(236,72,153,0.3) 20%, rgba(56,189,248,0.3) 40%, rgba(52,211,153,0.3) 60%, rgba(251,191,36,0.3) 80%, transparent 100%);"></div>
                        <div class="relative z-10 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-code text-[8px] sm:text-[9px] text-slate-400 tracking-widest uppercase font-semibold">
                                Z1 DIGITAL MEMBERSHIP PROTOCOL • VOL.2
                            </span>
                        </div>
                        <span class="relative z-10 font-mono text-[9px] sm:text-[10px] text-emerald-400 font-bold tracking-wider">
                            🔒 {{ $customer->member_code }}
                        </span>
                    </div>

                    {{-- Header Box di Bawah Magnetic Strip: EMV Chip + Member Identity + Tier Badge --}}
                    <div class="relative z-10 mt-6 sm:mt-7 flex items-center justify-between px-0.5">
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            {{-- Realistic 3D EMV Gold Smart Chip --}}
                            <div class="relative w-10 h-6.5 sm:w-11 sm:h-7.5 rounded bg-gradient-to-br from-amber-200 via-amber-400 to-amber-600 p-[1px] shadow-[0_0_12px_rgba(245,158,11,0.3)] flex items-center justify-center shrink-0">
                                <div class="w-full h-full rounded-[3px] bg-gradient-to-br from-[#dfa837] via-[#ca8a04] to-[#854d0e] relative overflow-hidden flex items-center justify-center">
                                    <div class="absolute inset-x-0 top-1/2 h-[1px] bg-amber-950/50"></div>
                                    <div class="absolute inset-y-0 left-1/3 w-[1px] bg-amber-950/50"></div>
                                    <div class="absolute inset-y-0 right-1/3 w-[1px] bg-amber-950/50"></div>
                                    <div class="w-3.5 h-3 rounded-xs border border-amber-950/50 bg-amber-300/35"></div>
                                </div>
                            </div>

                            {{-- Member Name & Code --}}
                            <div class="min-w-0">
                                <span class="text-[8px] sm:text-[9px] font-orbitron font-bold text-slate-400 uppercase tracking-widest block leading-tight">
                                    MEMBER EXCLUSIVE
                                </span>
                                <span class="text-xs sm:text-sm font-extrabold text-white font-orbitron uppercase tracking-wider leading-tight truncate block max-w-[160px] sm:max-w-[210px]"
                                      style="text-shadow: 0 1px 4px rgba(0,0,0,0.95), 0 0 10px rgba(255,255,255,0.15);">
                                    {{ $customer->name }}
                                </span>
                            </div>
                        </div>

                        {{-- Tier Badge Matching Front --}}
                        <span class="px-2.5 sm:px-3 py-0.5 rounded-full text-[9px] sm:text-[10px] font-extrabold uppercase font-orbitron border {{ $tierBadgeClass }} shadow-[0_0_12px_rgba(0,0,0,0.5)] shrink-0">
                            ★ {{ $customer->tier }}
                        </span>
                    </div>

                    {{-- HERO CYBER HUD: SALDO POIN & BATAS TUKAR POIN --}}
                    <div class="relative z-10 my-auto p-3 sm:p-3.5 rounded-xl bg-gradient-to-b from-slate-900/85 via-slate-950/90 to-black/95 border border-white/15 backdrop-blur-md shadow-[inset_0_1px_1px_rgba(255,255,255,0.15),0_12px_28px_rgba(0,0,0,0.7)]">
                        {{-- Futuristic Corner Reticles --}}
                        <div class="absolute top-1.5 left-1.5 w-2 h-2 border-t-2 border-l-2 border-cyan-400/80 pointer-events-none"></div>
                        <div class="absolute top-1.5 right-1.5 w-2 h-2 border-t-2 border-r-2 border-cyan-400/80 pointer-events-none"></div>
                        <div class="absolute bottom-1.5 left-1.5 w-2 h-2 border-b-2 border-l-2 border-cyan-400/80 pointer-events-none"></div>
                        <div class="absolute bottom-1.5 right-1.5 w-2 h-2 border-b-2 border-r-2 border-cyan-400/80 pointer-events-none"></div>

                        <div class="grid grid-cols-2 gap-2 sm:gap-4 items-center">
                            {{-- Saldo Poin Member --}}
                            <div>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="text-[8px] sm:text-[9px] font-orbitron font-bold text-slate-300 uppercase tracking-widest block leading-none">
                                        SALDO POIN MEMBER
                                    </span>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span class="font-orbitron font-black text-xl sm:text-2xl text-white tracking-tight leading-none"
                                          style="text-shadow: 0 0 16px rgba(255,255,255,0.3), 0 2px 4px rgba(0,0,0,0.9);">
                                        Rp {{ number_format($customer->points, 0, ',', '.') }}
                                    </span>
                                </div>
                                <span class="text-[7.5px] sm:text-[8.5px] text-emerald-400 font-medium block mt-1 tracking-wide">
                                    {{ number_format($customer->points, 0, ',', '.') }} Poin • Siap Pakai di Kasir
                                </span>
                            </div>

                            {{-- Batas Tukar Poin Kasir --}}
                            <div class="text-right border-l border-white/10 pl-2 sm:pl-3">
                                <span class="text-[8px] sm:text-[9px] font-orbitron font-semibold text-slate-400 uppercase tracking-wider block mb-1 leading-none">
                                    BATAS TUKAR POIN
                                </span>
                                <div class="font-orbitron font-bold text-base sm:text-lg text-emerald-400 tracking-tight leading-none"
                                     style="text-shadow: 0 0 12px rgba(52,211,153,0.35);">
                                    Rp {{ number_format($currentTierModel->max_redeem_rp ?? 30000, 0, ',', '.') }}
                                </div>
                                <span class="text-[7.5px] sm:text-[8.5px] text-slate-400 block mt-1">
                                    Maks. Potongan / Transaksi
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Info: Tier Benefit & Progress --}}
                    <div class="relative z-10 space-y-1.5 text-slate-400 text-[10px]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                @if(($currentTierModel->discount_nominal ?? 0) > 0)
                                    <span class="px-2 py-0.5 rounded bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 font-mono font-bold text-[8.5px] sm:text-[9px] flex items-center gap-1 shadow-xs">
                                        <span class="material-symbols-outlined text-[10px] sm:text-[11px] text-emerald-400">sell</span>
                                        <span>Diskon Rp {{ number_format($currentTierModel->discount_nominal, 0, ',', '.') }}</span>
                                    </span>
                                @else
                                    <span class="text-[8.5px] sm:text-[9px] font-mono text-slate-400 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400/80 animate-pulse"></span>
                                        <span>Benefit Aktif Tier {{ $customer->tier }}</span>
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Flip Back Pill Button --}}
                            <div class="flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-900/90 border border-white/20 text-[8.5px] sm:text-[9px] font-bold text-slate-200 shadow-sm transition hover:bg-slate-800">
                                <span class="material-symbols-outlined text-[11px] text-amber-400">qr_code_2</span>
                                <span>Lihat QR Code ↺</span>
                            </div>
                        </div>

                        @if(!empty($nextTierModel) && ($amountToNextTier ?? 0) > 0)
                            <div class="pt-1.5 border-t border-white/10">
                                <div class="flex items-center justify-between text-[8.5px] sm:text-[9px] mb-1 font-mono">
                                    <span class="text-slate-300">Menuju Tier <strong class="text-white font-orbitron">{{ $nextTierModel->name }}</strong>:</span>
                                    <span class="text-amber-400 font-bold">Rp {{ number_format($amountToNextTier, 0, ',', '.') }} lagi</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden p-[1px] border border-white/10">
                                    <div class="bg-gradient-to-r from-emerald-500 via-cyan-400 to-amber-400 h-full rounded-full shadow-[0_0_8px_rgba(52,211,153,0.5)]" style="width: {{ $tierProgressPercent }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>

            </div>
        </div>

        {{-- Hint & Flip Button Bar di Bawah Kartu --}}
        <div class="text-center space-y-2 pt-1">
            <button type="button" onclick="toggleCardFlip(event)" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 text-xs font-bold text-slate-200 transition active:scale-95 shadow-md cursor-pointer">
                <span class="material-symbols-outlined text-sm text-amber-400 animate-pulse">flip</span>
                <span id="flip-hint-btn">Klik untuk Balikkan Kartu (Lihat Saldo)</span>
            </button>
            <p class="text-[11px] text-slate-400 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm text-slate-500">touch_app</span>
                <span id="flip-hint-text">Ketuk kartu untuk melihat Saldo Poin &amp; Benefit Member</span>
            </p>
        </div>

    </div>

    {{-- SCRIPTS: GENERATE QR CODE, 3D INTERACTIVE TILT & FLIP ANIMATION --}}
    <script>
        let isFlipped = false;
        let tiltX = 0;
        let tiltY = 0;

        const flipper = document.getElementById('cardFlipper');
        const glare = document.getElementById('specularGleam');
        const glareBack = document.getElementById('specularGleamBack');
        const hintBtn = document.getElementById('flip-hint-btn');
        const hintText = document.getElementById('flip-hint-text');

        function updateCardTransform() {
            const baseRotationY = isFlipped ? 180 : 0;
            const currentTiltY = isFlipped ? -tiltY : tiltY;
            flipper.style.transform = `rotateX(${tiltX}deg) rotateY(${baseRotationY + currentTiltY}deg) scale(${isFlipped ? 1.02 : 1})`;
        }

        function toggleCardFlip(event) {
            if (event && event.target && (event.target.closest('a') || event.target.closest('button#whatsapp-btn'))) {
                return;
            }
            isFlipped = !isFlipped;
            flipper.classList.toggle('is-flipped', isFlipped);
            
            tiltX = 0;
            tiltY = 0;
            updateCardTransform();

            if (hintBtn) {
                hintBtn.innerText = isFlipped ? 'Klik untuk Balikkan Kartu (Lihat QR)' : 'Klik untuk Balikkan Kartu (Lihat Saldo)';
            }
            if (hintText) {
                hintText.innerText = isFlipped ? 'Ketuk kartu untuk kembali ke QR Code Kasir' : 'Ketuk kartu untuk melihat Saldo Poin & Benefit Member';
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // 1. Generate Dynamic QR Code inside the clean white box
            const qrContainer = document.getElementById("qrcode");
            const qrSize = Math.max(50, Math.min(80, qrContainer.clientWidth || 65));
            new QRCode(qrContainer, {
                text: "{{ $customer->member_code }}",
                width: qrSize,
                height: qrSize,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });

            // 2. Interactive 3D Card Tilt & Specular Glare
            function onMove(clientX, clientY) {
                const rect = flipper.getBoundingClientRect();
                const x = clientX - rect.left;
                const y = clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                tiltX = ((y - centerY) / centerY) * -12;
                tiltY = ((x - centerX) / centerX) * 12;

                updateCardTransform();

                // Specular light position for both front and back
                if (!isFlipped && glare) {
                    glare.style.opacity = '1';
                    glare.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.4) 0%, transparent 60%)`;
                    if (glareBack) glareBack.style.opacity = '0';
                } else if (isFlipped && glareBack) {
                    glareBack.style.opacity = '1';
                    glareBack.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.3) 0%, transparent 60%)`;
                    if (glare) glare.style.opacity = '0';
                }
            }

            function onReset() {
                tiltX = 0;
                tiltY = 0;
                updateCardTransform();
                if (glare) glare.style.opacity = '0';
                if (glareBack) glareBack.style.opacity = '0';
            }

            const wrapper = document.getElementById('cardWrapper');

            // Desktop Mouse
            wrapper.addEventListener('mousemove', (e) => onMove(e.clientX, e.clientY));
            wrapper.addEventListener('mouseleave', onReset);

            // Mobile Touch Tilt
            wrapper.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    onMove(e.touches[0].clientX, e.touches[0].clientY);
                }
            }, { passive: true });
            wrapper.addEventListener('touchend', onReset);

            // Smartphone Gyroscope
            if (window.DeviceOrientationEvent) {
                window.addEventListener('deviceorientation', (e) => {
                    if (e.gamma !== null && e.beta !== null) {
                        const gamma = Math.max(-20, Math.min(20, e.gamma));
                        const beta = Math.max(-20, Math.min(20, e.beta - 45));
                        tiltY = gamma * 0.5;
                        tiltX = -beta * 0.5;
                        updateCardTransform();
                    }
                });
            }
        });
    </script>
</body>
</html>
