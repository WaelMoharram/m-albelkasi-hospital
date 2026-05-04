@extends('layouts.app')

@section('title', __('Wards & Rooms'))
@section('page_title', __('Wards & Rooms'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active">{{ __('Wards & Rooms') }}</li>
@endsection

@section('content')
<div class="row g-4">

    {{-- ── Left: Add ward ──────────────────────────────────────────── --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-plus-circle text-primary ms-1"></i>
                    {{ __('Add Ward') }}
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('catalog.wards.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="ward_name">{{ __('Ward Name') }} <span class="text-danger">*</span></label>
                        <input id="ward_name" type="text" name="name"
                               value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="{{ __('e.g. Cardiology') }}"
                               autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Ward') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Right: Wards list ───────────────────────────────────────── --}}
    <div class="col-md-8">
        @forelse ($wards as $ward)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 d-flex align-items-center gap-2">

                {{-- Ward name (inline edit toggle) --}}
                <span class="fw-semibold flex-grow-1" id="ward-label-{{ $ward->id }}">
                    <i class="bi bi-building ms-1 text-secondary"></i>
                    {{ $ward->name }}
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">
                        {{ $ward->rooms_count }} {{ __('rooms') }}
                    </span>
                </span>

                {{-- Edit toggle button --}}
                <button class="btn btn-sm btn-outline-primary"
                        onclick="toggleEdit({{ $ward->id }})" type="button">
                    <i class="bi bi-pencil"></i>
                </button>

                {{-- Delete ward --}}
                <form method="POST" action="{{ route('catalog.wards.destroy', $ward) }}"
                      onsubmit="return confirm('{{ __('Delete this ward and all its rooms?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>

            {{-- Inline edit form (hidden by default) --}}
            <div id="ward-edit-{{ $ward->id }}" class="px-3 pt-2 pb-0 d-none">
                <form method="POST" action="{{ route('catalog.wards.update', $ward) }}" class="d-flex gap-2 mb-2">
                    @csrf @method('PUT')
                    <input type="text" name="name" value="{{ $ward->name }}"
                           class="form-control form-control-sm" required>
                    <button class="btn btn-sm btn-success" style="white-space:nowrap">
                        <i class="bi bi-check-lg"></i> {{ __('Save') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="toggleEdit({{ $ward->id }})">{{ __('Cancel') }}</button>
                </form>
            </div>

            <div class="card-body pt-2">
                {{-- Rooms as badges --}}
                <div class="d-flex flex-wrap gap-2 mb-3" id="rooms-{{ $ward->id }}">
                    @forelse ($ward->rooms as $room)
                    <span class="badge bg-light text-dark border d-flex align-items-center gap-1 px-2 py-2">
                        <i class="bi bi-door-closed text-secondary"></i>
                        {{ $room->name }}
                        <form method="POST"
                              action="{{ route('catalog.wards.rooms.destroy', [$ward, $room]) }}"
                              class="d-inline m-0 p-0"
                              onsubmit="return confirm('{{ __('Remove this room?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn btn-link btn-sm p-0 ms-1 text-danger border-0"
                                    style="line-height:1; font-size:.75rem;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </span>
                    @empty
                    <span class="text-muted small fst-italic">{{ __('No rooms yet.') }}</span>
                    @endforelse
                </div>

                {{-- Add room form --}}
                <form method="POST" action="{{ route('catalog.wards.rooms.store', $ward) }}"
                      class="d-flex gap-2">
                    @csrf
                    <input type="text" name="name"
                           class="form-control form-control-sm"
                           placeholder="{{ __('Room name, e.g. 101') }}"
                           style="max-width: 220px;"
                           required>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg"></i> {{ __('Add Room') }}
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-building fs-1 d-block mb-2 opacity-25"></i>
                {{ __('No wards added yet. Add your first ward on the left.') }}
            </div>
        </div>
        @endforelse
    </div>

</div>

@push('scripts')
<script>
function toggleEdit(wardId) {
    var editDiv   = document.getElementById('ward-edit-' + wardId);
    var labelSpan = document.getElementById('ward-label-' + wardId);
    var hidden    = editDiv.classList.contains('d-none');
    editDiv.classList.toggle('d-none', !hidden);
    labelSpan.classList.toggle('d-none', hidden);
}
</script>
@endpush

@endsection
