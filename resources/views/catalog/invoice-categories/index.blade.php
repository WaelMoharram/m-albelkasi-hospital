@extends('layouts.app')

@section('title', __('Invoice Categories'))
@section('page_title', __('Invoice Categories'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Invoice Categories') }}</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0 small">{{ __('Define the sections shown in the invoice printout, in order.') }}</p>
    <a href="{{ route('catalog.invoice-categories.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Category') }}
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">{{ __('Order') }}</th>
                    <th style="width:200px;">{{ __('Category Name') }}</th>
                    <th>{{ __('Services') }}</th>
                    <th style="width:120px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr class="align-top">
                    <td class="pt-3"><span class="badge bg-secondary">{{ $cat->sort_order }}</span></td>
                    <td class="fw-semibold pt-3">{{ $cat->name }}</td>
                    <td class="py-2">
                        @forelse($cat->services->sortBy('name') as $svc)
                        <div class="py-1 border-bottom border-light">
                            <a href="{{ route('catalog.services.edit', $svc) }}"
                               class="fw-medium text-decoration-none text-body">
                                {{ $svc->name }}
                            </a>
                            @if($svc->code)
                                <span class="font-monospace text-muted small ms-2">{{ $svc->code }}</span>
                            @endif
                            @if($svc->triggers->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($svc->triggers as $trig)
                                    <a href="{{ route('catalog.services.edit', $trig) }}"
                                       class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-decoration-none"
                                       style="font-weight:500;">
                                        <i class="bi bi-link-45deg"></i> {{ $trig->name }}
                                    </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @empty
                        <span class="text-muted small fst-italic">{{ __('No services assigned.') }}</span>
                        @endforelse
                    </td>
                    <td class="text-end pt-2">
                        <a href="{{ route('catalog.invoice-categories.edit', $cat) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('catalog.invoice-categories.destroy', $cat) }}"
                              class="d-inline"
                              onsubmit="return confirm('{{ __('Delete this category?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        {{ __('No categories yet.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
