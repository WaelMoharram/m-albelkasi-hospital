@extends('layouts.app')

@section('title', 'Medications')
@section('page_title', 'Medications')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catalog</li>
    <li class="breadcrumb-item active">Medications</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
        <form method="GET" action="{{ route('catalog.medications.index') }}" class="d-flex gap-2 flex-grow-1">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                class="form-control form-control-sm"
                placeholder="Search by name or unit…"
                style="max-width: 280px;"
            >
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('catalog.medications.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            @endif
        </form>
        <a href="{{ route('catalog.medications.create') }}" class="btn btn-sm btn-primary ms-auto">
            <i class="bi bi-plus-lg me-1"></i> Add Medication
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Type</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($medications as $medication)
                <tr>
                    <td class="text-muted small">{{ $medication->id }}</td>
                    <td>{{ $medication->name }}</td>
                    <td>{{ $medication->unit }}</td>
                    <td>{{ number_format($medication->price, 2) }}</td>
                    <td>
                        @if ($medication->type === 'local')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Local</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Imported</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('catalog.medications.edit', $medication) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('catalog.medications.destroy', $medication) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete this medication?')">
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
                    <td colspan="6" class="text-center text-muted py-4">No medications found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($medications->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing {{ $medications->firstItem() }}–{{ $medications->lastItem() }} of {{ $medications->total() }}
        </small>
        {{ $medications->links() }}
    </div>
    @endif
</div>
@endsection
