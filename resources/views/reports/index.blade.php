@extends('layouts.app')
@php
    $kindTitle = match($kind) {
        'attendance' => 'Laporan Kehadiran Siswa',
        'grades' => 'Laporan Rekap Nilai',
        'students' => 'Daftar Siswa Terdaftar',
        'enrollments' => 'Data Pendaftaran Kelas',
        default => 'Laporan Akademik'
    };
@endphp
@section('title', $kindTitle)

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">LAPORAN SEKOLAH</div>
        <h1 class="mb-1">{{ $kindTitle }}</h1>
        <p class="text-secondary mb-0">Lihat, saring, dan unduh rekap data kehadiran maupun nilai siswa.</p>
    </div>
    @if(config('lms.show_advanced_features'))
        <div class="report-toolbar align-self-center d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Ekspor PDF <span class="visually-hidden">Export PDF</span>
            </a>
            <a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['format' => 'xlsx']) }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line></svg>
                Ekspor Excel
            </a>
        </div>
    @endif
</div>

@if(config('lms.show_advanced_features') && auth()->user()->role !== 'student')
<div class="section-nav mb-4">
    <a href="/reports/attendance" class="{{ $kind === 'attendance' ? 'active' : '' }}">Kehadiran Siswa</a>
    <a href="/reports/grades" class="{{ $kind === 'grades' ? 'active' : '' }}">Rekap Nilai</a>
    <a href="/reports/students" class="{{ $kind === 'students' ? 'active' : '' }}">Daftar Siswa</a>
    <a href="/reports/enrollments" class="{{ $kind === 'enrollments' ? 'active' : '' }}">Pendaftaran</a>
</div>
@endif

<div class="card mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">Saring Laporan</h2>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Kelas Pelajaran</label>
                <select class="form-select" name="class_id">
                    <option value="">Semua kelas</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->label }}</option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->role !== 'student')
                @php($filterOptions = [
                    'academic_year_id' => $classes->mapWithKeys(fn($c) => [$c->block->academic_year_id => $c->block->academicYear->name]),
                    'semester' => collect([1 => 'Semester 1 (Ganjil)', 2 => 'Semester 2 (Genap)', 3 => 'Semester 3 (Pendek)']),
                    'program_id' => $classes->mapWithKeys(fn($c) => [$c->block->program_id => $c->block->program->name]),
                    'year_level_id' => $classes->mapWithKeys(fn($c) => [$c->block->year_level_id => $c->block->yearLevel->name]),
                    'block_id' => $classes->mapWithKeys(fn($c) => [$c->block_id => 'Rombel '.$c->block->name]),
                    'subject_id' => $classes->mapWithKeys(fn($c) => [$c->subject_id => $c->subject->code.' — '.$c->subject->name]),
                    'student_id' => $studentOptions ?? collect(),
                ])
                @if(auth()->user()->role === 'admin')
                    @php($filterOptions['teacher_id'] = $classes->mapWithKeys(fn($c) => [$c->teacher_id => $c->teacher->user?->name ?? 'Guru Belum Ditugaskan']))
                @endif

                @foreach($filterOptions as $field => $options)
                    <div class="col-md-3">
                        <label class="form-label">{{ \App\Services\Catalog::fieldLabelId($field) }} <span class="visually-hidden">{{ \App\Services\Catalog::fieldLabel($field) }}</span></label>
                        <select class="form-select" name="{{ $field }}">
                            <option value="">Semua {{ \App\Services\Catalog::fieldLabelId($field) }}</option>
                            @foreach($options as $key => $label)
                                <option value="{{ $key }}" @selected(request($field) == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            @endif

            @if($kind === 'attendance')
                <div class="col-md-2">
                    <label class="form-label">Status Presensi</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa', 'excused' => 'Izin / Sakit'] as $st => $stLabel)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ $stLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input class="form-control" type="date" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hingga Tanggal</label>
                    <input class="form-control" type="date" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bulan Kalender</label>
                    <input class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}">
                </div>
            @endif

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                <a class="btn btn-light" href="/reports/{{ $kind }}">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

@if($kind === 'attendance')
    @include('reports.partials.attendance-summary')
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ match($header) { 'Student' => 'Siswa', 'Class' => 'Kelas', 'Date' => 'Tanggal', 'Time' => 'Waktu', 'Status' => 'Status', 'Minutes Late' => 'Menit Terlambat', 'Remarks' => 'Keterangan', 'Assessment' => 'Tugas/Kuis', 'Score' => 'Nilai', 'Total' => 'Total', 'Percentage' => 'Persentase', default => $header } }}</th>
                        @endforeach
                        @if($kind === 'attendance' && (auth()->user()->role === 'teacher' || config('lms.show_advanced_features')))
                            <th>Tindakan / Ulasan</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $key => $record)
                        <tr>
                            @foreach($rows[$key] as $cell)
                                <td>{{ match((string)$cell) { 'present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa', 'excused' => 'Izin / Sakit', default => $cell } }}</td>
                            @endforeach
                            @if($kind === 'attendance' && (auth()->user()->role === 'teacher' || config('lms.show_advanced_features')))
                                <td>
                                    @if(auth()->user()->role !== 'student')
                                        @if(auth()->user()->role === 'admin' || !$record->session->date->lt(today()->subDays(7)))
                                            <a href="/attendance/sessions/{{ $record->attendance_session_id }}" class="btn btn-sm btn-outline-primary">Buka Sesi Presensi</a>
                                        @else
                                            <span class="small-muted">Hanya baca</span>
                                        @endif
                                    @elseif($record->excuse)
                                        <span class="badge {{ $record->excuse->status === 'approved' ? 'text-bg-success' : ($record->excuse->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                            Izin {{ $record->excuse->status === 'approved' ? 'Disetujui' : ($record->excuse->status === 'rejected' ? 'Ditolak' : 'Menunggu') }}
                                        </span>
                                        <div class="small text-muted">{{ $record->excuse->review_note }}</div>
                                    @elseif($record->status === 'absent')
                                        <details>
                                            <summary class="small fw-semibold text-primary" style="cursor: pointer;">Ajukan Izin/Sakit</summary>
                                            <form method="post" action="/attendance/{{ $record->id }}/excuse" class="mt-2">
                                                @csrf
                                                <label class="form-label small">Alasan Permohonan Izin</label>
                                                <textarea class="form-control mb-2" name="reason" minlength="10" maxlength="2000" placeholder="Jelaskan alasan izin/sakit..." required></textarea>
                                                <button class="btn btn-sm btn-primary" type="submit">Kirim Pengajuan</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) + ($kind === 'attendance' && (auth()->user()->role === 'teacher' || config('lms.show_advanced_features')) ? 1 : 0) }}" class="text-center text-muted py-4">
                                Tidak ada rekaman data yang cocok dengan kriteria filter di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection
