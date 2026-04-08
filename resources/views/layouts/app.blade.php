<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hospital') }} — @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
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
    </style>
    @stack('styles')
</head>
<body>

<div id="wrapper">
    {{-- Sidebar --}}
    @include('partials.sidebar')

    {{-- Page content --}}
    <div id="page-content">
        {{-- Top navbar --}}
        <header class="topbar d-flex align-items-center px-3 py-2">
            <button class="btn btn-sm btn-outline-secondary me-3 d-none" id="sidebarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>
            <nav aria-label="breadcrumb" class="me-auto">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
            <span class="text-muted small me-2">
                <i class="bi bi-calendar3 me-1"></i>{{ now()->format('D, d M Y') }}
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
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
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
            &copy; {{ date('Y') }} {{ config('app.name', 'Hospital') }} — Insurance Billing System
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('sweetalert::alert')
@stack('scripts')
</body>
</html>
