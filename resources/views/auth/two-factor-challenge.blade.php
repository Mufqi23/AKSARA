<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Verifikasi Dua Faktor (2FA) AKSARA — Aplikasi Kegiatan Sekolah dan Akademik Terpadu.">
    <title>Verifikasi Dua Faktor (2FA) — AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)</title>
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

            <h1>Autentikasi <em>Dua Faktor (2FA)</em></h1>
            <p class="login-left-desc">
                Perlindungan ganda untuk akun akademik Anda. Masukkan kode verifikasi 6 digit sekali pakai (OTP) yang telah dikirimkan untuk melanjutkan sesi aman.
            </p>
        </div>
    </div>

    <div class="login-right">
        <div class="login-form-wrap">
            @if(session('info'))
                <div class="login-alert login-alert-success">{{ session('info') }}</div>
            @endif

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

            <div class="auth-surface-card">
                <div class="login-card-top">
                    <div class="login-badge-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    </div>
                    <span class="uiv-singup">Verifikasi Keamanan Akun</span>
                    <p class="uiv-sub">Masukkan kode 6 digit OTP untuk menyelesaikan proses masuk.</p>
                </div>

                <form method="post" action="/two-factor-challenge">
                    @csrf

                    <div class="field-group">
                        <label for="code">Kode Verifikasi 6 Digit</label>
                        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="Contoh: 123456" required autofocus autocomplete="one-time-code" style="letter-spacing: 4px; font-size: 1.25rem; font-weight: 700; text-align: center;">
                    </div>

                    <button class="btn-submit" type="submit">Verifikasi &amp; Lanjutkan Masuk &rarr;</button>
                </form>

                <form method="post" action="/two-factor-challenge/resend">
                    @csrf
                    <button type="submit" class="btn-outline-card" style="cursor: pointer;">
                        Kirim Ulang Kode OTP
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <a href="/login" class="login-back" style="margin-bottom: 0;">&larr; Kembali ke Halaman Masuk</a>
            </div>

            <div class="login-help">
                Kode verifikasi berlaku selama 10 menit. Jika mengalami kendala penerimaan kode, hubungi administrator sekolah Anda.
            </div>

            <p class="login-copyright">
                &copy; {{ date('Y') }} AKSARA &middot; Aplikasi Kegiatan Sekolah dan Akademik Terpadu
            </p>
        </div>
    </div>
</div>

</body>
</html>
