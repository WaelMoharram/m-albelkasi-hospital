<nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" style="width: 260px; min-height: 100vh;">
    <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="bi bi-hospital fs-4 me-2 text-primary"></i>
        <span class="fs-5 fw-semibold">{{ config('app.name', 'Hospital') }}</span>
    </a>

    <hr>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        @canany(['register_patients', 'view_patients'])
        <li class="nav-item mt-1">
            <a href="{{ route('patients.index') }}"
               class="nav-link text-white {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                <i class="bi bi-people me-2"></i> Patients
            </a>
        </li>
        @endcanany

        @canany(['manage_admissions', 'view_admissions'])
        <li class="nav-item mt-1">
            <a href="{{ route('admissions.index') }}"
               class="nav-link text-white {{ request()->routeIs('admissions.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-pulse me-2"></i> Admissions
            </a>
        </li>
        @endcanany

        @canany(['view_invoices', 'create_invoices', 'edit_invoices'])
        <li class="nav-item mt-1">
            <a href="#" class="nav-link text-white {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <i class="bi bi-receipt me-2"></i> Invoices
            </a>
        </li>
        @endcanany

        @can('view_reports')
        <li class="nav-item mt-1">
            <a href="#" class="nav-link text-white {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
            </a>
        </li>
        @endcan

        @can('manage_catalog')
        <li class="mt-3">
            <span class="text-uppercase text-muted small px-2">Catalog</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.medications.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.medications.*') ? 'active' : '' }}">
                <i class="bi bi-capsule me-2"></i> Medications
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.services.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.services.*') ? 'active' : '' }}">
                <i class="bi bi-heart-pulse me-2"></i> Services
            </a>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('catalog.insurance.index') }}"
               class="nav-link text-white {{ request()->routeIs('catalog.insurance.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check me-2"></i> Insurance Companies
            </a>
        </li>
        @endcan

        @can('manage_users')
        <li class="mt-3">
            <span class="text-uppercase text-muted small px-2">Administration</span>
        </li>
        <li class="nav-item mt-1">
            <a href="#" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear me-2"></i> Users
            </a>
        </li>
        @endcan
    </ul>

    <hr>

    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-5 me-2"></i>
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
                        <i class="bi bi-box-arrow-left me-1"></i> Sign Out
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>
