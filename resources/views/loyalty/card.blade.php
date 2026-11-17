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

        /* SISI BELAKANG */
        .card-back {
            background: linear-gradient(145deg, #10141f 0%, #080a10 50%, #131a27 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            transform: rotateY(180deg);
            z-index: 1;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), inset 0 -1px 0 rgba(0, 0, 0, 0.6);
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
                <div class="card-face card-back flex flex-col justify-between p-4 sm:p-5 relative select-none">
                    {{-- Cyber mecha background texture --}}
                    <div class="absolute inset-0 opacity-10 pointer-events-none" 
                         style="background-image: radial-gradient(rgba(255,255,255,0.25) 1px, transparent 1px); background-size: 14px 14px;"></div>
                    
                    {{-- Laser Magnetic Strip Mecha --}}
                    <div class="absolute top-0 left-0 right-0 h-8 sm:h-9 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border-b border-slate-700/70 flex items-center justify-between px-4 overflow-hidden">
                        <span class="font-code text-[8px] sm:text-[9px] text-slate-500 tracking-widest uppercase truncate">
                            Z1 VAPE OFFICIAL DIGITAL MEMBERSHIP PROTOCOL
                        </span>
                        <span class="font-mono text-[9px] text-emerald-400 font-bold">
                            {{ $customer->member_code }}
                        </span>
                    </div>

                    {{-- Header Box di Bawah Magnetic Strip --}}
                    <div class="relative z-10 mt-6 sm:mt-7 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            {{-- Mecha Smart Chip Graphic --}}
                            <div class="w-9 h-6 sm:w-10 sm:h-7 rounded bg-gradient-to-br from-amber-200/30 via-amber-400/20 to-amber-600/30 border border-amber-300/40 relative flex items-center justify-center shadow-inner">
                                <div class="w-6 h-3 border border-amber-400/40 rounded-xs"></div>
                            </div>
                            <div>
                                <span class="text-[9px] sm:text-[10px] font-orbitron font-bold text-slate-400 uppercase tracking-wider block leading-tight">
                                    MEMBER EXCLUSIVE
                                </span>
                                <span class="text-[10px] sm:text-xs font-bold text-white font-code uppercase leading-tight truncate block max-w-[150px] sm:max-w-[200px]">
                                    {{ $customer->name }}
                                </span>
                            </div>
                        </div>

                        <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold uppercase font-orbitron border {{ $tierBadgeClass }}">
                            ★ {{ $customer->tier }}
                        </span>
                    </div>

                    {{-- HERO SALDO POIN & POTONGAN BELANJA --}}
                    <div class="relative z-10 grid grid-cols-2 gap-2 sm:gap-3 p-3 sm:p-3.5 rounded-xl bg-slate-950/70 border border-slate-700/70 backdrop-blur-md shadow-inner my-auto">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-orbitron font-bold text-slate-400 uppercase tracking-widest block mb-0.5 leading-none">
                                SALDO POIN MEMBER
                            </span>
                            <div class="flex items-baseline gap-1">
                                <span class="font-orbitron font-black text-xl sm:text-2xl text-white tracking-tight leading-none drop-shadow">
                                    {{ number_format($customer->points) }}
                                </span>
                                <span class="font-orbitron font-bold text-[10px] text-amber-400 tracking-wider">
                                    POIN
                                </span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-[9px] sm:text-[10px] font-medium text-slate-400 uppercase tracking-wider block mb-0.5 leading-none">
                                Potongan Belanja
                            </span>
                            <div class="font-code font-bold text-sm sm:text-base text-emerald-400 tracking-wide leading-none drop-shadow">
                                Rp {{ number_format($customer->points * ($currentTierModel->point_value_rp ?? 100), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Footer Info: Tier Perks & Next Tier Progress --}}
                    <div class="relative z-10 space-y-1.5 text-slate-400 text-[10px]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 rounded bg-emerald-950/70 border border-emerald-800/60 text-emerald-400 font-mono font-bold text-[9px]">
                                    {{ $currentTierModel->point_multiplier ?? 1 }}x Poin
                                </span>
                                @if(($currentTierModel->discount_nominal ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-950/70 border border-indigo-800/60 text-indigo-300 font-mono font-bold text-[9px]">
                                        Diskon Rp {{ number_format($currentTierModel->discount_nominal, 0, ',', '.') }}
                                    </span>
                                @elseif(($currentTierModel->discount_percent ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-950/70 border border-indigo-800/60 text-indigo-300 font-mono font-bold text-[9px]">
                                        Diskon {{ $currentTierModel->discount_percent }}%
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Flip Back Pill Button --}}
                            <div class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-900 border border-slate-700 text-[9px] font-bold text-slate-300">
                                <span class="material-symbols-outlined text-[11px] text-amber-400">qr_code</span>
                                <span>Lihat QR Code ↺</span>
                            </div>
                        </div>

                        @if(!empty($nextTierModel) && ($amountToNextTier ?? 0) > 0)
                            <div class="pt-1.5 border-t border-slate-800/80">
                                <div class="flex items-center justify-between text-[9px] mb-1">
                                    <span>Menuju Tier <strong class="text-white">{{ $nextTierModel->name }}</strong>:</span>
                                    <span class="text-amber-400 font-mono font-bold">Rp {{ number_format($amountToNextTier, 0, ',', '.') }} lagi</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1 overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-500 to-amber-400 h-1 rounded-full" style="width: {{ $tierProgressPercent }}%"></div>
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

                // Specular light position (visible only on front)
                if (!isFlipped && glare) {
                    glare.style.opacity = '1';
                    glare.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.4) 0%, transparent 60%)`;
                }
            }

            function onReset() {
                tiltX = 0;
                tiltY = 0;
                updateCardTransform();
                if (glare) glare.style.opacity = '0';
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
