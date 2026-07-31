<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hospital') }} — @yield('title', __('Dashboard'))</title>

    {{-- Google Fonts: Cairo --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 RTL --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            overflow-x: hidden;
        }
        #wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        #page-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                right: 0;
                height: 100vh;
                z-index: 1045;
                transform: translateX(100%);
                transition: transform 0.25s ease-in-out;
                box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.4);
                z-index: 1040;
                display: none;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
        .topbar {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
        }
        .main-content {
            flex: 1;
            padding: 1.5rem;
            background-color: #f8f9fa;
        }
        .nav-link.active {
            background-color: #0d6efd !important;
        }
        .nav-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
        }
        /* RTL breadcrumb separator */
        .breadcrumb-item + .breadcrumb-item::before {
            float: right;
            padding-left: 0.5rem;
            padding-right: 0;
            content: var(--bs-breadcrumb-divider, "/");
        }
    </style>
    @stack('styles')
</head>
<body>

<div id="wrapper">
    {{-- Sidebar --}}
    @include('partials.sidebar')
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Page content --}}
    <div id="page-content">
        {{-- Top navbar --}}
        <header class="topbar d-flex align-items-center px-3 py-2">
            <button class="btn btn-outline-secondary d-lg-none ms-2" id="sidebarToggle" type="button" aria-label="{{ __('Menu') }}">
                <i class="bi bi-list fs-5"></i>
            </button>
            <nav aria-label="breadcrumb" class="me-auto">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
            <span class="text-muted small ms-2">
                <i class="bi bi-calendar3 ms-1"></i>{{ now()->locale('ar')->isoFormat('D MMMM YYYY') }}
            </span>
        </header>

        {{-- Page title bar --}}
        @hasSection('page_title')
        <div class="px-3 pt-3">
            <h5 class="fw-semibold mb-0">@yield('page_title')</h5>
        </div>
        @endif

        {{-- Flash messages --}}
        <div class="px-3 pt-3">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle ms-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-circle ms-1"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>

        {{-- Main content --}}
        <main class="main-content">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="text-center text-muted small py-2 border-top bg-white">
            &copy; {{ date('Y') }} {{ config('app.name', 'Hospital') }} — {{ __('Insurance Billing System') }}
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar  = document.getElementById('sidebar');
        const toggle   = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        function closeSidebar() {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        }

        toggle?.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });
        backdrop?.addEventListener('click', closeSidebar);
        sidebar?.querySelectorAll('a, button[type="submit"]').forEach(function (el) {
            el.addEventListener('click', closeSidebar);
        });
    });
</script>
@include('sweetalert::alert')
@stack('scripts')
</body>
</html>
