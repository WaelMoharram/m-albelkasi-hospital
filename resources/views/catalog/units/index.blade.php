@extends('layouts.app')

@section('title', __('Units'))
@section('page_title', __('Units'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active">{{ __('Units') }}</li>
@endsection

@section('content')
<div class="row g-4">

    {{-- Add form --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-plus-circle ms-2 text-primary"></i>
                    {{ __('Add Unit') }}
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('catalog.units.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">{{ __('Unit Name') }} <span class="text-danger">*</span></label>
                        <input id="name" type="text" name="name"
                               value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="{{ __('e.g.') }} حبة، قارورة، أمبول"
                               autofocus required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg ms-1"></i> {{ __('Add') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-list-ul ms-2 text-secondary"></i>
                    {{ __('All Units') }} <span class="badge bg-secondary ms-1">{{ $units->count() }}</span>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Unit Name') }}</th>
                            <th class="text-start">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                        <tr>
                            <td class="text-muted small">{{ $unit->id }}</td>
                            <td id="unit-name-{{ $unit->id }}">{{ $unit->name }}</td>
                            <td class="text-start">
                                {{-- Inline edit form --}}
                                <form method="POST"
                                      action="{{ route('catalog.units.update', $unit) }}"
                                      class="d-inline align-items-center gap-1 unit-edit-form d-none"
                                      id="edit-form-{{ $unit->id }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $unit->name }}"
                                           class="form-control form-control-sm d-inline-block mb-1"
                                           style="max-width:160px;" required>
                                    <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary cancel-edit"
                                            data-id="{{ $unit->id }}">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>

                                <span class="unit-actions" id="actions-{{ $unit->id }}">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary edit-btn"
                                            data-id="{{ $unit->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST"
                                          action="{{ route('catalog.units.destroy', $unit) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('Delete this unit?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No units yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        document.getElementById('unit-name-' + id).classList.add('d-none');
        document.getElementById('actions-' + id).classList.add('d-none');
        document.getElementById('edit-form-' + id).classList.remove('d-none');
        document.getElementById('edit-form-' + id).classList.add('d-flex');
    });
});
document.querySelectorAll('.cancel-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        document.getElementById('unit-name-' + id).classList.remove('d-none');
        document.getElementById('actions-' + id).classList.remove('d-none');
        document.getElementById('edit-form-' + id).classList.add('d-none');
        document.getElementById('edit-form-' + id).classList.remove('d-flex');
    });
});
</script>
@endsection
