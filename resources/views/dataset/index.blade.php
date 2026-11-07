@extends('layouts.app')
@section('title', 'Ekspor & Impor Dataset Sekolah')

@section('content')
<section class="hero mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <span class="dsh-chip">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Kelola Data Sekolah via Excel &amp; CSV
        </span>
        <span class="badge badge-soft">
            {{ $user->role === 'admin' ? 'Akses Penuh Administrator Sekolah' : 'Akses Kelas yang Anda Ajar' }}
        </span>
    </div>
    <h1 class="h2 mb-2">Pusat Ekspor &amp; Impor Dataset Sekolah</h1>
    <p class="text-secondary mb-0">
        Unduh atau masukkan data siswa, guru, rombel, jadwal pelajaran, modul bacaan, tugas, nilai, hingga kehadiran harian dengan praktis menggunakan berkas Excel (<code>.xlsx</code>), <code>.csv</code>, atau <code>.pdf</code>.
    </p>
</section>

@if(is_array($activePreview) && !empty($activePreview['module']))
    <div class="dsh-preview-banner mb-4" role="region" aria-label="Pratinjau Impor Aktif">
        <div>
            <strong>Terdapat pratinjau berkas yang belum dikonfirmasi:</strong>
            <span class="ms-1">{{ $activePreview['filename'] ?? 'Berkas Dataset' }} ({{ $activePreview['total_rows'] ?? 0 }} baris dibaca)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="/dataset/preview" class="btn btn-sm btn-primary">Lanjutkan Tinjauan →</a>
            <form method="post" action="/dataset/cancel" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">Batalkan</button>
            </form>
        </div>
    </div>
@endif

<div class="dsh-kpi-grid mb-4" aria-label="Ringkasan Data Basis Data Aktif">
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">{{ $selectedClass ? 'Siswa di Kelas Terpilih' : 'Siswa Tercatat' }}</div>
            <div class="dsh-kpi-val">{{ number_format($stats['students']) }}</div>
        </div>
        <span class="dsh-kpi-icon is-blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">{{ $user->role === 'admin' && !$selectedClass ? 'Guru Pengajar' : 'Rombel Terhubung' }}</div>
            <div class="dsh-kpi-val">{{ number_format($user->role === 'admin' && !$selectedClass ? $stats['teachers'] : $stats['blocks']) }}</div>
        </div>
        <span class="dsh-kpi-icon is-amber">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Kelas Mata Pelajaran</div>
            <div class="dsh-kpi-val">{{ number_format($stats['classes']) }}</div>
        </div>
        <span class="dsh-kpi-icon is-sage">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
        </span>
    </div>
    <div class="dsh-kpi-card">
        <div>
            <div class="dsh-kpi-label">Catatan Nilai &amp; Kehadiran</div>
            <div class="dsh-kpi-val">{{ number_format($stats['records']) }}</div>
        </div>
        <span class="dsh-kpi-icon is-plum">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
        </span>
    </div>
</div>

@if($user->role === 'admin')
    @include('dataset.partials.master-bundle-card')
@endif

<section class="card p-3 mb-4" aria-label="Filter Cakupan Kelas Kegiatan Belajar">
    <form method="get" action="/dataset" class="row g-2 align-items-end">
        <div class="col-md-8 col-lg-9">
            <label for="dsh_class_id" class="form-label small fw-semibold mb-1">
                Pilih Kelas Mata Pelajaran (Opsional — memudahkan unduh templat daftar siswa otomatis untuk Nilai &amp; Kehadiran)
            </label>
            <select id="dsh_class_id" name="class_id" class="form-select">
                <option value="">{{ $user->role === 'admin' ? 'Seluruh Kelas di Sekolah' : 'Semua Kelas yang Saya Ajar' }}</option>
                @foreach($classes as $cls)
                    <option value="{{ $cls->id }}" @selected($selectedClassId === $cls->id)>
                        {{ $cls->label }} ({{ $cls->block->academicYear->name }} · Sem {{ $cls->block->semester }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Terapkan Kelas</button>
            @if($selectedClassId)
                <a href="/dataset" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>
</section>

@if(!empty($classroomModules))
    <section class="mb-5" aria-label="Dataset Kegiatan Belajar Mengajar">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <div class="eyebrow">DATA KELAS &amp; PELAJARAN</div>
                <h2 class="h4 mb-0">Data Kegiatan Belajar, Tugas, Nilai &amp; Kehadiran</h2>
            </div>
            @if($selectedClass)
                <span class="badge text-bg-primary">Kelas Aktif: {{ $selectedClass->label }}</span>
            @endif
        </div>
        @include('dataset.partials.module-grid', ['items' => $classroomModules, 'isClassroomGroup' => true])
    </section>
@endif

@if($user->role === 'admin' && !empty($masterModules))
    <section class="mb-5" aria-label="Dataset Induk dan Tata Usaha Sekolah">
        <div class="mb-3">
            <div class="eyebrow">DATA UTAMA SEKOLAH</div>
            <h2 class="h4 mb-0">Data Induk Sekolah, Guru, Siswa, Mata Pelajaran &amp; Jadwal</h2>
        </div>
        @include('dataset.partials.module-grid', ['items' => $masterModules, 'isClassroomGroup' => false])
    </section>
@endif

@include('dataset.partials.audit-table')
@endsection
