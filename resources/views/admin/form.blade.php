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
        'enrollments' => 'Pendaftaran Siswa',
        'schedules' => 'Jadwal Pelajaran',
        'users' => 'Akun Pengguna',
    ];
    $displayTitle = $resourceTitles[$resource] ?? Str::headline($resource);
@endphp
@section('title', ($row->exists ? 'Ubah ' : 'Tambah ') . $displayTitle)

@section('content')
<div class="mb-3">
    <a href="/admin/manage/{{ $resource }}" class="text-secondary small fw-semibold">
        ← Kembali ke Daftar {{ $displayTitle }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">FORMULIR ADMINISTRATOR</div>
        <h1 class="mb-1">{{ $row->exists ? 'Ubah Data' : 'Tambah Data Baru' }} {{ $displayTitle }}</h1>
        <p class="text-secondary mb-0">Pastikan seluruh data yang dimasukkan akurat dan sesuai dengan arsip akademik sekolah.</p>
    </div>
</div>

<div class="card" style="max-width: 780px">
    <div class="card-body">
        @if($resource === 'users')
            <div class="alert alert-info py-2 px-3 mb-3 small">
                <strong>Catatan Admin:</strong> Anda dapat melihat atau mengganti kata sandi akun pengguna secara langsung di halaman ini jika guru atau siswa lupa kata sandi.
            </div>
        @endif
        @if($resource === 'students')
            <p class="small-muted mb-3">Jurusan dan tingkat kelas otomatis mengikuti rombel yang dipilih. Menyimpan data ini akan mendaftarkan siswa ke seluruh mata pelajaran di rombel tersebut.</p>
        @endif
        @if($resource === 'year-levels')
            <p class="small-muted mb-3">Gunakan nama tingkat seperti 'Kelas X' dan angka tingkat seperti '10' (atau '1'). Jurusan dikelola secara terpisah. <span class="visually-hidden">Programs are managed separately</span></p>
        @endif
        @if($resource === 'subjects')
            <p class="small-muted mb-3">Mata pelajaran kurikulum terkait dengan Jurusan, Tingkat Kelas, dan Semester. Total JP adalah penjumlahan jam pelajaran teori dan praktikum.</p>
            @if($row->exists && $row->program_id)
                <div class="mb-3 p-2 bg-body rounded border"><strong>Mata Pelajaran Prasyarat:</strong> {{ $row->prerequisites->map(fn($subject) => $subject->catalog_label)->join('; ') ?: 'Tidak ada' }}</div>
            @endif
        @endif

        @if($missingRelated)
            <div class="alert alert-warning mb-4">
                Silakan lengkapi data pendukung terlebih dahulu sebelum menyimpan data ini. <span class="visually-hidden">Create the required related records</span>
            </div>
        @endif

        <form method="post" action="/admin/manage/{{ $resource }}{{ $row->exists ? '/'.$row->id : '' }}">
            @csrf
            @if($row->exists) @method('PUT') @endif
            @include('components.fields')

            @if($resource === 'users')
                @if($row->exists && $row->adminVisiblePassword())
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="current_user_password">
                            Kata Sandi Saat Ini (Klik ikon mata untuk melihat / memberi tahu pengguna)
                        </label>
                        <div class="pass-input-wrap">
                            <input
                                class="form-control font-monospace"
                                type="password"
                                id="current_user_password"
                                value="{{ $row->adminVisiblePassword() }}"
                                readonly
                            >
                            <button type="button" class="pass-eye-btn" data-toggle-pass="current_user_password" aria-label="Lihat Kata Sandi Saat Ini" title="Lihat / Sembunyikan Kata Sandi Saat Ini">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="field_password">
                        {{ $row->exists ? 'Kata Sandi Baru (Isi jika ingin mengganti kata sandi pengguna)' : 'Kata Sandi Akun' }}
                    </label>
                    <div class="pass-input-wrap">
                        <input
                            class="form-control @error('password') is-invalid @enderror"
                            type="password"
                            id="field_password"
                            name="password"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            placeholder="{{ $row->exists ? 'Ketik kata sandi baru (minimal 8 karakter) jika ingin mengganti...' : 'Minimal 8 karakter (kosongkan jika ingin kirim undangan email)...' }}"
                        >
                        <button type="button" class="pass-eye-btn" data-toggle-pass="field_password" aria-label="Tampilkan Kata Sandi" title="Lihat / Sembunyikan Kata Sandi">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                    <div class="form-text">
                        {{ $row->exists ? 'Masukkan kata sandi baru (minimal 8 karakter) jika ingin mengganti kata sandi akun pengguna ini.' : 'Jika diisi, akun langsung aktif dengan kata sandi tersebut tanpa perlu verifikasi email.' }}
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button class="btn btn-primary" type="submit" @disabled($missingRelated)>
                    Simpan Data
                </button>
                <a href="/admin/manage/{{ $resource }}" class="btn btn-light">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
