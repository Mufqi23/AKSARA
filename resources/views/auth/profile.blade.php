@extends('layouts.app')
@section('title', 'Profil Pengguna')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">AKUN & IDENTITAS</div>
        <h1 class="mb-1">Pengaturan Profil Saya</h1>
        <p class="text-secondary mb-0">Kelola informasi pribadi, foto profil, dan keamanan akun Anda.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Data Pribadi</h2>
            </div>
            <div class="card-body">
                @if($user->profile_picture)
                    <div class="text-center mb-4">
                        <img src="/profile/photo" alt="Foto Profil {{ $user->name }}" class="rounded-circle border p-1" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                @endif

                <div class="d-flex align-items-center justify-content-between p-3 bg-body rounded border mb-3">
                    <div>
                        <div class="text-muted small">Alamat Email Terdaftar</div>
                        <div class="fw-semibold text-main">{{ $user->email }}</div>
                    </div>
                    <span class="badge text-bg-primary">{{ match($user->role) { 'admin' => 'Administrator', 'teacher' => 'Guru Pengajar', 'student' => 'Siswa', default => ucfirst($user->role) } }}</span>
                </div>

                <form method="post" action="/profile" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="name">Nama Lengkap</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="profile_picture">Foto Profil (PNG/JPG, Maksimal 2 MB)</label>
                        <input class="form-control" id="profile_picture" type="file" name="profile_picture" accept="image/png,image/jpeg">
                    </div>

                    <button class="btn btn-primary" type="submit">
                        Simpan Perubahan Profil
                    </button>
                </form>

                @if($user->student)
                    <hr class="my-4">
                    <h3 class="h6 fw-bold mb-3">Data Kelas &amp; Rombel Siswa</h3>
                    <div class="p-3 bg-body rounded border">
                        <div class="small text-muted">Nomor Induk Siswa (NIS):</div>
                        <div class="fw-bold mb-2">{{ $user->student->student_number }}</div>
                        <div class="small text-muted">Jurusan &amp; Rombel:</div>
                        @if($user->student->block)
                            <div class="fw-semibold">{{ $user->student->block->program?->name ?? '—' }}</div>
                            <div class="text-secondary small">{{ $user->student->block->yearLevel?->name ?? '—' }} · Rombel {{ $user->student->block->name }}</div>
                        @else
                            <div class="text-secondary small">Belum ditempatkan pada rombel kelas</div>
                        @endif
                    </div>
                    <p class="small-muted mt-2 mb-0">Hubungi bagian tata usaha atau admin sekolah jika ada data kelas yang belum sesuai.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        @if($user->role === 'admin')
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Keamanan & Kata Sandi Administrator</h2>
                </div>
                <div class="card-body">
                    <p class="small-muted">
                        Gunakan minimal 12 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol untuk keamanan akun Administrator. Untuk melihat atau memperbarui kata sandi pengguna lain (Guru/Siswa), buka menu <a href="/admin/manage/users" class="fw-semibold">Akun Pengguna</a>.
                    </p>

                    <form method="post" action="/profile/password">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                            <div class="pass-input-wrap">
                                <input class="form-control" id="current_password" name="current_password" type="password" placeholder="Masukkan kata sandi lama Anda" required>
                                <button type="button" class="pass-eye-btn" data-toggle-pass="current_password" aria-label="Tampilkan Kata Sandi Saat Ini" title="Lihat / Sembunyikan Kata Sandi">
                                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                </button>
                            </div>
                        </div>

                        @include('auth.password-fields')

                        <button class="btn btn-primary mt-2" type="submit">
                            Perbarui Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Autentikasi Dua Faktor (2FA)</h2>
                <span class="badge {{ $user->two_factor_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $user->two_factor_enabled ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="card-body">
                <p class="small text-secondary mb-3">
                    Tingkatkan keamanan akun Anda dengan verifikasi dua faktor melalui kode sandi sekali pakai (OTP) saat proses masuk.
                </p>
                <form method="post" action="/profile/two-factor">
                    @csrf
                    <button class="btn btn-sm {{ $user->two_factor_enabled ? 'btn-outline-danger' : 'btn-outline-primary' }}" type="submit">
                        {{ $user->two_factor_enabled ? 'Nonaktifkan 2FA' : 'Aktifkan 2FA Sekarang' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
