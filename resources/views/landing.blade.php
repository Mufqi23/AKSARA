<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu) — Portal pembelajaran digital, presensi harian, penilaian rapor, dan AI peringatan dini siswa untuk sekolah.">
    <title>AKSARA — Aplikasi Kegiatan Sekolah dan Akademik Terpadu</title>
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
<body class="pg-landing">
@include('components.page-loader')
@include('landing.navbar-hero')
@include('landing.sections')
@include('landing.footer-scripts')
</body>
</html>
