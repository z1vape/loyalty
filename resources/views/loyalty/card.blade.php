<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Z1 Loyalty Card - {{ $customer->name }}</title>
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

        /* 3D PERSPECTIVE WRAPPER */
        .card-perspective {
            perspective: 1200px;
        }

        .card-3d {
            aspect-ratio: 652 / 427;
            position: relative;
            background-image: url('{{ asset("images/cropped-card.png") }}?v={{ filemtime(public_path("images/cropped-card.png")) }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.95), 0 0 35px rgba(255, 255, 255, 0.08);
            transform-style: preserve-3d;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
            overflow: hidden;
        }

        /* KILAU PERAK / PURE SILVER-WHITE METALLIC SHINE (TANPA GRADASI WARNA) */
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

        /* SPECULAR INTERACTIVE GLARE (PURE WHITE) */
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

        .glass-box {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
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

    <div class="w-full max-w-lg mx-auto space-y-5">

        {{-- Top App Bar --}}
        <div class="flex items-center justify-between px-2 pt-1">
            <div class="flex items-center space-x-2">
                <span class="font-orbitron font-black text-sm text-white tracking-widest">Z1 VAPE STORE</span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-orbitron bg-slate-800 text-slate-300 border border-slate-700">
                ★ {{ $customer->tier }}
            </span>
        </div>

        {{-- THE OFFICIAL Z1 LOYALTY CARD (HERO 3D CARD) --}}
        <div class="card-perspective w-full">
            <div id="cardElement" class="card-3d w-full">

                {{-- Kilau / Shimmer Animated Beam (Pure White Metallic) --}}
                <div class="card-shine-beam"></div>

                {{-- Interactive Specular Light Highlight --}}
                <div id="specularGleam" class="card-specular-glare"></div>

                {{-- Sparkle Stars at Silver Letter Corners --}}
                <div class="sparkle top-[28%] left-[24%]" style="animation-delay: 0.2s;"></div>
                <div class="sparkle top-[30%] right-[22%]" style="animation-delay: 1.8s;"></div>
                <div class="sparkle top-[10%] left-[58%]" style="animation-delay: 2.8s;"></div>

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

                {{-- 3. TERMS & CONDITIONS APPLY (Sejajar rapi di bawah baris MEMBER NO:) --}}
                <div class="absolute z-10 flex items-center overflow-hidden pointer-events-none" 
                     style="left: 6.4%; top: 90.2%; height: 4.8%; max-width: 60%;">
                    <span class="text-[8.5px] sm:text-[10px] text-slate-400 font-medium italic tracking-wider leading-none"
                          style="text-shadow: 0 1px 3px rgba(0,0,0,0.95);">
                        Terms &amp; Conditions Apply
                    </span>
                </div>

                {{-- 4. KOTAK QR CODE RAPI (SQUARE BERSIH TANPA KOTAK PUTIH DI BAWAHNYA) --}}
                <div class="absolute z-10 bg-white rounded-xl p-1.5 shadow-2xl flex items-center justify-center overflow-hidden"
                     style="left: 77%; top: 66%; width: 17.5%; aspect-ratio: 1/1;">
                    <div id="qrcode" class="w-full h-full flex items-center justify-center"></div>
                </div>

            </div>
        </div>

        {{-- Hint Interactive 3D --}}
        <p class="text-center text-[11px] text-slate-400 flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-slate-400">3d_rotation</span>
            <span>Sentuh atau goyangkan HP untuk melihat pantulan kilau 3D</span>
        </p>

        {{-- MEMBER POINTS WIDGET (RAPI, MEWAH & SESUAI TEMA KARTU) --}}
        <div class="rounded-2xl px-6 py-5 border border-slate-700/80 relative"
             style="background: linear-gradient(145deg, #131722 0%, #0c0f16 60%, #151924 100%); box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.1);">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-orbitron font-bold text-slate-400 uppercase tracking-widest block mb-1.5 leading-none">
                        SALDO POIN MEMBER
                    </span>
                    <div class="flex items-baseline gap-1.5">
                        <span class="font-orbitron font-black text-2xl sm:text-3xl text-white tracking-tight leading-none drop-shadow">
                            {{ number_format($customer->points) }}
                        </span>
                        <span class="font-orbitron font-bold text-xs text-slate-400 tracking-wider">
                            POIN
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block mb-1.5 leading-none">
                        Potongan Belanja
                    </span>
                    <div class="font-code font-bold text-base sm:text-lg text-emerald-400 tracking-wide leading-none drop-shadow">
                        Rp {{ number_format($customer->points * 100, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- SCRIPTS: GENERATE QR CODE & 3D INTERACTIVE TILT SHINE --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // 1. Generate Dynamic QR Code inside the clean white box
            const qrContainer = document.getElementById("qrcode");
            // Hitung ukuran pixel pas agar pas di dalam kotak
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
            const card = document.getElementById('cardElement');
            const glare = document.getElementById('specularGleam');

            function onMove(clientX, clientY) {
                const rect = card.getBoundingClientRect();
                const x = clientX - rect.left;
                const y = clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = ((y - centerY) / centerY) * -14;
                const rotateY = ((x - centerX) / centerX) * 14;

                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;

                // Specular light position follows finger/mouse (Pure white light)
                glare.style.opacity = '1';
                glare.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.4) 0%, transparent 60%)`;
            }

            function onReset() {
                card.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
                glare.style.opacity = '0';
            }

            // Desktop Mouse
            card.addEventListener('mousemove', (e) => onMove(e.clientX, e.clientY));
            card.addEventListener('mouseleave', onReset);

            // Mobile Touch
            card.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    onMove(e.touches[0].clientX, e.touches[0].clientY);
                }
            }, { passive: true });
            card.addEventListener('touchend', onReset);

            // Smartphone Gyroscope
            if (window.DeviceOrientationEvent) {
                window.addEventListener('deviceorientation', (e) => {
                    if (e.gamma !== null && e.beta !== null) {
                        const gamma = Math.max(-25, Math.min(25, e.gamma));
                        const beta = Math.max(-25, Math.min(25, e.beta - 45));
                        card.style.transform = `rotateY(${gamma * 0.7}deg) rotateX(${-beta * 0.7}deg)`;
                    }
                });
            }
        });
    </script>
</body>
</html>
