@extends('layouts.app')
@section('title', 'Pembaruan Kata Sandi')

@section('content')
<div class="auth-card">
    <div class="eyebrow mb-2">BANTUAN AKUN</div>
    <h1 class="h3 mb-2 fw-bold text-main">Lupa Kata Sandi?</h1>
    <p class="text-secondary mb-3 small">
        Penggantian kata sandi akun guru dan siswa dibantu langsung oleh Admin Sekolah. Silakan hubungi bagian Tata Usaha atau Admin Sekolah untuk mendapatkan bantuan kata sandi baru.
    </p>

    <div class="border-top pt-3 mt-4 text-center">
        <a href="/login" class="small fw-semibold text-primary">← Kembali ke Halaman Masuk</a>
    </div>
</div>
@endsection
