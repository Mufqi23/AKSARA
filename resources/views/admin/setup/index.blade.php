@extends('layouts.app')
@section('title', 'Atur Tahun Ajaran Baru')

@section('content')
@php
    $stepNames = [
        1 => 'Tahun Ajaran',
        2 => 'Jurusan & Rombel',
        3 => 'Mata Pelajaran',
        4 => 'Penugasan Guru',
        5 => 'Data Siswa',
        6 => 'Tinjau & Simpan'
    ];
@endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PANDUAN TAHUN AJARAN BARU</div>
        <h1 class="mb-1">Pengaturan Tahun Ajaran Baru</h1>
        <p class="text-secondary mb-0">Susun kelas, mata pelajaran, guru pengajar, dan siswa langkah demi langkah. Semua perubahan baru akan disimpan setelah Anda memeriksa ringkasan akhir.</p>
    </div>
    <form method="post" action="/admin/setup/restart" data-confirm="Hapus draf pengaturan yang belum disimpan ini dan mulai dari awal?">
        @csrf
        <button class="btn btn-outline-secondary" type="submit">Mulai dari Awal</button>
    </form>
</div>

@if($step === 1)
    <div class="card p-3 mb-4 d-flex flex-row justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="fw-bold text-primary">Ingin Memasukkan Data Sekaligus dari Excel?</div>
            <div class="small text-secondary">Gunakan Berkas Excel Data Sekolah (<code>.xlsx</code>) untuk mengimpor tahun ajaran, jurusan, rombel, guru, mata pelajaran, jadwal, dan siswa sekaligus.</div>
        </div>
        <a href="/dataset" class="btn btn-sm btn-primary flex-shrink-0">Buka Ekspor &amp; Impor Data →</a>
    </div>
@endif

<nav class="setup-steps mb-4" aria-label="Langkah Panduan Setup">
    @foreach($steps as $number => $label)
        @php($translatedLabel = $stepNames[$number] ?? $label)
        @if($number <= $draft['completed'] + 1)
            <a href="/admin/setup?step={{ $number }}" class="setup-step {{ $number === $step ? 'active' : '' }}" @if($number === $step) aria-current="step" @endif>
                <span>{{ $number }}</span>
                {{ $translatedLabel }}
            </a>
        @else
            <span class="setup-step text-secondary">
                <span>{{ $number }}</span>
                {{ $translatedLabel }}
            </span>
        @endif
    @endforeach
</nav>

<div class="card">
    <div class="card-body">
        <h2 class="h5 mb-4">Langkah {{ $step }}: {{ $stepNames[$step] ?? ($steps[$step] ?? 'Pengaturan') }} <span class="visually-hidden">Step {{ $step }}: {{ $steps[$step] ?? '' }}</span></h2>

        @if($step < 6)
            <form method="post" action="/admin/setup/steps/{{ $step }}" enctype="multipart/form-data" data-setup-form>
                @csrf
                <input type="hidden" name="draft_token" value="{{ $draft['token'] }}">
                @include('admin.setup.step-'.$step)

                <div class="d-flex flex-wrap gap-2 border-top pt-4 mt-4">
                    @if($step > 1)
                        <a class="btn btn-outline-secondary" href="/admin/setup?step={{ $step - 1 }}">
                            ← Kembali ke Langkah Sebelumnya
                        </a>
                    @endif
                    <button class="btn btn-primary" name="action" value="continue" type="submit">
                        {{ $step === 5 ? 'Tinjau Seluruh Pengaturan →' : 'Simpan & Lanjutkan →' }}
                    </button>
                </div>
            </form>
        @else
            @include('admin.setup.review')
        @endif
    </div>
</div>

@if($step === 1)
    <details class="card mt-4" @if(request('duplicate')) open @endif>
        <summary class="card-header fw-bold text-primary" style="cursor: pointer;">
            Salin Data dari Semester Sebelumnya <span class="visually-hidden">Duplicate Previous Setup</span>
        </summary>
        <div class="card-body">
            <p class="text-secondary small mb-3">
                Pilih semester sebelumnya untuk menyalin daftar mata pelajaran, penugasan guru, dan rombel kelas ke semester baru dengan cepat.
            </p>
            <form method="post" action="/admin/setup/duplicate" class="row g-3">
                @csrf
                <input type="hidden" name="draft_token" value="{{ $draft['token'] }}">
                <div class="col-md-6">
                    <label for="source_year" class="form-label">Tahun Ajaran Sumber</label>
                    <select id="source_year" name="source[academic_year_id]" class="form-select" required>
                        <option value="">Pilih tahun ajaran sumber</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="source_semester" class="form-label">Semester Sumber</label>
                    <select id="source_semester" name="source[semester]" class="form-select" required>
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                        <option value="3">Semester 3 (Pendek)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="source_program" class="form-label">Jurusan Sumber</label>
                    <select id="source_program" name="source[program_id]" class="form-select" required>
                        <option value="">Pilih jurusan</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->code }} — {{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="source_level" class="form-label">Tingkat Kelas Sumber</label>
                    <select id="source_level" name="source[year_level_id]" class="form-select" required>
                        <option value="">Pilih tingkat kelas</option>
                        @foreach($yearLevels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="copy_schedules" id="copy_schedules" value="1" checked>
                        <label class="form-check-label small" for="copy_schedules">Ikut salin jadwal pelajaran mingguan (bentrok jadwal akan diperiksa otomatis)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="copy_students" id="copy_students" value="1">
                        <label class="form-check-label small" for="copy_students">Ikut salin daftar siswa ke pengaturan baru</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-outline-primary" type="submit">Muat Pengaturan Sebelumnya</button>
                </div>
            </form>
        </div>
    </details>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/academic-setup.js') }}?v=20261108d" defer></script>
@endpush
