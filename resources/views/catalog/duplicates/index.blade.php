@extends('layouts.app')

@section('title', __('Duplicate Codes'))
@section('page_title', __('Duplicate Codes'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active">{{ __('Duplicate Codes') }}</li>
@endsection

@section('content')
@php
    $totalGroups = $medicationGroups->count() + $serviceGroups->count();
@endphp

@if($totalGroups === 0)
    <div class="alert alert-success">
        <i class="bi bi-check-circle ms-1"></i>
        {{ __('No duplicate codes found — every medication and service has its own unique code.') }}
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle ms-1"></i>
        {{ __('Found :count code(s) shared by more than one item. This usually means the same real-world item was entered twice under slightly different names — review each group below and merge or correct as needed.', ['count' => $totalGroups]) }}
    </div>
@endif

@if($medicationGroups->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <strong>{{ __('Medications') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($medicationGroups as $code => $group)
                            @foreach($group as $i => $medication)
                                <tr class="{{ $i === 0 ? 'border-top border-2 border-warning-subtle' : '' }}">
                                    @if($i === 0)
                                        <td rowspan="{{ $group->count() }}" class="fw-bold align-middle">{{ $code }}</td>
                                    @endif
                                    <td>{{ $medication->name }}</td>
                                    <td>{{ $medication->type === 'local' ? __('Local') : __('Imported') }}</td>
                                    <td>{{ number_format($medication->price, 2) }}</td>
                                    <td>
                                        <a href="{{ route('catalog.medications.edit', $medication) }}" class="btn btn-sm btn-outline-secondary">
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if($serviceGroups->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <strong>{{ __('Services') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviceGroups as $code => $group)
                            @foreach($group as $i => $service)
                                <tr class="{{ $i === 0 ? 'border-top border-2 border-warning-subtle' : '' }}">
                                    @if($i === 0)
                                        <td rowspan="{{ $group->count() }}" class="fw-bold align-middle">{{ $code }}</td>
                                    @endif
                                    <td>{{ $service->name }}</td>
                                    <td>{{ $service->category }}</td>
                                    <td>{{ number_format($service->price, 2) }}</td>
                                    <td>
                                        <a href="{{ route('catalog.services.edit', $service) }}" class="btn btn-sm btn-outline-secondary">
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
