<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pabna International School EMS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        themeNavy: '#002C53',
                        themeOrange: '#FFA155',
                        themeNavyDark: '#001A33',
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
</head>
<body class="font-sans text-gray-900 antialiased overflow-hidden">

    <div class="flex min-h-screen bg-white dark:bg-gray-900">
        
        <div class="hidden lg:flex lg:w-1/2 bg-themeNavy relative items-center justify-center overflow-hidden">
            <div class="absolute inset-0 z-0">
               <img src="{{ asset('img/school.jpg') }}" class="object-cover w-full h-full opacity-20" alt="School Background">
            </div>
            
            <div class="relative z-10 px-12 text-center text-white flex flex-col items-center">
                <div class="mb-8 rounded-full shadow-2xl border-white/20 backdrop-blur-sm overflow-hidden flex items-center justify-center">
                    <img src="{{ asset('img/logo.svg') }}" class="w-32 h-32 object-contain" alt="Pabna International School Logo">
                </div>

                <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tighter uppercase">Pabna International School</h1>
                <p class="text-lg md:text-xl text-blue-100 font-medium mb-8">Smart Education Management System</p>
                
                <div class="flex gap-4 text-sm text-blue-200/80 font-bold bg-black/20 py-2.5 px-8 rounded-full backdrop-blur-md border border-white/10 uppercase tracking-widest">
                    <span>Admin</span> • <span>Teacher</span> • <span>Student</span>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-gray-50 dark:bg-gray-900 relative">
            
            <div class="w-full max-w-md">
                
                <div class="lg:hidden flex flex-col items-center mb-10 text-center">
                    <div class="bg-white rounded-full shadow-lg mb-4 border-2 border-themeNavy overflow-hidden flex items-center justify-center">
                        <img src="{{ asset('img/logo.svg') }}" class="w-20 h-20 object-contain" alt="Logo">
                    </div>
                    <h2 class="text-2xl font-extrabold text-themeNavy dark:text-themeOrange uppercase tracking-tighter">Pabna International School</h2>
                </div>

                <div class="text-center lg:text-left mb-10">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2 uppercase tracking-tighter">Welcome Back!</h2>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Please sign in to your dashboard</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-themeNavy/10 focus:border-themeNavy outline-none transition-all shadow-sm font-semibold" placeholder="admin@example.com">
                        </div>
                        @error('email')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400 font-black uppercase tracking-tighter">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest ml-1">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[10px] font-black text-themeNavy hover:text-themeOrange dark:text-themeOrange dark:hover:text-amber-300 hover:underline uppercase tracking-widest">Forgot?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full pl-12 pr-12 py-4 rounded-2xl border-2 border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-themeNavy/10 focus:border-themeNavy outline-none transition-all shadow-sm font-semibold" placeholder="••••••••">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-themeOrange hover:scale-110 active:scale-95 dark:text-gray-400 dark:hover:text-themeOrange transition-all duration-200 focus:outline-none">
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
                        <input id="remember_me" type="checkbox" name="remember" class="w-5 h-5 text-themeNavy bg-gray-100 border-gray-300 rounded-lg focus:ring-themeNavy cursor-pointer">
                        <label for="remember_me" class="ml-3 block text-xs font-black text-gray-600 dark:text-gray-400 cursor-pointer uppercase tracking-widest">Keep me signed in</label>
                    </div>

                    <button type="submit" class="w-full py-4 px-4 bg-themeNavy hover:bg-themeOrange text-white font-black rounded-2xl shadow-xl shadow-themeNavy/20 hover:shadow-themeNavy/40 hover:-translate-y-1 transition-all duration-300 flex justify-center items-center group uppercase tracking-[0.2em] text-sm">
                        Sign In to Dashboard
                        <svg class="w-5 h-5 ml-3 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    
                </form>

                <div class="mt-16 pt-8 border-t border-gray-100 dark:border-gray-800 text-center lg:text-left">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-black uppercase tracking-[0.15em]">
                        Powered by 
                        <a href="https://www.codenextit.com" target="_blank" class="text-themeNavy dark:text-themeOrange font-black hover:underline underline-offset-8 transition-all">
                            Code Next IT
                        </a>
                    </p>
                </div>

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
    </script>
</body>
</html>