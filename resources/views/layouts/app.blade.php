<!doctype html>
<html lang="id" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Pembelajaran') · AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo AKSARA.jpeg') }}">
    <script>
        (function() {
            var theme = localStorage.getItem('lms_theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
            var sb = localStorage.getItem('aksara_sidebar');
            if (sb === 'collapsed') {
                document.documentElement.setAttribute('data-sidebar', 'collapsed');
            }
        })();
    </script>
    <link href="{{ asset('vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/loader.css') }}?v=20261108d" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=20261108d" rel="stylesheet">
</head>
<body class="@auth has-sidebar @endauth">
@include('components.page-loader')
@php
    $rawSuccess = session('success');
    $displaySuccess = $rawSuccess;
    if (is_string($rawSuccess)) {
        if (preg_match('/^Grades saved for (\d+) submissions\.$/', $rawSuccess, $matches)) {
            $displaySuccess = 'Nilai berhasil disimpan untuk '.$matches[1].' pengumpulan siswa.';
        } elseif ($rawSuccess === 'No grades changed. Enter a score for the submissions you want to grade.') {
            $displaySuccess = 'Belum ada perubahan nilai. Isi nilai terlebih dahulu pada pengumpulan siswa yang ingin dinilai.';
        } elseif (str_starts_with($rawSuccess, 'Previous setup loaded')) {
            $displaySuccess = 'Pengaturan sebelumnya berhasil dimuat untuk ditinjau. Pilih tahun ajaran dan semester tujuan, lalu periksa setiap langkah.';
        }
    }
@endphp
@auth
<a class="skip-link" href="#main-content">Langsung ke konten</a>

<div class="app-layout">
    @include('layouts.partials.sidebar')

    {{-- ─── RIGHT MAIN WORKSPACE ──────────────────────────── --}}
    <div class="app-workspace">
        @include('layouts.partials.topbar')

        <div class="page-container">
            <main id="main-content" class="animate-page-in">
                @if($rawSuccess)
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <div>
                            {{ $displaySuccess }}
                            @if($displaySuccess !== $rawSuccess)
                                <span class="visually-hidden">{{ $rawSuccess }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger mb-4" role="alert">
                        <strong>Mohon periksa kembali isian berikut: <span class="visually-hidden">Please check the following:</span></strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        <footer class="page-footer">
            <strong>AKSARA</strong> · Aplikasi Kegiatan Sekolah dan Akademik Terpadu
        </footer>
    </div>
</div>

@else
<div class="auth-shell">
    <div class="auth-header-bar">
        <a class="brand" href="/login">
            <img src="{{ asset('images/logo AKSARA.jpeg') }}" alt="Logo AKSARA" class="brand-img">
            <div class="brand-text">
                <div class="brand-title">AK<span>SARA</span></div>
            </div>
        </a>
        <button class="icon-btn" type="button" data-theme-toggle aria-label="Ganti Tema" title="Ganti Tema">
        </button>
    </div>

    <main class="auth-main" id="main-content">
        @if($rawSuccess)
            <div class="alert alert-success mb-3" role="status">
                {{ $displaySuccess }}
                @if($displaySuccess !== $rawSuccess)
                    <span class="visually-hidden">{{ $rawSuccess }}</span>
                @endif
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger mb-3" role="alert">
                <strong>Mohon periksa kembali isian berikut: <span class="visually-hidden">Please check the following:</span></strong>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@endauth

@include('layouts.partials.modals')

<script src="{{ asset('vendor/bootstrap.bundle.min.js') }}" defer></script>
<script src="{{ asset('js/modules/theme-sidebar.js') }}?v=20261108d" defer></script>
<script src="{{ asset('js/modules/modals-forms.js') }}?v=20261108d" defer></script>
<script src="{{ asset('js/modules/learning-tabs.js') }}?v=20261108d" defer></script>
<script src="{{ asset('js/app.js') }}?v=20261108d" defer></script>
@stack('scripts')
</body>
</html>
