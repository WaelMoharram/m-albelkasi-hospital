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
            @foreach ($roles as $roleEnum)
                <option value="{{ $roleEnum->value }}"
                    {{ old('role', isset($user) ? ($user->roles->first()?->name ?? '') : '') === $roleEnum->value ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $roleEnum->value)) }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

</div>
