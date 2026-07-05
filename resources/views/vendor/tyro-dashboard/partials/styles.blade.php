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
                    sans: ['Figtree', 'Onest', 'sans-serif'],
                }
            }
        }
    }
</script>

{{-- Include shadcn theme variables --}}
@include('tyro-dashboard::partials.shadcn-theme')

<style>
    :root {
        --background: #ffffff;
        --foreground: #09090b;
        --card: #ffffff;
        --card-foreground: #09090b;
        --popover: #ffffff;
        --popover-foreground: #09090b;
        --primary: #008ED6;
        --primary-foreground: #ffffff;
        --secondary: #f4f4f5;
        --secondary-foreground: #18181b;
        --muted: #f4f4f5;
        --muted-foreground: #71717a;
        --accent: #f4f4f5;
        --accent-foreground: #18181b;
        --destructive: #ef4444;
        --destructive-foreground: #fafafa;
        --border: #e4e4e7;
        --input: #e4e4e7;
        --ring: #008ED6;
        --radius: 0.5rem;
        --sidebar: #ffffff;
        --sidebar-foreground: #475569;
        --sidebar-border: #e2e8f0;
        --sidebar-accent: #f0f9ff;
        --sidebar-accent-foreground: #008ED6;
        --sidebar-primary: #008ED6;
        --sidebar-primary-foreground: #ffffff;
        @if($sidebar_bg = config('tyro-dashboard.branding.sidebar_bg'))
            --sidebar: {{ $sidebar_bg }} !important;
        @endif
        @if($sidebar_text = config('tyro-dashboard.branding.sidebar_text'))
            --sidebar-foreground: {{ $sidebar_text }} !important;
        @endif
    }
    
    .dark {
        --background: #070E14;
        --foreground: #fafafa;
        --card: #0F1E2C;
        --card-foreground: #fafafa;
        --popover: #0F1E2C;
        --popover-foreground: #fafafa;
        --primary: #008ED6;
        --primary-foreground: #ffffff;
        --secondary: rgba(255, 255, 255, 0.06);
        --secondary-foreground: #fafafa;
        --muted: rgba(255, 255, 255, 0.04);
        --muted-foreground: #a1a1aa;
        --accent: rgba(0, 142, 214, 0.08);
        --accent-foreground: #fafafa;
        --destructive: #7f1d1d;
        --destructive-foreground: #fafafa;
        --border: rgba(255, 255, 255, 0.08);
        --input: rgba(255, 255, 255, 0.08);
        --ring: #008ED6;
        --sidebar: #070E14;
        --sidebar-foreground: #cbd5e1;
        --sidebar-border: rgba(255,255,255,0.06);
        --sidebar-accent: rgba(0, 142, 214, 0.12);
        --sidebar-accent-foreground: #ffffff;
        --sidebar-primary: #008ED6;
        --sidebar-primary-foreground: #ffffff;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        scrollbar-width: thin;
        scrollbar-color: var(--border) var(--background);
    }

    html::-webkit-scrollbar {
        width: 10px;
    }

    html::-webkit-scrollbar-track {
        background: var(--background);
    }

    html::-webkit-scrollbar-thumb {
        background-color: var(--border);
        border-radius: 6px;
        border: 2px solid var(--background);
    }

    html::-webkit-scrollbar-thumb:hover {
        background-color: var(--muted-foreground);
    }

    body {
        font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        background-color: var(--background);
        min-height: 100vh;
        line-height: 1.6;
        color: var(--foreground);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-size: 16px;
    }

    h1, h2, h3, h4, h5, h6, .font-secondary {
        font-family: 'Onest', 'Figtree', sans-serif !important;
    }

    /* Dashboard Layout */
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
        background-color: var(--background);
    }

    /* Sidebar - Vercel style overrides */
    .sidebar {
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden !important;
        z-index: 100;
        transition: width 0.2s ease, transform 0.2s ease;
        display: flex;
        flex-direction: column;
        scrollbar-width: thin;
        scrollbar-color: var(--border) transparent;
        background-color: #ffffff !important;
        border-right: 1px solid #eaeaea !important;
    }

    .dark .sidebar {
        background-color: #000000 !important;
        border-right: 1px solid #111111 !important;
    }

    /* Custom Scrollbar for Sidebar */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: var(--sidebar-border);
        border-radius: 2px;
    }

    /* Collapsed Sidebar */
    .sidebar.collapsed {
        width: 70px !important;
        overflow: visible !important;
    }

    .sidebar.collapsed .sidebar-nav {
        overflow: visible !important;
    }

    .sidebar.collapsed .sidebar-header {
        display: flex !important;
        justify-content: center !important;
        padding: 0 !important;
    }

    .sidebar.collapsed .sidebar-logo {
        justify-content: center !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .sidebar.collapsed .sidebar-logo-text {
        opacity: 0 !important;
        visibility: hidden !important;
        display: none !important;
    }

    .sidebar.collapsed .sidebar-link,
    .sidebar.collapsed .sidebar-section-title {
        justify-content: center !important;
        padding: 9px 0 !important;
        margin: 4px 8px !important;
        border-radius: 8px !important;
        width: calc(100% - 16px) !important;
    }

    .sidebar.collapsed .sidebar-link > span:first-child {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0 !important;
    }

    .sidebar.collapsed .sidebar-link svg {
        opacity: 1 !important;
        margin: 0 !important;
        width: 18px !important;
        height: 18px !important;
    }

    .sidebar.collapsed .sidebar-text,
    .sidebar.collapsed .sidebar-link > span:last-child {
        display: none !important;
    }

    .sidebar.collapsed .sidebar-submenu {
        display: none !important;
    }

    .sidebar.collapsed .sidebar-back-btn {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        width: 36px !important;
        height: 36px !important;
        padding: 0 !important;
        margin: 8px auto 12px auto !important;
        border-radius: 8px !important;
    }

    .sidebar.collapsed .sidebar-back-btn span {
        display: none !important;
    }

    /* Floating Submenu Popup for Collapsed Sidebar */
    .sidebar-popup-menu {
        display: none !important;
    }

    .sidebar.collapsed .group\/trigger:hover .sidebar-popup-menu {
        display: block !important;
    }

    /* Vercel Sidebar Links & Accords styling */
    .sidebar-link, .sidebar-section-title {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 9px 12px !important;
        margin: 8px 8px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #5b5b5b !important;
        background-color: transparent !important;
        border-radius: 6px !important;
        border-left: none !important;
        transition: background-color 0.15s ease, color 0.15s ease, padding 0.15s ease !important;
        cursor: pointer;
        text-decoration: none;
    }
    .dark .sidebar-link, .dark .sidebar-section-title {
        color: #9c9c9c !important;
    }

    .sidebar-link svg, .sidebar-section-title svg {
        width: 16px !important;
        height: 16px !important;
        opacity: 0.7;
        transition: opacity 0.15s ease;
        flex-shrink: 0;
    }

    .sidebar-link:hover, .sidebar-section-title:hover {
        background-color: rgba(0, 0, 0, 0.04) !important;
        color: #000000 !important;
    }
    .dark .sidebar-link:hover, .dark .sidebar-section-title:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
    }
    .sidebar-link:hover svg, .sidebar-section-title:hover svg {
        opacity: 1 !important;
    }

    .sidebar-link.active, .sidebar-section.open .sidebar-section-title {
        background-color: rgba(0, 0, 0, 0.06) !important;
        color: #000000 !important;
        font-weight: 600 !important;
        padding-left: 10px !important;
    }
    .dark .sidebar-link.active, .dark .sidebar-section.open .sidebar-section-title {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .sidebar-link.active svg, .sidebar-section.open .sidebar-section-title svg {
        opacity: 1 !important;
    }

    /* Highlighted Back Button styling */
    .sidebar-back-btn {
        width: calc(100% - 16px) !important;
        margin: 0 8px 12px 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.65rem !important;
        padding: 9px 12px !important;
        font-size: 13.5px !important;
        font-weight: 800 !important;
        color: #008ED6 !important;
        background-color: rgba(0, 142, 214, 0.05) !important;
        border: 1px solid rgba(0, 142, 214, 0.15) !important;
        border-radius: 8px !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
    }
    .dark .sidebar-back-btn {
        background-color: rgba(0, 142, 214, 0.08) !important;
        border: 1px solid rgba(0, 142, 214, 0.2) !important;
        color: #008ed6 !important;
    }
    .sidebar-back-btn:hover {
        background-color: rgba(0, 142, 214, 0.1) !important;
        border-color: rgba(0, 142, 214, 0.3) !important;
        transform: translateX(-2px) !important;
    }
    .sidebar-back-btn svg {
        width: 14px !important;
        height: 14px !important;
        stroke-width: 3.5 !important;
        color: #008ED6 !important;
        opacity: 1 !important;
    }

    /* Submenus Vercel Style (Clean indentation, no lines) */
    .sidebar-submenu {
        background-color: transparent !important;
        border-left: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 14px !important;
        padding-right: 0 !important;
    }

    .sidebar-submenu .sidebar-link {
        font-size: 13px !important;
        padding: 7.5px 12px !important;
        margin: 2px 8px !important;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 250px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        transition: margin-left 0.3s ease;
        background-color: var(--background);
    }

    .sidebar.collapsed ~ .main-content {
        margin-left: 70px;
    }

    /* Top Bar - shadcn style */
    .topbar {
        position: sticky;
        top: 0;
        z-index: 50;
        background-color: var(--background);
        border-bottom: 1px solid var(--border);
        padding: 0 1.5rem;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* User Dropdown */
    .user-dropdown {
        position: relative;
    }

    .user-dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 220px;
        background-color: var(--background);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        padding: 0.5rem;
        display: none;
        z-index: 100;
    }

    .user-dropdown.active .user-dropdown-menu {
        display: block;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
        text-decoration: none;
        border-radius: 8px;
        transition: background-color 0.15s ease;
        cursor: pointer;
    }

    .dropdown-item:hover {
        background-color: var(--accent);
        color: var(--accent-foreground);
    }

    .dropdown-item-danger {
        color: #ef4444;
    }

    .dropdown-item-danger:hover {
        background-color: #fee2e2;
        color: #ef4444;
    }

    .dropdown-divider {
        height: 1px;
        background-color: var(--border);
        margin: 0.5rem 0;
    }

    /* Content Area */
    .content-body {
        flex: 1;
        padding: 1.5rem;
        background-color: var(--background);
    }

    .page-content {
        width: 100%;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 1.2rem 1.5rem;
        display: flex;
        flex-direction: column;
        /* gap: 1.5rem; */
        box-sizing: border-box;
    }


    /* ==========================================
       MACS School Premium Global Class Overrides
       ========================================== */

    /* Cards */
    .card {
        background-color: var(--card);
        border: 1px solid var(--border);
        border-radius: 24px !important; /* rounded-3xl */
        padding: 1.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
        transform: translateY(-1px);
    }

    .card-body {
        padding: 0;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 16px !important; /* rounded-2xl */
        font-size: 0.75rem !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        padding: 0.625rem 1.25rem !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-sm {
        padding: 0.45rem 0.9rem !important;
        font-size: 0.7rem !important;
        border-radius: 12px !important; /* Smaller border radius for smaller buttons */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-xs {
        padding: 0.35rem 0.7rem !important;
        font-size: 0.65rem !important;
        border-radius: 8px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn:hover, .btn-sm:hover, .btn-xs:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .btn-primary {
        background: linear-gradient(to right, #008ED6, #009A49) !important;
        color: #ffffff !important;
        border: none !important;
    }

    .btn-primary:hover {
        opacity: 0.95;
        box-shadow: 0 10px 15px -3px rgba(0, 142, 214, 0.25) !important;
    }

    .btn-secondary {
        background-color: var(--secondary) !important;
        color: var(--secondary-foreground) !important;
        border: 1px solid var(--border) !important;
    }

    .btn-secondary:hover {
        background-color: var(--accent) !important;
    }

    .btn-ghost {
        background: transparent !important;
        color: var(--foreground) !important;
        border: none !important;
    }

    .btn-ghost:hover {
        background-color: var(--accent) !important;
        color: var(--accent-foreground) !important;
    }

    /* Form Fields */
    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        height: 42px;
        padding: 0.5rem 1rem;
        border-radius: 12px !important; /* rounded-xl */
        border: 2px solid var(--border) !important;
        background-color: var(--background) !important;
        color: var(--foreground) !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
        outline: none !important;
        box-sizing: border-box;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #008ED6 !important;
        box-shadow: 0 0 0 4px rgba(0, 142, 214, 0.1) !important;
    }

    .form-label {
        font-size: 10px !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        text-transform: uppercase !important;
        color: var(--muted-foreground) !important;
        margin-bottom: 0.5rem !important;
        display: block;
    }

    /* Tables */
    .table-container {
        width: 100%;
        overflow-x: auto;
        border-radius: 16px !important;
        border: 1px solid var(--border) !important;
        background-color: var(--card);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .table th {
        font-size: 10px !important;
        font-weight: 900 !important;
        letter-spacing: 0.15em !important;
        text-transform: uppercase !important;
        color: var(--muted-foreground) !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid var(--border) !important;
        background-color: var(--muted) !important;
    }

    .table td {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid var(--border) !important;
        font-size: 0.875rem !important;
        color: var(--foreground) !important;
        background-color: transparent !important;
    }

    .table tr:last-child td {
        border-bottom: none !important;
    }

    .table tr:hover td {
        background-color: var(--accent) !important;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        font-size: 10px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        padding: 0.25rem 0.75rem !important;
        border-radius: 8px !important; /* rounded-lg */
        white-space: nowrap;
    }

    .badge-primary {
        background-color: rgba(0, 142, 214, 0.1) !important;
        color: #008ED6 !important;
        border: 1px solid rgba(0, 142, 214, 0.2) !important;
    }

    .badge-success {
        background-color: rgba(0, 154, 73, 0.1) !important;
        color: #009A49 !important;
        border: 1px solid rgba(0, 154, 73, 0.2) !important;
    }

    .badge-danger {
        background-color: rgba(239, 68, 68, 0.1) !important;
        color: #ef4444 !important;
        border: 1px solid rgba(239, 68, 68, 0.2) !important;
    }

    .badge-secondary {
        background-color: var(--secondary) !important;
        color: var(--muted-foreground) !important;
        border: 1px solid var(--border) !important;
    }

    /* Action Buttons in tables */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
        border: 1px solid var(--border) !important;
        background-color: var(--background) !important;
        color: var(--muted-foreground) !important;
        transition: all 0.2s ease !important;
        cursor: pointer;
        text-decoration: none;
    }

    .action-btn:hover {
        background-color: var(--accent) !important;
        color: var(--foreground) !important;
        border-color: var(--foreground) !important;
    }

    .action-btn svg {
        width: 16px !important;
        height: 16px !important;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-title {
        font-size: 1.875rem !important; /* text-3xl */
        font-weight: 900 !important; /* font-black */
        letter-spacing: -0.025em !important; /* tracking-tight */
        color: var(--foreground) !important;
    }

    .page-description {
        font-size: 0.875rem !important; /* text-sm */
        font-weight: 500 !important; /* font-medium */
        color: var(--muted-foreground) !important;
        margin-top: 0.25rem;
    }
    .transition-colors {
        transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    .duration-200 {
        transition-duration: 200ms;
    }

    .duration-300 {
        transition-duration: 300ms;
    }

    .ease-in-out {
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Search and Filters Layout overrides */
    .filters-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
        width: 100%;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .search-box svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: var(--muted-foreground);
        pointer-events: none;
    }

    .search-box .form-input {
        padding-left: 2.25rem !important;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .filter-label {
        font-size: 10px !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        text-transform: uppercase !important;
        color: var(--muted-foreground) !important;
    }

    /* Users / Accounts Cell Table Components */
    .user-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--foreground);
    }

    .user-cell-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: var(--secondary);
        color: var(--foreground);
        font-size: 0.875rem;
        font-weight: 700;
        overflow: hidden;
    }

    .user-cell-name {
        font-weight: 700;
        color: var(--foreground);
    }

    .user-cell-email {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }

    .badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
    }

    .sidebar-logo-text {
        background: linear-gradient(to right, #008ED6, #009A49) !important;
        -webkit-background-clip: text !important;
        background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        color: transparent !important;
    }
</style>
