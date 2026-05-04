<nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" style="width: 260px; min-height: 100vh;">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="bi bi-hospital fs-4 ms-2 text-primary"></i>
        <span class="fs-5 fw-semibold">{{ config('app.name', 'Hospital') }}</span>
    </a>

    <hr>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 ms-2"></i> {{ __('Dashboard') }}
            </a>
        </li>

        @canany(['register_patients', 'view_patients'])
        <li class="nav-item mt-1">
            <a href="{{ route('patients.index') }}"
               class="nav-link text-white {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                <i class="bi bi-people ms-2"></i> {{ __('Patients') }}
            </a>
        </li>
        @endcanany

        @canany(['manage_admissions', 'view_admissions'])
        <li class="nav-item mt-1">
            <a href="{{ route('admissions.index') }}"
               class="nav-link text-white {{ request()->routeIs('admissions.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-pulse ms-2"></i> {{ __('Admissions') }}
            </a>
        </li>
        @endcanany

        @canany(['view_invoices', 'create_invoices', 'edit_invoices'])
        <li class="nav-item mt-1">
            <a href="{{ route('invoices.index') }}"
               class="nav-link text-white {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <i class="bi bi-receipt ms-2"></i> {{ __('Invoices') }}
            </a>
        </li>
        @endcanany

        @can('view_reports')
        <li class="nav-item mt-1">
            <a href="{{ route('reports.index') }}"
               class="nav-link text-white {{ request()->routeIs('reports.index') || request()->routeIs('reports.export') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph ms-2"></i> {{ __('Reports') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('reports.claim') }}"
               class="nav-link text-white {{ request()->routeIs('reports.claim*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-medical ms-2"></i> {{ __('Claim Sheet') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('reports.patient-list') }}"
               class="nav-link text-white {{ request()->routeIs('reports.patient-list*') ? 'active' : '' }}">
                <i class="bi bi-list-ol ms-2"></i> {{ __('Patient List') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('reports.summary') }}"
               class="nav-link text-white {{ request()->routeIs('reports.summary*') ? 'active' : '' }}">
                <i class="bi bi-collection ms-2"></i> {{ __('Summary') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('reports.performance') }}"
               class="nav-link text-white {{ request()->routeIs('reports.performance*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow ms-2"></i> {{ __('Performance Indicators') }}
            </a>
        </li>
        @endcan

        @can('manage_catalog')
        <li class="mt-3">
            <span class="text-uppercase text-muted small px-2">{{ __('Catalog') }}</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.medications.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.medications.*') ? 'active' : '' }}">
                <i class="bi bi-capsule ms-2"></i> {{ __('Medications') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.services.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.services.*') ? 'active' : '' }}">
                <i class="bi bi-heart-pulse ms-2"></i> {{ __('Services') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.insurance.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.insurance.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check ms-2"></i> {{ __('Insurance Companies') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.invoice-categories.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.invoice-categories.*') ? 'active' : '' }}">
                <i class="bi bi-layout-text-sidebar ms-2"></i> {{ __('Invoice Sections') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.units.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.units.*') ? 'active' : '' }}">
                <i class="bi bi-rulers ms-2"></i> {{ __('Units') }}
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.wards.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.wards.*') ? 'active' : '' }}">
                <i class="bi bi-building ms-2"></i> {{ __('Wards & Rooms') }}
            </a>
        </li>
        @endcan

        @canany(['manage_users'])
        <li class="mt-3">
            <span class="text-uppercase text-muted small px-2">{{ __('Administration') }}</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('users.index') }}"
               class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear ms-2"></i> {{ __('Users') }}
            </a>
        </li>
        @endcanany
        @role('super_admin')
        <li class="nav-item mt-1">
            <a href="{{ route('settings.index') }}"
               class="nav-link text-white {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear ms-2"></i> {{ __('Settings') }}
            </a>
        </li>
        @endrole
    </ul>

    <hr>

    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-5 ms-2"></i>
            <span class="text-truncate" style="max-width: 140px;">{{ auth()->user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li>
                <span class="dropdown-item-text text-muted small">
                    @foreach(auth()->user()->getRoleNames() as $role)
                        <span class="badge bg-secondary">{{ str_replace('_', ' ', $role) }}</span>
                    @endforeach
                </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right ms-1"></i> {{ __('Sign Out') }}
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>
