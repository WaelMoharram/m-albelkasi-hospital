@extends('layouts.app')

@section('title', __('Services'))
@section('page_title', __('Services'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active">{{ __('Services') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3 flex-wrap">
        <form method="GET" action="{{ route('catalog.services.index') }}" class="d-flex gap-2 flex-grow-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="{{ __('Search by name…') }}"
                   style="max-width: 240px;">

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily'), [])) }}"
                   class="btn btn-sm {{ !request('category') && !request('is_daily') ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily'), ['category' => 'supplies'])) }}"
                   class="btn btn-sm {{ request('category') === 'supplies' ? 'btn-warning' : 'btn-outline-warning' }}">
                    {{ __('Supplies') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily'), ['category' => 'lab'])) }}"
                   class="btn btn-sm {{ request('category') === 'lab' ? 'btn-info text-white' : 'btn-outline-info' }}">
                    {{ __('Lab') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily'), ['category' => 'radiology'])) }}"
                   class="btn btn-sm {{ request('category') === 'radiology' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    {{ __('Radiology') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily','is_once'), ['category' => 'other'])) }}"
                   class="btn btn-sm {{ request('category') === 'other' ? 'btn-dark' : 'btn-outline-dark' }}">
                    {{ __('Other') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily','is_once'), ['is_daily' => '1'])) }}"
                   class="btn btn-sm {{ request('is_daily') === '1' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-arrow-repeat"></i> {{ __('Auto-daily') }}
                </a>
                <a href="{{ route('catalog.services.index', array_merge(request()->except('category','is_daily','is_once'), ['is_once' => '1'])) }}"
                   class="btn btn-sm {{ request('is_once') === '1' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="bi bi-1-circle"></i> {{ __('Once') }}
                </a>
            </div>

            @if(request('search') || request('category') || request('is_daily') || request('is_once'))
                <a href="{{ route('catalog.services.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> {{ __('Clear') }}
                </a>
            @endif
        </form>
        <a href="{{ route('catalog.services.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Service') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Full Name') }}</th>
                    <th>{{ __('Item Code') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Linked Services') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                <tr>
                    <td class="text-muted small">{{ $service->id }}</td>
                    <td>{{ $service->name }}</td>
                    <td class="text-muted small">{{ $service->code ?? '—' }}</td>
                    <td>{{ number_format($service->price, 2) }}</td>
                    <td>
                        @php
                            $catMap = [
                                'supplies'  => ['bg-warning-subtle text-warning border-warning-subtle',      __('Supplies')],
                                'lab'       => ['bg-info-subtle text-info border-info-subtle',                __('Lab')],
                                'radiology' => ['bg-secondary-subtle text-secondary border-secondary-subtle', __('Radiology')],
                                'other'     => ['bg-dark-subtle text-dark border-dark-subtle',                __('Other')],
                            ];
                            [$cls, $label] = $catMap[$service->category] ?? ['bg-light text-muted border-secondary-subtle', $service->category];
                        @endphp
                        <span class="badge {{ $cls }} border">{{ $label }}</span>
                        @if($service->is_daily)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">
                                <i class="bi bi-arrow-repeat"></i> {{ __('Auto-daily') }}
                            </span>
                        @endif
                        @if($service->is_once)
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                                <i class="bi bi-1-circle"></i> {{ __('Once') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @forelse ($service->triggers as $trigger)
                            <span class="badge bg-light text-dark border me-1">{{ $trigger->name }}</span>
                        @empty
                            <span class="text-muted small">—</span>
                        @endforelse
                    </td>
                    <td class="text-start">
                        <a href="{{ route('catalog.services.edit', $service) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('catalog.services.destroy', $service) }}"
                              class="d-inline"
                              onsubmit="return confirm('{{ __('Delete this service?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('No services found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($services->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
            {{ __('Showing :from–:to of :total', ['from' => $services->firstItem(), 'to' => $services->lastItem(), 'total' => $services->total()]) }}
        </small>
        {{ $services->links() }}
    </div>
    @endif
</div>
@endsection
