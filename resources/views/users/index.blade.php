@extends('layouts.app')

@section('title', __('User Management'))
@section('page_title', __('User Management'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Users') }}</li>
@endsection

@php
    $roleColors = [
        'super_admin' => 'danger',
        'admin'       => 'primary',
        'cashier'     => 'success',
        'data_entry'  => 'info',
    ];
@endphp

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
        <form method="GET" action="{{ route('users.index') }}" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="{{ __('Search name or email…') }}"
                   style="max-width:280px;">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> {{ __('Clear') }}
                </a>
            @endif
        </form>
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary me-auto">
            <i class="bi bi-person-plus ms-1"></i> {{ __('New User') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Full Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr class="{{ ! $user->is_active ? 'opacity-50' : '' }}">
                    <td class="text-muted small">{{ $user->id }}</td>

                    <td>
                        <div class="fw-medium">{{ $user->name }}</div>
                        @if($user->id === auth()->id())
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px;">{{ __('You') }}</span>
                        @endif
                    </td>

                    <td class="small">{{ $user->email }}</td>

                    <td>
                        @foreach($user->roles as $role)
                            @php $color = $roleColors[$role->name] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle">
                                {{ ucwords(str_replace('_', ' ', $role->name)) }}
                            </span>
                        @endforeach
                        @if($user->roles->isEmpty())
                            <span class="text-muted small fst-italic">{{ __('No role') }}</span>
                        @endif
                    </td>

                    <td class="text-center">
                        @if($user->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Inactive') }}</span>
                        @endif
                    </td>

                    <td class="text-start">
                        <a href="{{ route('users.edit', $user) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>

                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.toggle-active', $user) }}"
                              class="d-inline"
                              onsubmit="return confirm('{{ $user->is_active ? __('Deactivate') : __('Activate') }} {{ $user->name }}؟')">
                            @csrf
                            <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $user->is_active ? __('Deactivate') : __('Activate') }}">
                                <i class="bi {{ $user->is_active ? 'bi-person-x' : 'bi-person-check' }}"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              class="d-inline"
                              onsubmit="return confirm('حذف نهائي لـ {{ $user->name }}؟')">
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
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No users found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ __('Showing :from–:to of :total', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}</small>
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
