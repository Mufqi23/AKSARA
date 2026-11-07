@extends('layouts.app')
@section('title', $classroom->label)

@section('content')
@php
    $teaching = auth()->user()->role === 'teacher';
    $lessonsCount = ($content['lessons'] ?? collect())->count();
    $materialsCount = ($content['materials'] ?? collect())->count();
    $assignmentsCount = ($content['assignments'] ?? collect())->count();
@endphp

<section id="overview" class="hero mb-4">
    <span class="visually-hidden">Overview</span>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <div class="eyebrow">
            {{ $classroom->block->program->name }} &middot; Rombel {{ $classroom->block->name }}
        </div>
        <span class="badge badge-soft fs-6">{{ $classroom->subject->units }} JP</span>
    </div>
    <h1 class="h2 mb-2">{{ $classroom->subject->code }} &mdash; {{ $classroom->subject->name }}</h1>
    <div class="d-flex flex-wrap align-items-center gap-3 text-secondary mb-3">
        <div class="d-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Guru: <strong>{{ $classroom->teacher->user?->name ?? 'Guru Belum Ditentukan' }}</strong></span>
        </div>
        <span>&middot;</span>
        <div>{{ $classroom->block->academicYear->name }} &middot; Semester {{ $classroom->block->semester }}</div>
    </div>
    @if($classroom->subject->description)
        <p class="mt-2 mb-0 text-secondary border-top pt-2">{{ $classroom->subject->description }}</p>
    @endif
</section>

@include('components.early-warning-panel', ['ewsClassId' => $classroom->id])

@if($teaching)
    <div class="class-quick-actions d-flex flex-wrap gap-2 mb-4 p-3 rounded" aria-label="Aksi Cepat Guru">
        <a class="btn btn-modul-action" href="/classes/{{ $classroom->id }}/content/lessons/create">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            + Tambah Modul Bacaan <span class="visually-hidden">+ Add Lesson</span>
        </a>
        <a class="btn btn-materi-action" href="/classes/{{ $classroom->id }}/content/materials/create">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            + Unggah Berkas Materi <span class="visually-hidden">+ Add Material</span>
        </a>
        <a class="btn btn-tugas-action" href="/classes/{{ $classroom->id }}/content/assignments/create">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
            + Buat Tugas Baru <span class="visually-hidden">+ Create Assignment</span>
        </a>
        <a class="btn btn-outline-primary" href="#take-attendance" data-lw-jump="monitoring">
            + Catat Kehadiran <span class="visually-hidden">Take Attendance</span>
        </a>
        <a class="btn btn-outline-secondary" href="/classes/{{ $classroom->id }}/gradebook">
            Lihat Buku Nilai <span class="visually-hidden">Enter Grades</span>
        </a>
        <a class="btn btn-outline-secondary" href="/dataset?class_id={{ $classroom->id }}">
            Ekspor / Impor Kelas
        </a>
    </div>
@elseif(auth()->user()->role === 'admin')
    <div class="d-flex justify-content-end mb-3">
        <a class="btn btn-sm btn-outline-primary" href="/dataset?class_id={{ $classroom->id }}">
            Ekspor / Impor Data Kelas Ini &rarr;
        </a>
    </div>
@endif

<div class="lw-workspace" data-lw-root data-has-search="{{ request()->filled('q') ? '1' : '0' }}">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <nav class="learning-3d-tabs" aria-label="Ruang Kerja Kelas Terpisah">
            <a href="#lessons" class="lw-tab-btn is-active" data-lw-tab="lessons">
                <span class="lw-tab-dot dot-modul"></span>
                <span>Modul Bacaan</span>
                <span class="lw-tab-count">{{ $lessonsCount }}</span>
            </a>
            <a href="#materials" class="lw-tab-btn" data-lw-tab="materials">
                <span class="lw-tab-dot dot-materi"></span>
                <span>Berkas Materi</span>
                <span class="lw-tab-count">{{ $materialsCount }}</span>
            </a>
            <a href="#assignments" class="lw-tab-btn" data-lw-tab="assignments">
                <span class="lw-tab-dot dot-tugas"></span>
                <span>Tugas &amp; Evaluasi</span>
                <span class="lw-tab-count">{{ $assignmentsCount }}</span>
            </a>
            @if(isset($content['quizzes']))
                <a href="#quizzes" class="lw-tab-btn" data-lw-tab="quizzes">
                    <span class="lw-tab-dot dot-tugas"></span>
                    <span>Kuis Daring</span>
                    <span class="lw-tab-count">{{ $content['quizzes']->count() }}</span>
                </a>
            @endif
            <a href="#monitoring" class="lw-tab-btn" data-lw-tab="monitoring">
                <span class="lw-tab-dot dot-presensi"></span>
                <span>Presensi &amp; Nilai <span class="visually-hidden">Monitoring</span></span>
            </a>
            @unless($student)
                <a href="#students" class="lw-tab-btn" data-lw-tab="students">
                    <span class="lw-tab-dot dot-siswa"></span>
                    <span>Daftar Siswa</span>
                    <span class="lw-tab-count">{{ $students->count() }}</span>
                </a>
            @endunless
        </nav>

        <form method="get" class="d-flex gap-2 lw-search-inline">
            <input name="q" class="form-control form-control-sm" value="{{ request('q') }}" maxlength="100" placeholder="Cari modul, materi, tugas{{ $student ? '' : ', siswa' }}..." aria-label="Cari di kelas ini">
            <button class="btn btn-sm btn-outline-secondary" type="submit">Cari</button>
            @if(request()->filled('q'))
                <a href="/classes/{{ $classroom->id }}" class="btn btn-sm btn-light">Reset</a>
            @endif
        </form>
    </div>

    @include('classes.partials.content-list')
    @include('classes.partials.attendance-roster')
</div>
@endsection
