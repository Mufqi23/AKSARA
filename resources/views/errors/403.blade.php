@extends('layouts.app')
@section('title', 'Akses Ditolak')

@section('content')
<div class="card text-center py-5">
    <div class="card-body">
        <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px; background: var(--warning-subtle); color: var(--warning);">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        </div>
        <h1 class="h3 fw-bold mb-2">Akses Dibatasi (403)</h1>
        <p class="text-secondary mb-4">Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <a href="/dashboard" class="btn btn-primary">
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection
