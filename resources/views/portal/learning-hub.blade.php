@extends('layouts.app')
@section('title', 'Modul, Materi & Tugas')

@section('content')
@php
    $student = $isStudent;
    $teaching = $isTeacher;
    $selectedClass = $selectedClassId ? $classes->firstWhere('id', $selectedClassId) : null;
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PUSAT PEMBELAJARAN TERPISAH</div>
        <h1 class="mb-1">Modul, Materi &amp; Tugas Pelajaran</h1>
        <p class="text-secondary mb-0">
            {{ $isStudent
                ? 'Akses modul bacaan teks, unduh berkas bahan ajar, dan kerjakan tugas sekolah yang telah dipisahkan rapi per kategori.'
                : ($isTeacher
                    ? 'Kelola modul bacaan, berkas bahan ajar, serta pantau pengumpulan tugas siswa pada seluruh kelas yang Anda ampu.'
                    : 'Pantau distribusi modul pelajaran, berkas materi, dan penugasan siswa di seluruh kelas sekolah.') }}
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/classes" class="btn btn-outline-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
            Lihat Daftar Kelas
        </a>
    </div>
</div>

{{-- 3D Summary Cards for the 3 Separated Categories --}}
<div class="lw-hub-kpi-grid mb-4">
    <button type="button" class="lw-hub-kpi lw-kpi-modul {{ $activeTab === 'lessons' ? 'is-selected' : '' }}" data-lw-jump="lessons">
        <div class="lw-hub-kpi-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
        </div>
        <div class="text-start">
            <div class="lw-hub-kpi-label">1. Modul Bacaan</div>
            <div class="lw-hub-kpi-val">{{ $lessons->count() }} <span>Modul Teks</span></div>
        </div>
    </button>

    <button type="button" class="lw-hub-kpi lw-kpi-materi {{ $activeTab === 'materials' ? 'is-selected' : '' }}" data-lw-jump="materials">
        <div class="lw-hub-kpi-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="m9 15 3 3 3-3"></path></svg>
        </div>
        <div class="text-start">
            <div class="lw-hub-kpi-label">2. Berkas Materi</div>
            <div class="lw-hub-kpi-val">{{ $materials->count() }} <span>Bahan Ajar</span></div>
        </div>
    </button>

    <button type="button" class="lw-hub-kpi lw-kpi-tugas {{ $activeTab === 'assignments' ? 'is-selected' : '' }}" data-lw-jump="assignments">
        <div class="lw-hub-kpi-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
        </div>
        <div class="text-start">
            <div class="lw-hub-kpi-label">3. Tugas &amp; Evaluasi</div>
            <div class="lw-hub-kpi-val">{{ $assignments->count() }} <span>Penugasan</span></div>
        </div>
    </button>
</div>

{{-- Filter Mata Pelajaran & Pencarian Cepat --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="get" action="/learning" class="row g-2 align-items-center">
            <input type="hidden" name="tab" id="lwActiveTabInput" value="{{ $activeTab }}">
            <div class="col-md-5">
                <label for="filter_class_id" class="visually-hidden">Filter Mata Pelajaran</label>
                <select id="filter_class_id" name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Mata Pelajaran ({{ $classes->count() }} Kelas)</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}" @selected($selectedClassId === $cls->id)>
                            {{ $cls->subject->code }} &mdash; {{ $cls->subject->name }} (Rombel {{ $cls->block->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="filter_q" class="visually-hidden">Cari Judul Konten</label>
                <input id="filter_q" type="text" name="q" value="{{ $search }}" maxlength="100" class="form-control form-control-sm" placeholder="Cari judul modul bacaan, berkas materi, atau tugas...">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Terapkan</button>
                @if($selectedClassId || $search !== '')
                    <a href="/learning?tab={{ $activeTab }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($isTeacher && $selectedClass)
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
                <span class="small fw-semibold text-secondary me-1">Tambah ke {{ $selectedClass->subject->code }}:</span>
                <a class="btn btn-sm btn-modul-action" href="/classes/{{ $selectedClass->id }}/content/lessons/create">+ Modul Bacaan</a>
                <a class="btn btn-sm btn-materi-action" href="/classes/{{ $selectedClass->id }}/content/materials/create">+ Berkas Materi</a>
                <a class="btn btn-sm btn-tugas-action" href="/classes/{{ $selectedClass->id }}/content/assignments/create">+ Tugas Baru</a>
            </div>
        @elseif($isTeacher && $classes->isNotEmpty())
            <div class="small text-muted mt-2 pt-2 border-top">
                Tip Guru: Pilih salah satu mata pelajaran pada filter di atas untuk langsung menambahkan modul, berkas materi, atau tugas baru.
            </div>
        @endif
    </div>
</div>

{{-- Separated 3D Tab Workspace --}}
<div class="lw-workspace" data-lw-root data-default-tab="{{ $activeTab }}">
    <div class="mb-3">
        <nav class="learning-3d-tabs" aria-label="Kategori Konten Pembelajaran">
            <a href="#lessons" class="lw-tab-btn {{ $activeTab === 'lessons' ? 'is-active' : '' }}" data-lw-tab="lessons">
                <span class="lw-tab-dot dot-modul"></span>
                <span>Modul Bacaan</span>
                <span class="lw-tab-count">{{ $lessons->count() }}</span>
            </a>
            <a href="#materials" class="lw-tab-btn {{ $activeTab === 'materials' ? 'is-active' : '' }}" data-lw-tab="materials">
                <span class="lw-tab-dot dot-materi"></span>
                <span>Berkas Materi</span>
                <span class="lw-tab-count">{{ $materials->count() }}</span>
            </a>
            <a href="#assignments" class="lw-tab-btn {{ $activeTab === 'assignments' ? 'is-active' : '' }}" data-lw-tab="assignments">
                <span class="lw-tab-dot dot-tugas"></span>
                <span>Tugas &amp; Evaluasi</span>
                <span class="lw-tab-count">{{ $assignments->count() }}</span>
            </a>
        </nav>
    </div>

    <div class="lw-pane {{ $activeTab === 'lessons' ? 'is-active' : '' }}" data-lw-pane="lessons">
        @include('classes.partials.lessons-panel', [
            'lessons' => $lessons,
            'showSubjectBadge' => true,
        ])
    </div>

    <div class="lw-pane {{ $activeTab === 'materials' ? 'is-active' : '' }}" data-lw-pane="materials">
        @include('classes.partials.materials-panel', [
            'materials' => $materials,
            'showSubjectBadge' => true,
        ])
    </div>

    <div class="lw-pane {{ $activeTab === 'assignments' ? 'is-active' : '' }}" data-lw-pane="assignments">
        @include('classes.partials.assignments-panel', [
            'assignments' => $assignments,
            'studentSubmissions' => $studentSubmissions,
            'showSubjectBadge' => true,
        ])
    </div>
</div>
@endsection

