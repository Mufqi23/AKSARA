<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke Portal AKSARA — Aplikasi Kegiatan Sekolah dan Akademik Terpadu.">
    <title>Masuk — AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo AKSARA.jpeg') }}">
    <script>
        (function () {
            var theme = localStorage.getItem('lms_theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="{{ asset('css/loader.css') }}?v=20261108d" rel="stylesheet">
    <link href="{{ asset('css/landing.css') }}?v=20261108d" rel="stylesheet">
</head>
<body class="pg-login">
@include('components.page-loader')

<div class="login-split">
    <div class="login-left">
        <div class="login-shapes">
            <div class="ls ls-1"></div>
            <div class="ls ls-2"></div>
            <div class="ls ls-3"></div>
        </div>

        <div class="login-left-content">
            <a class="ln-logo" href="/">
                <img src="{{ asset('images/logo AKSARA.jpeg') }}" alt="Logo AKSARA" class="ln-logo-img">
                <div class="ln-logo-text">
                    AKSARA
                    <small>Aplikasi Kegiatan Sekolah &amp; Akademik Terpadu</small>
                </div>
            </a>

            <h1>Selamat Datang di <em>AKSARA</em></h1>
            <p class="login-left-desc">
                Aplikasi Kegiatan Sekolah dan Akademik Terpadu untuk mengelola kegiatan belajar mengajar, kehadiran siswa harian, penilaian, dan administrasi sekolah.
            </p>

            <ul class="login-features">
                <li>
                    <span class="lf-icon lfi-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg>
                    </span>
                    <div>
                        <h4>Manajemen Kelas</h4>
                        <p>Kelola materi, tugas, dan kuis dalam satu tempat.</p>
                    </div>
                </li>
                <li>
                    <span class="lf-icon lfi-sage">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
                    </span>
                    <div>
                        <h4>Absensi Harian Digital</h4>
                        <p>Catat kehadiran siswa dengan mudah setiap jam pelajaran.</p>
                    </div>
                </li>
                <li>
                    <span class="lf-icon lfi-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </span>
                    <div>
                        <h4>Laporan Akademik</h4>
                        <p>Akses nilai dan statistik kehadiran kapan saja.</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <div class="login-right">
        <div class="login-form-wrap">
            @if(session('success'))
                <div class="login-alert login-alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="login-alert login-alert-error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="/login" class="uiv-card">
                @csrf

                <div class="login-card-top">
                    <div class="login-badge-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <span class="uiv-singup">Masuk Portal Sekolah</span>
                    <p class="uiv-sub">Gunakan email &amp; kata sandi akademik Anda</p>
                </div>

                <div class="login-field">
                    <label for="email">Alamat Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@sekolah.sch.id" required autofocus autocomplete="username">
                </div>

                <div class="login-field">
                    <label for="password">Kata Sandi</label>
                    <div class="login-input-wrap">
                        <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                        <button type="button" class="uiv-eye-btn" data-toggle-pass="password" aria-label="Tampilkan Kata Sandi" title="Lihat / Sembunyikan Kata Sandi">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="uiv-meta-row">
                    <label class="uiv-remember" for="remember">
                        <input type="checkbox" name="remember" id="remember" value="1">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <div class="login-btn-stack">
                    <button class="uiv-enter" type="submit">Masuk ke Portal &rarr;</button>
                    <a href="/" class="btn-outline-card">Kembali ke Beranda</a>
                </div>
            </form>

            <div class="login-help">
                Lupa kata sandi atau kendala akses akun? Silakan hubungi Administrator Sekolah untuk mendapatkan informasi atau pembaruan kata sandi Anda.
            </div>

            <p class="login-copyright">
                &copy; {{ date('Y') }} AKSARA &middot; Aplikasi Kegiatan Sekolah dan Akademik Terpadu
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-pass]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-toggle-pass'));
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    var openIcon = btn.querySelector('.eye-open');
    var closedIcon = btn.querySelector('.eye-closed');
    if (openIcon) openIcon.style.display = show ? 'none' : '';
    if (closedIcon) closedIcon.style.display = show ? '' : 'none';
});
</script>
</body>
</html>
