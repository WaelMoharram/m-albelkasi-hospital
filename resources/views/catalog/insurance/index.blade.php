@extends('layouts.app')

@section('title', 'Insurance Companies')
@section('page_title', 'Insurance Companies')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Insurance Companies</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
        <form method="GET" action="{{ route('catalog.insurance.index') }}" class="d-flex gap-2 flex-grow-1">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                class="form-control form-control-sm"
                placeholder="Search by name…"
                style="max-width: 280px;"
            >
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('catalog.insurance.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            @endif
        </form>
        <a href="{{ route('catalog.insurance.create') }}" class="btn btn-sm btn-primary ms-auto">
            <i class="bi bi-plus-lg me-1"></i> Add Company
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Contact Info</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                <tr>
                    <td class="text-muted small">{{ $company->id }}</td>
                    <td>{{ $company->name }}</td>
                    <td class="text-muted small">{{ Str::limit($company->contact_info, 60) ?: '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('catalog.insurance.edit', $company) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('catalog.insurance.destroy', $company) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete this insurance company?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No insurance companies found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($companies->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing {{ $companies->firstItem() }}–{{ $companies->lastItem() }} of {{ $companies->total() }}
        </small>
        {{ $companies->links() }}
    </div>
    @endif
</div>
@endsection
