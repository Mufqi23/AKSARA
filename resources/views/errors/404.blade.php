@extends('layouts.app')
@section('title', 'Halaman Tidak Ditemukan')

@section('content')
<div class="card text-center py-5">
    <div class="card-body">
        <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px; background: var(--danger-subtle); color: var(--danger);">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        </div>
        <h1 class="h3 fw-bold mb-2">Halaman Tidak Ditemukan (404)</h1>
        <p class="text-secondary mb-4">Halaman yang Anda cari tidak ditemukan atau sudah dipindahkan.</p>
        <a href="/dashboard" class="btn btn-primary">
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection
