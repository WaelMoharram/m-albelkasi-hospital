@extends('layouts.app')

@section('title', __('Roles Management'))
@section('page_title', __('Roles Management'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Roles') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center">
        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg ms-1"></i> {{ __('New Role') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Role Name') }}</th>
                    <th class="text-center">{{ __('Permissions') }}</th>
                    <th class="text-center">{{ __('Users') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                <tr>
                    <td class="text-muted small">{{ $role->id }}</td>
                    <td>
                        <span class="fw-medium">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                        @if($role->is_protected)
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">
                                <i class="bi bi-shield-lock"></i> {{ __('Protected') }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                            {{ $role->permissions_count }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                            {{ $role->users_count }}
                        </span>
                    </td>
                    <td class="text-start">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>

                        @if(! $role->is_protected && $role->users_count === 0)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Delete role') }} {{ $role->name }}؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No roles found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
