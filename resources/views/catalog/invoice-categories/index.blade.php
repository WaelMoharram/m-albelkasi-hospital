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
                    <th>{{ __('Category Name') }}</th>
                    <th>{{ __('Services') }}</th>
                    <th style="width:120px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td><span class="badge bg-secondary">{{ $cat->sort_order }}</span></td>
                    <td class="fw-semibold">{{ $cat->name }}</td>
                    <td>
                        <span class="text-muted small">{{ $cat->services_count ?? $cat->services()->count() }} {{ __('services') }}</span>
                    </td>
                    <td class="text-end">
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
