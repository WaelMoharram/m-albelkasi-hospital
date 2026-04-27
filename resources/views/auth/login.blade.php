@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-hospital fs-1 text-primary"></i>
            <h4 class="mt-2 mb-0 fw-bold">{{ config('app.name', 'المستشفى') }}</h4>
            <p class="text-muted small">نظام فوترة التأمين</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">البريد الإلكتروني</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="example@hospital.com"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">تذكرني</label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right ms-1"></i> تسجيل الدخول
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
