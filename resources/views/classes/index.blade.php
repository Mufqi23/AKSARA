@extends('layouts.app')
@php
    $module = in_array(request('module'), ['learning', 'monitoring']) ? request('module') : 'academic';
    $title = match ($module) {
        'learning' => auth()->user()->role === 'admin' ? 'Pantau Aktivitas Kelas' : 'Materi, Modul & Tugas Pelajaran',
        'monitoring' => 'Rekam Presensi & Nilai Siswa',
        default => auth()->user()->role === 'admin' ? 'Daftar Seluruh Kelas' : 'Kelas Pelajaran Saya',
    };
    $subtitle = match ($module) {
        'learning' => 'Pilih kelas pelajaran untuk meninjau materi ajar, modul pembelajaran, dan tugas siswa.',
        'monitoring' => 'Pilih kelas pelajaran untuk mencatat presensi pertemuan atau mengelola buku nilai.',
        default => 'Daftar mata pelajaran, guru pengajar, dan siswa yang dikelompokkan berdasarkan rombel kelas.',
    };
    $classAnchor = match ($module) { 'learning' => '#learning', 'monitoring' => '#attendance', default => '' };
@endphp
@section('title', $title)
@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">RUANG PEMBELAJARAN</div>
        <h1 class="mb-1">{{ $title }}</h1>
        <p class="text-secondary mb-0">{{ $subtitle }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="/learning">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            Pusat Modul, Materi &amp; Tugas
        </a>
        @if(auth()->user()->role === 'admin')
            <a class="btn btn-primary" href="/admin/manage/teacher-assignments/create">+ Tambah Kelas Baru</a>
        @endif
    </div>
</div>

@forelse($classes->getCollection()->groupBy('block_id') as $group)
    <div class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <span class="badge text-bg-primary">{{ $group->first()->block->program->code }}</span>
            <h2 class="h5 mb-0 fw-bold">
                {{ $group->first()->block->yearLevel->name }} · Rombel {{ $group->first()->block->name }} <span class="visually-hidden">Block {{ $group->first()->block->name }}</span>
            </h2>
            <span class="text-muted small ms-auto">
                {{ $group->first()->block->academicYear->name }} · Semester {{ $group->first()->block->semester }}
            </span>
        </div>

        <div class="row g-3">
            @foreach($group as $classroom)
                <div class="col-md-6 col-xl-4">
                    @include('classes.card')
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-3"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
        <h3 class="h5 fw-bold mb-2">Belum Ada Kelas yang Ditugaskan</h3>
        <p class="small-muted mb-0">
            {{ auth()->user()->role === 'admin' ? 'Mulai dengan membuat struktur tahun ajaran atau tambahkan penugasan kelas secara mandiri.' : 'Administrator sekolah akan mengatur penempatan kelas dan jadwal pelajaran Anda.' }}
        </p>
        @if(auth()->user()->role === 'admin')
            <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                <a class="btn btn-primary" href="/admin/setup">+ Atur Tahun Ajaran Baru</a>
                <a class="btn btn-outline-primary" href="/admin/manage/teacher-assignments/create">Tambah Penugasan Kelas</a>
            </div>
        @endif
    </div>
@endforelse

<div class="mt-4">
    {{ $classes->links() }}
</div>
@endsection
