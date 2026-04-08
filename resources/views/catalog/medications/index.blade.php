@extends('layouts.app')

@section('title', __('Medications'))
@section('page_title', __('Medications'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active">{{ __('Medications') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
        <form method="GET" action="{{ route('catalog.medications.index') }}" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="{{ __('Search by name or unit…') }}"
                   style="max-width: 280px;">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('catalog.medications.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> {{ __('Clear') }}
                </a>
            @endif
        </form>
        <a href="{{ route('catalog.medications.create') }}" class="btn btn-sm btn-primary me-auto">
            <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Medication') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Full Name') }}</th>
                    <th>{{ __('Unit') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
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
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Local') }}</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Imported') }}</span>
                        @endif
                    </td>
                    <td class="text-start">
                        <a href="{{ route('catalog.medications.edit', $medication) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('catalog.medications.destroy', $medication) }}"
                              class="d-inline"
                              onsubmit="return confirm('{{ __('Delete this medication?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No medications found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($medications->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
            {{ __('Showing :from–:to of :total', ['from' => $medications->firstItem(), 'to' => $medications->lastItem(), 'total' => $medications->total()]) }}
        </small>
        {{ $medications->links() }}
    </div>
    @endif
</div>
@endsection
