@extends('layouts.app')
@section('title', 'Pratinjau Validasi Impor Dataset')

@section('content')
@php
    $hasErrors = !empty($preview['errors']);
@endphp

<section class="hero mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <span class="dsh-chip">Tahap 1 dari 2 · Pemeriksaan Data Sebelum Disimpan</span>
        <span class="badge badge-soft">{{ strtoupper($preview['format'] ?? 'XLSX') }} · {{ $preview['generated_at'] ?? '' }}</span>
    </div>
    <h1 class="h2 mb-2">Pratinjau Impor: {{ $moduleTitle }}</h1>
    <p class="text-secondary mb-0">
        Berkas <strong>{{ $preview['filename'] ?? '-' }}</strong> telah diperiksa oleh sistem AKSARA. Belum ada data yang diubah sebelum Anda menekan tombol konfirmasi simpan.
    </p>
</section>

<div class="dsh-kpi-grid mb-4">
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Total Baris Dibaca</div>
            <div class="dsh-kpi-val">{{ number_format($preview['total_rows'] ?? 0) }}</div>
        </div>
        <span class="badge badge-soft">Baris</span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Siap Ditambahkan Baru</div>
            <div class="dsh-kpi-val text-success">{{ number_format($preview['created'] ?? 0) }}</div>
        </div>
        <span class="badge text-bg-success">Baru</span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Data Lama Diperbarui</div>
            <div class="dsh-kpi-val text-primary">{{ number_format($preview['updated'] ?? 0) }}</div>
        </div>
        <span class="badge text-bg-primary">Diperbarui</span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Perlu Diperbaiki</div>
            <div class="dsh-kpi-val {{ $hasErrors ? 'text-danger' : 'text-success' }}">{{ count($preview['errors'] ?? []) }}</div>
        </div>
        <span class="badge {{ $hasErrors ? 'text-bg-danger' : 'text-bg-success' }}">{{ $hasErrors ? 'Perlu Perbaikan' : 'Siap Disimpan' }}</span>
    </div>
</div>

@if($hasErrors)
    <section class="alert alert-danger mb-4" role="alert">
        <h2 class="h6 fw-bold mb-2">Masih Ada Data yang Perlu Diperbaiki (Belum Ada Perubahan Disimpan)</h2>
        <p class="small mb-2">Perbaiki baris berikut pada berkas Excel/CSV Anda, lalu unggah kembali di halaman Ekspor &amp; Impor Data:</p>
        <ul class="small mb-0 ps-3">
            @foreach(array_slice($preview['errors'], 0, 30) as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        @if(count($preview['errors']) > 30)
            <div class="small mt-2 fst-italic">...dan {{ count($preview['errors']) - 30 }} baris lainnya yang perlu diperbaiki.</div>
        @endif
    </section>
@else
    <section class="card p-4 mb-4 border-success" aria-label="Konfirmasi Penyimpanan Data">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="h5 text-success mb-1">Seluruh Baris Tervalidasi dan Siap Disimpan</h2>
                <p class="text-secondary small mb-0">
                    Menekan tombol konfirmasi akan menyimpan seluruh data secara utuh dan aman ke dalam sistem sekolah.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="/dataset/commit" class="m-0">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] ?? '' }}">
                    <button type="submit" class="btn btn-primary">
                        Konfirmasi &amp; Simpan ke Basis Data →
                    </button>
                </form>
                <form method="post" action="/dataset/cancel" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Batalkan</button>
                </form>
            </div>
        </div>
    </section>
@endif

@if(!empty($preview['sheet_stats']))
    <section class="card mb-4">
        <div class="card-header">
            <h2 class="h6 mb-0">Ringkasan per Lembar Kerja</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Lembar / Modul</th>
                        <th>Baris Dibaca</th>
                        <th>Data Baru</th>
                        <th>Diperbarui</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['sheet_stats'] as $st)
                        <tr>
                            <td class="fw-semibold">{{ $st['sheet'] }}</td>
                            <td>{{ $st['rows'] }}</td>
                            <td class="text-success fw-semibold">{{ $st['created'] }}</td>
                            <td class="text-primary fw-semibold">{{ $st['updated'] }}</td>
                            <td>
                                @if(($st['errors'] ?? 0) > 0)
                                    <span class="badge text-bg-danger">{{ $st['errors'] }} Perlu Perbaikan</span>
                                @elseif(($st['rows'] ?? 0) === 0)
                                    <span class="badge badge-soft">Kosong (Dilewati)</span>
                                @else
                                    <span class="badge text-bg-success">Siap</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if(!empty($preview['sample_rows']))
    <section class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Cuplikan Baris Data yang Dibaca</h2>
            <span class="small text-secondary">Menampilkan maks. {{ count($preview['sample_rows']) }} baris pertama</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lembar</th>
                        <th>No. Baris</th>
                        <th>Ringkasan Isi Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['sample_rows'] as $sr)
                        <tr>
                            <td class="small fw-semibold">{{ $sr['sheet'] }}</td>
                            <td class="small text-secondary">#{{ $sr['line'] }}</td>
                            <td class="small">{{ $sr['summary'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<div class="d-flex justify-content-between">
    <a href="{{ !empty($preview['class_id']) ? '/dataset?class_id='.(int)$preview['class_id'] : '/dataset' }}" class="btn btn-outline-secondary">
        ← Kembali ke Pusat Dataset
    </a>
</div>
@endsection

