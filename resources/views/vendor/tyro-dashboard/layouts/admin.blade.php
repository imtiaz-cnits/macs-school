<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">

    <title>@yield('title', 'Admin Dashboard') - {{ $branding['app_name'] ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
     <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&family=Onest:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    @include('tyro-dashboard::partials.styles')
    @stack('styles')
</head>

<body>
    @include('tyro-dashboard::partials.admin-bar')
    <div class="dashboard-layout">
        <!-- Admin Sidebar -->
        @include('tyro-dashboard::partials.admin-sidebar')

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            @include('tyro-dashboard::partials.topbar')

            <!-- Page Content -->
            <main class="page-content">
                <!-- Flash Messages -->
                @include('tyro-dashboard::partials.flash-messages')

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Global Modal -->
    @include('tyro-dashboard::partials.modal')

    @include('tyro-dashboard::partials.scripts')
    @stack('scripts')
</body>

</html>

<!-- <script>
    // মাউস রাইট-ক্লিক বন্ধ করা
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // কিবোর্ড শর্টকাট বন্ধ করা (F12, Ctrl+Shift+I, Ctrl+U)
    document.onkeydown = function(e) {
        if (e.keyCode == 123) { // F12
            return false;
        }
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) { // Ctrl+Shift+I
            return false;
        }
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) { // Ctrl+Shift+J
            return false;
        }
        if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) { // Ctrl+U (View Source)
            return false;
        }
    };
</script> -->
