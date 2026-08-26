@php
    $isProtected = isset($role) && $role->is_protected;
    $selected = $rolePermissions ?? old('permissions', []);
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label" for="name">{{ __('Role Name (English, no spaces)') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $role->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="e.g. lab_technician"
               {{ $isProtected ? 'readonly' : '' }}
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">{{ __('Lowercase letters, numbers and underscores only.') }}</div>
    </div>
</div>

@if($isProtected)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-shield-lock fs-5"></i>
    <div>{{ __('This is a protected system role — its permissions cannot be changed.') }}</div>
</div>
@endif

<div class="d-flex justify-content-end mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllPerms" {{ $isProtected ? 'disabled' : '' }}>
        {{ __('Select All') }}
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="clearAllPerms" {{ $isProtected ? 'disabled' : '' }}>
        {{ __('Clear All') }}
    </button>
</div>

<div class="row g-3">
    @foreach ($groupedPermissions as $groupKey => $permissions)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
                <span class="fw-semibold small">{{ $groupLabels[$groupKey] ?? $groupKey }}</span>
                <div class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input group-toggle" data-group="{{ $groupKey }}"
                           {{ $isProtected ? 'disabled' : '' }}>
                </div>
            </div>
            <div class="card-body py-2" style="max-height: 260px; overflow-y: auto;">
                @foreach ($permissions as $permission)
                <div class="form-check">
                    <input type="checkbox" class="form-check-input perm-checkbox" data-group="{{ $groupKey }}"
                           id="perm-{{ $permission->value }}" name="permissions[]"
                           value="{{ $permission->value }}"
                           {{ in_array($permission->value, $selected) ? 'checked' : '' }}
                           {{ $isProtected ? 'disabled' : '' }}>
                    <label class="form-check-label small" for="perm-{{ $permission->value }}">
                        {{ $permission->label() }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

@unless($isProtected)
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('selectAllPerms')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
        document.querySelectorAll('.group-toggle').forEach(cb => cb.checked = true);
    });
    document.getElementById('clearAllPerms')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.group-toggle').forEach(cb => cb.checked = false);
    });
    document.querySelectorAll('.group-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            document.querySelectorAll('.perm-checkbox[data-group="' + this.dataset.group + '"]')
                .forEach(cb => cb.checked = toggle.checked);
        });
    });
});
</script>
@endunless
