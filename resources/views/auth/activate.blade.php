@extends('layouts.app')
@section('title', 'Aktivasi Akun')

@section('content')
<div class="auth-card">
    <div class="eyebrow mb-2">SELAMAT DATANG</div>
    <h1 class="h3 mb-2 fw-bold text-main">Halo, {{ $user->name }}!</h1>
    <p class="text-secondary mb-4 small">
        Lengkapi proses aktivasi akun untuk <strong>{{ $user->email }}</strong> dengan membuat kata sandi baru (minimal 12 karakter).
        <span class="visually-hidden">Use at least 12 characters</span>
    </p>

    <form method="post" action="/activate/{{ $user->activation_token }}">
        @csrf
        @include('auth.password-fields')

        <button class="btn btn-primary w-100 py-2 fw-bold mt-4" type="submit">
            Aktifkan Akun Saya <span class="visually-hidden">Verify email & activate</span>
        </button>
    </form>
</div>
@endsection
