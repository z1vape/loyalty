<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Sign In | Z1 VapeOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: 'liga';
        }
    </style>
</head>
<body class="bg-white text-[#0b1c30] min-h-screen flex flex-col antialiased selection:bg-[#006c49] selection:text-white">

    <div class="flex-1 flex flex-col lg:flex-row min-h-screen">

        <!-- Left Column: Form & Brand Area (50% on desktop) -->
        <div class="w-full lg:w-1/2 flex flex-col justify-between p-6 sm:p-10 md:p-14 lg:p-16 xl:p-20 bg-white min-h-screen">
            
            <!-- Top: Brand Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <!-- Geometric 4-Cube Logo matching reference -->
                    <div class="grid grid-cols-2 gap-1 w-7 h-7 shrink-0">
                        <span class="w-3 h-3 rounded-xs bg-[#006c49]"></span>
                        <span class="w-3 h-3 rounded-xs bg-[#006c49]/60"></span>
                        <span class="w-3 h-3 rounded-xs bg-[#006c49]/40"></span>
                        <span class="w-3 h-3 rounded-xs bg-[#006c49]"></span>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-[#0b1c30]">Z1 VapeOS</span>
                </div>

                <!-- Quick Help / Support link -->
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-surface-container text-[#006c49] border border-emerald-100 hidden sm:inline-block">
                    Vape Retail OS v2.0
                </span>
            </div>

            <!-- Center: Main Login Form Area -->
            <div class="max-w-[420px] w-full mx-auto my-auto py-10">
                
                <!-- Headings -->
                <h1 class="text-3xl sm:text-4xl font-extrabold text-[#0b1c30] tracking-tight mb-2">
                    Welcome back
                </h1>
                <p class="text-sm sm:text-base text-slate-500 font-medium mb-8">
                    Please enter your details
                </p>

                <!-- Session Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center gap-2.5 shadow-2xs">
                        <span class="material-symbols-outlined text-base text-[#006c49]">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
                        <span class="material-symbols-outlined text-base text-rose-600 shrink-0 mt-0.5">error</span>
                        <div class="flex flex-col gap-0.5">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Auth Form -->
                <form action="{{ route('login') }}" method="POST" id="login-form" class="space-y-5">
                    @csrf

                    <!-- Email address -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-bold text-[#0b1c30] mb-2">
                            Email address
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            required 
                            autocomplete="username" 
                            value="{{ old('email', 'admin@inventrack.com') }}" 
                            placeholder="Enter your email"
                            class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm text-[#0b1c30] placeholder:text-slate-400 focus:outline-none focus:border-[#006c49] focus:ring-2 focus:ring-[#006c49]/20 transition-all font-medium"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs sm:text-sm font-bold text-[#0b1c30] mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required 
                                autocomplete="current-password" 
                                value="password" 
                                placeholder="••••••••"
                                class="w-full pl-4 pr-11 py-3 bg-white border border-slate-300 rounded-xl text-sm text-[#0b1c30] placeholder:text-slate-400 focus:outline-none focus:border-[#006c49] focus:ring-2 focus:ring-[#006c49]/20 transition-all font-medium tracking-wide"
                            />
                            <button 
                                type="button" 
                                onclick="togglePasswordVisibility()" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#006c49] p-1 transition cursor-pointer"
                                title="Lihat / Sembunyikan Sandi"
                            >
                                <span id="pwd-icon" class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Remember for 30 days & Forgot Password -->
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                id="remember" 
                                checked 
                                class="w-4 h-4 rounded border-slate-300 text-[#006c49] focus:ring-[#006c49] cursor-pointer"
                            />
                            <label for="remember" class="text-xs sm:text-sm font-medium text-slate-700 cursor-pointer select-none">
                                Remember for 30 days
                            </label>
                        </div>

                        <button 
                            type="button" 
                            onclick="fillDemoCredentials()" 
                            class="text-xs sm:text-sm font-bold text-[#006c49] hover:text-[#00553a] hover:underline cursor-pointer"
                        >
                            Forgot password
                        </button>
                    </div>

                    <!-- Primary Action Button: Sign in -->
                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-4 bg-[#006c49] hover:bg-[#00553a] text-white font-bold rounded-xl active:scale-95 duration-150 transition-all shadow-sm hover:shadow text-sm cursor-pointer flex items-center justify-center gap-2 mt-6"
                    >
                        <span>Sign in</span>
                    </button>

                    <!-- Sign Up Link -->
                    <div class="text-center pt-2 text-xs sm:text-sm text-slate-500 font-medium">
                        Don't have an account? 
                        <a href="javascript:void(0)" onclick="fillDemoCredentials()" class="font-bold text-[#006c49] hover:underline cursor-pointer ml-1">
                            Sign up
                        </a>
                    </div>

                </form>

            </div>

            <!-- Bottom: Footer info -->
            <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2 border-t border-slate-100 pt-5">
                <span>© {{ date('Y') }} Z1 VapeOS • Z1 Vape Store. Hak Cipta Dilindungi.</span>
                <span class="flex items-center gap-1.5 text-slate-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#006c49]"></span>
                    <span>Sistem Terenkripsi SSL</span>
                </span>
            </div>

        </div>

        <!-- Right Column: Emerald Themed Modern Illustration (50% on desktop) -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#10814d] relative items-center justify-center overflow-hidden min-h-screen select-none">
            
            <!-- Atmospheric Gradient Glow -->
            <div class="absolute inset-0 bg-gradient-to-tr from-[#00553a] via-[#10814d] to-[#129457] pointer-events-none"></div>

            <!-- Subtle Decorative Circles in Background -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/[0.04] pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-black/[0.06] pointer-events-none"></div>

            <!-- Main Vector Character & Line-Art Illustration -->
            <div class="relative z-10 w-full h-full max-h-[860px] flex items-center justify-center p-8 xl:p-12">
                <img 
                    src="/images/auth-hero-illustration.jpg" 
                    alt="Z1 VapeOS Retail System" 
                    class="w-full h-full object-contain max-h-[780px] drop-shadow-2xl pointer-events-none"
                />
            </div>

        </div>

    </div>

    <script>
        // Toggle password show/hide
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const pwdIcon = document.getElementById('pwd-icon');
            if (!pwdInput || !pwdIcon) return;

            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                pwdIcon.innerText = 'visibility_off';
            } else {
                pwdInput.type = 'password';
                pwdIcon.innerText = 'visibility';
            }
        }

        // Fill demo credentials
        function fillDemoCredentials() {
            const emailInput = document.getElementById('email');
            const pwdInput = document.getElementById('password');
            if (emailInput && pwdInput) {
                emailInput.value = 'admin@inventrack.com';
                pwdInput.value = 'password';
                
                // Submit form directly for seamless login
                const form = document.getElementById('login-form');
                if (form) {
                    form.submit();
                }
            }
        }
    </script>
</body>
</html>
