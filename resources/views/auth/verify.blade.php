@extends('layouts.app')
@section('title', 'Verifikasi Email')

@section('content')
<div class="auth-card text-center">
    <div class="stat-icon mx-auto mb-3" style="width: 50px; height: 50px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
    </div>
    <h1 class="h4 mb-2 fw-bold text-main">Verifikasi Alamat Email Anda</h1>
    <p class="text-secondary mb-4 small">
        Buka tautan verifikasi yang telah kami kirimkan ke email Anda untuk mulai menggunakan portal sekolah.
    </p>

    <form method="post" action="/email/verification-notification">
        @csrf
        <button class="btn btn-outline-primary w-100 py-2" type="submit">
            Kirim Ulang Tautan Verifikasi
        </button>
    </form>
</div>
@endsection
