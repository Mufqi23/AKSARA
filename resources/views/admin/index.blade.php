@extends('layouts.app')
@php
    $resourceTitles = [
        'academic-years' => 'Tahun Ajaran',
        'programs' => 'Jurusan',
        'year-levels' => 'Tingkat Kelas',
        'blocks' => 'Rombel & Kelas',
        'subjects' => 'Mata Pelajaran',
        'students' => 'Data Siswa',
        'teachers' => 'Data Guru',
        'teacher-assignments' => 'Penugasan Guru & Kelas',
        'enrollments' => 'Pendaftaran Siswa ke Kelas',
        'schedules' => 'Jadwal Pelajaran',
        'users' => 'Manajemen Akun Pengguna',
    ];
    $displayTitle = $resourceTitles[$resource] ?? Str::headline($resource);
    $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $semesterNames = [1 => 'Semester 1 (Ganjil)', 2 => 'Semester 2 (Genap)', 3 => 'Semester 3 (Pendek)'];
@endphp
@section('title', $displayTitle)

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">DATA SEKOLAH</div>
        <h1 class="mb-1">{{ $displayTitle }}</h1>
        <p class="text-secondary mb-0">Kelola daftar {{ strtolower($displayTitle) }} pada portal akademik sekolah.</p>
    </div>
    <a href="/admin/manage/{{ $resource }}/create" class="btn btn-primary align-self-center">
        + Tambah {{ $displayTitle }} Baru
    </a>
</div>

@if(in_array($resource, ['teacher-assignments', 'enrollments', 'schedules']))
    <div class="section-nav mb-4">
        <a href="/admin/manage/teacher-assignments" class="{{ $resource === 'teacher-assignments' ? 'active' : '' }}">Penugasan Guru &amp; Kelas</a>
        <a href="/admin/manage/enrollments" class="{{ $resource === 'enrollments' ? 'active' : '' }}">Pendaftaran Siswa</a>
        <a href="/admin/manage/schedules" class="{{ $resource === 'schedules' ? 'active' : '' }}">Jadwal Pelajaran Mingguan</a>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form class="row g-2 align-items-end mb-4" method="get">
            <div class="col-md-4">
                <label class="form-label small mb-1">Pencarian Cepat</label>
                <input class="form-control" name="q" value="{{ request('q') }}" aria-label="Cari data" placeholder="Ketik untuk mencari {{ preg_replace('/^data\s+/i', '', strtolower($displayTitle)) }}...">
            </div>
            @foreach($fields as $field => $type)
                @if(!in_array($type, ['text', 'email', 'date', 'time', 'textarea', 'number']))
                    <div class="col-md-3">
                        <label class="form-label small mb-1">{{ \App\Services\Catalog::fieldLabelId($field, $resource) }}</label>
                        <select class="form-select" name="{{ $field }}" aria-label="Filter {{ $field }}">
                            <option value="">Semua {{ \App\Services\Catalog::fieldLabelId($field, $resource) }}</option>
                            @foreach($fieldChoices[$field] as $key => $label)
                                <option value="{{ $key }}" @selected((string)request($field) === (string)$key)>
                                    @if($field === 'day' && isset($dayNames[(int)$key]))
                                        {{ $dayNames[(int)$key] }}
                                    @elseif($field === 'semester' && isset($semesterNames[(int)$key]))
                                        {{ $semesterNames[(int)$key] }}
                                    @else
                                        {{ match((string)$label) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Ditangguhkan', 'student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Administrator', default => $label } }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @endforeach
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">Saring</button>
            </div>
            <div class="col-auto">
                <a class="btn btn-light" href="/admin/manage/{{ $resource }}">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table{{ $resource === 'subjects' ? ' curriculum-table' : '' }}">
                <thead>
                    <tr>
                        @foreach($fields as $field => $type)
                            <th>{{ \App\Services\Catalog::fieldLabelId($field, $resource) }} <span class="visually-hidden">{{ \App\Services\Catalog::fieldLabel($field, $resource) }}</span></th>
                        @endforeach
                        @if($resource === 'subjects')
                            <th>Prasyarat <span class="visually-hidden">Prerequisites</span></th>
                        @endif
                        @if($resource === 'users')
                            <th>Kata Sandi</th>
                        @endif
                        <th class="text-end">Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($fields as $field => $type)
                                <td>
                                    @php
                                        $options = $fieldChoices[$field];
                                        $raw = $row->$field instanceof \Carbon\CarbonInterface
                                            ? $row->$field->format('Y-m-d')
                                            : ($options[$row->$field] ?? Str::limit((string)$row->$field, 90));
                                    @endphp
                                    @if($field === 'day' && isset($dayNames[(int)$row->$field]))
                                        {{ $dayNames[(int)$row->$field] }}
                                    @elseif($field === 'semester' && isset($semesterNames[(int)$row->$field]))
                                        {{ $semesterNames[(int)$row->$field] }}
                                    @else
                                        {{ match((string)$raw) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Ditangguhkan', 'student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Administrator', 'draft' => 'Draf', 'published' => 'Terbit', default => $raw } }}
                                    @endif
                                </td>
                            @endforeach
                            @if($resource === 'subjects')
                                <td>{{ $row->prerequisites->pluck('code')->join('; ') ?: '—' }}</td>
                            @endif
                            @if($resource === 'users')
                                <td>
                                    @php($visiblePass = $row->adminVisiblePassword())
                                    @if($visiblePass)
                                        <div class="pass-input-wrap" style="max-width: 195px;">
                                            <input type="password" id="tbl_pass_{{ $row->id }}" class="form-control form-control-sm font-monospace" value="{{ $visiblePass }}" readonly aria-label="Kata sandi {{ $row->name }}">
                                            <button type="button" class="pass-eye-btn" data-toggle-pass="tbl_pass_{{ $row->id }}" aria-label="Lihat Kata Sandi" title="Lihat / Sembunyikan Kata Sandi untuk diberitahukan ke pengguna">
                                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted small">Belum diatur (Klik Ubah Sandi)</span>
                                    @endif
                                </td>
                            @endif
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    @if(!($resource === 'users' && $row->role === 'admin'))
                                        <a class="btn btn-sm btn-outline-secondary" href="/admin/manage/{{ $resource }}/{{ $row->id }}/edit">
                                            Edit
                                        </a>
                                        @if($resource === 'users')
                                            <a class="btn btn-sm btn-outline-primary" href="/admin/manage/users/{{ $row->id }}/edit" data-focus-password="true" title="Perbarui kata sandi jika pengguna lupa sandi">
                                                Ubah Sandi
                                            </a>
                                        @endif
                                        <form method="post" action="/admin/manage/{{ $resource }}/{{ $row->id }}" data-confirm="Hapus data ini? Catatan akademik yang sudah ada dapat membatasi penghapusan.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                        </form>
                                        @if($resource === 'users' && !$row->email_verified_at && $row->status === 'inactive')
                                            <form method="post" action="/admin/users/{{ $row->id }}/invite">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Kirim Undangan</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($fields) + (in_array($resource, ['subjects', 'users']) ? 2 : 1) }}" class="text-center text-muted py-4">
                                Belum ada data yang sesuai dengan pencarian Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
