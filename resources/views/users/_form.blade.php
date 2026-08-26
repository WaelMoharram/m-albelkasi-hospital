<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $user->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">{{ __('Email address') }} <span class="text-danger">*</span></label>
        <input id="email" type="email" name="email"
               value="{{ old('email', $user->email ?? '') }}"
               class="form-control @error('email') is-invalid @enderror"
               required autocomplete="off">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password">
            {{ __('Password') }}
            @isset($user) <span class="text-muted small">({{ __('leave blank to keep current') }})</span>
            @else <span class="text-danger">*</span>
            @endisset
        </label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password"
               {{ isset($user) ? '' : 'required' }}>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control"
               autocomplete="new-password">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="role">{{ __('Role') }} <span class="text-danger">*</span></label>
        <select id="role" name="role"
                class="form-select @error('role') is-invalid @enderror" required>
            <option value="">— {{ __('Select —') }}</option>
            @foreach ($roles as $roleModel)
                <option value="{{ $roleModel->name }}"
                    {{ old('role', isset($user) ? ($user->roles->first()?->name ?? '') : '') === $roleModel->name ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $roleModel->name)) }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

</div>

@php $selectedPermissions = $userDirectPermissions ?? old('permissions', []); @endphp
<div class="mt-4">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#extraPermissions">
        <i class="bi bi-plus-circle ms-1"></i> {{ __('Additional Permissions') }}
        @if(count($selectedPermissions)) <span class="badge bg-primary ms-1">{{ count($selectedPermissions) }}</span> @endif
    </button>
    <div class="form-text mt-1">{{ __('Optional — grant this specific user extra permissions on top of their role.') }}</div>

    <div class="collapse {{ count($selectedPermissions) ? 'show' : '' }} mt-3" id="extraPermissions">
        <div class="row g-3">
            @foreach ($groupedPermissions as $groupKey => $permissions)
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light py-2">
                        <span class="fw-semibold small">{{ $groupLabels[$groupKey] ?? $groupKey }}</span>
                    </div>
                    <div class="card-body py-2" style="max-height: 220px; overflow-y: auto;">
                        @foreach ($permissions as $permission)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   id="extra-perm-{{ $permission->value }}" name="permissions[]"
                                   value="{{ $permission->value }}"
                                   {{ in_array($permission->value, $selectedPermissions) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="extra-perm-{{ $permission->value }}">
                                {{ $permission->label() }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
