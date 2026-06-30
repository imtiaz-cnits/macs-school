<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'MACS School & College') }} EMS</title>
    
    <script>
        // Check local storage or prefers-color-scheme to set initial theme immediately and prevent flashing
        const initialTheme = localStorage.getItem('tyro-dashboard-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (initialTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        themeBlue: '#008ED6',
                        themeGreen: '#009A49',
                        themeDark: '#070E14',
                        themeNavy: '#0F1E2C',
                    },
                    fontFamily: {
                        sans: ['Figtree', 'sans-serif'],
                        secondary: ['Onest', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&family=Onest:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .gradient-mesh {
            background-color: #070E14;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 142, 214, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 90% 80%, rgba(0, 154, 73, 0.12) 0%, transparent 45%);
        }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased overflow-hidden">

    <div class="flex min-h-screen bg-white dark:bg-themeDark relative">
        
        <!-- Theme Switcher (Top Right Corner) -->
        <div class="absolute top-6 right-6 z-50">
            <button type="button" onclick="toggleTheme()" class="w-10 h-10 rounded-xl bg-white dark:bg-themeNavy border border-gray-200 dark:border-gray-800/80 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-themeBlue dark:hover:text-themeBlue shadow-md hover:scale-105 active:scale-95 transition-all cursor-pointer" aria-label="Toggle theme">
                <!-- Sun icon (shows in dark mode) -->
                <svg class="sun-icon w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <!-- Moon icon (shows in light mode) -->
                <svg class="moon-icon w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <!-- Left Hero Column (Desktop only) -->
        <div class="hidden lg:flex lg:w-1/2 gradient-mesh relative items-center justify-center overflow-hidden border-r border-gray-800">
            <div class="absolute inset-0 z-0">
               <img src="{{ asset('img/school.jpg') }}" class="object-cover w-full h-full opacity-10 mix-blend-overlay" alt="School Background">
            </div>
            
            <div class="relative z-10 px-12 text-center text-white flex flex-col items-center">
                <!-- Premium glassmorphic container for branding logo -->
                <div class="bg-white/5 border border-white/10 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl flex flex-col items-center max-w-sm">
                    <div class="w-36 h-36 rounded-2xl bg-white border border-white/20 shadow-2xl overflow-hidden flex items-center justify-center p-3 relative group transition-transform duration-500 hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-tr from-themeBlue/10 to-themeGreen/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-full h-full object-contain relative z-10" alt="{{ config('app.name', 'MACS School & College') }} Logo">
                    </div>

                    <h1 class="text-3xl font-extrabold mt-6 bg-gradient-to-r from-themeBlue via-blue-400 to-themeGreen bg-clip-text text-transparent tracking-tighter uppercase text-center leading-tight">
                        {{ config('app.name', 'MACS School & College') }}
                    </h1>
                    <p class="text-sm text-gray-400 font-semibold mt-2 tracking-wide">Smart Education Management System</p>
                    
                    <div class="mt-6 inline-flex gap-2 text-[10px] font-black text-white/90 bg-white/10 py-2 px-6 rounded-full border border-white/10 tracking-[0.2em] uppercase">
                        ESTD. 2007 • JALALPUR, PABNA
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Column -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-gray-50 dark:bg-themeDark relative">
            
            <div class="w-full max-w-md">
                
                <!-- Logo & Brand for Mobile screens -->
                <div class="lg:hidden flex flex-col items-center mb-10 text-center">
                    <div class="bg-white p-2 rounded-2xl shadow-xl mb-4 border border-gray-100 dark:border-gray-800 overflow-hidden flex items-center justify-center w-24 h-24">
                        <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-full h-full object-contain" alt="Logo">
                    </div>
                    <h2 class="text-2xl font-black bg-gradient-to-r from-themeBlue to-themeGreen bg-clip-text text-transparent uppercase tracking-tighter">{{ config('app.name', 'MACS School & College') }}</h2>
                </div>

                <div class="text-center lg:text-left mb-8">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-tighter">Welcome Back!</h2>
                    <p class="text-gray-500 dark:text-gray-400 font-bold text-sm tracking-wide">Please sign in to your dashboard</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy/45 text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all shadow-sm font-semibold placeholder-gray-400" placeholder="admin@example.com">
                        </div>
                        @error('email')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400 font-black uppercase tracking-tighter">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest ml-1">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[10px] font-black text-themeBlue hover:text-themeGreen transition-colors uppercase tracking-widest">Forgot?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full pl-12 pr-12 py-4 rounded-2xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy/45 text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all shadow-sm font-semibold placeholder-gray-400" placeholder="••••••••">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-themeBlue hover:scale-110 active:scale-95 dark:text-gray-500 dark:hover:text-themeBlue transition-all duration-200 focus:outline-none">
                                <svg id="eyeOpenIcon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eyeClosedIcon" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400 font-black uppercase tracking-tighter">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="w-5 h-5 text-themeBlue bg-gray-100 border-gray-300 dark:border-gray-800 rounded-lg focus:ring-themeBlue cursor-pointer">
                        <label for="remember_me" class="ml-3 block text-xs font-black text-gray-600 dark:text-gray-400 cursor-pointer uppercase tracking-widest">Keep me signed in</label>
                    </div>

                    <button type="submit" class="w-full py-4 px-4 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-blue-600 hover:to-green-600 text-white font-black rounded-2xl shadow-xl shadow-themeBlue/25 hover:shadow-themeBlue/45 hover:-translate-y-0.5 transition-all duration-300 flex justify-center items-center group uppercase tracking-[0.2em] text-sm cursor-pointer">
                        Sign In to Dashboard
                        <svg class="w-5 h-5 ml-3 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    
                </form>

            </div>
        </div>

        <!-- Powered by Code Next IT (Bottom Right Corner) -->
        <div class="absolute bottom-6 right-6 z-50 text-right">
            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-black uppercase tracking-[0.15em]">
                Powered by 
                <a href="https://www.codenextit.com" target="_blank" class="text-themeBlue hover:text-themeGreen font-black hover:underline underline-offset-8 transition-colors">
                    Code Next IT
                </a>
            </p>
        </div>

    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpenIcon = document.getElementById('eyeOpenIcon');
            const eyeClosedIcon = document.getElementById('eyeClosedIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpenIcon.classList.add('hidden');
                eyeClosedIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpenIcon.classList.remove('hidden');
                eyeClosedIcon.classList.add('hidden');
            }
        }

        // Theme management methods
        function getTheme() {
            if (localStorage.getItem('tyro-dashboard-theme')) {
                return localStorage.getItem('tyro-dashboard-theme');
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function setTheme(theme) {
            localStorage.setItem('tyro-dashboard-theme', theme);
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            updateThemeIcons(theme);
        }

        function toggleTheme() {
            const currentTheme = getTheme();
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        }

        function updateThemeIcons(theme) {
            const sunIcon = document.querySelector('.sun-icon');
            const moonIcon = document.querySelector('.moon-icon');
            
            if (sunIcon && moonIcon) {
                if (theme === 'dark') {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                } else {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            }
        }

        // Sync icons on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateThemeIcons(getTheme());
        });
    </script>
</body>
</html>