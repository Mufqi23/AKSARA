@php
    $academicYearLabel = $activeYear?->name ?? (date('Y').'/'.(date('Y') + 1));
@endphp

{{-- ─── TOP SCHOOL ACADEMIC BAR ─── --}}
<div class="ln-topbar" role="region" aria-label="Informasi Akademik Sekolah">
    <div class="wrap ln-topbar-inner">
        <div class="ln-topbar-left">
            <span class="ln-status-pill">
                <span class="ln-status-dot"></span>
                Tahun Ajaran {{ $academicYearLabel }} Aktif
            </span>
            <span class="ln-topbar-sep">·</span>
            <span class="ln-topbar-meta">Portal E-Learning &amp; Akademik Sekolah Terpadu</span>
        </div>
        <div class="ln-topbar-right">
            <a href="#fitur" class="ln-topbar-link">Fitur Unggulan</a>
            <a href="#tentang" class="ln-topbar-link">Peran Warga Sekolah</a>
            <button type="button" class="ln-theme-btn" id="landingThemeToggle" aria-label="Ganti Tema Terang atau Gelap" title="Ganti Tema Terang / Gelap">
                <svg class="ic-sun" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="ic-moon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <span id="landingThemeLabel">Mode</span>
            </button>
        </div>
    </div>
</div>

{{-- ─── STICKY NAVBAR ──────────────────────────── --}}
<nav class="ln-nav" id="landingNav">
    <div class="wrap">
        <a class="ln-logo" href="#beranda">
            <img src="{{ asset('images/logo AKSARA.jpeg') }}" alt="Logo AKSARA" class="ln-logo-img">
            <div class="ln-logo-text">
                AKSARA
                <small>Aplikasi Kegiatan Sekolah &amp; Akademik Terpadu</small>
            </div>
        </a>
        <div class="ln-links" id="landingNavLinks" role="navigation" aria-label="Navigasi Halaman Utama">
            <a href="#beranda" class="nav-link active">Beranda</a>
            <a href="#fitur" class="nav-link">Fitur</a>
            <a href="#tentang" class="nav-link">Tentang</a>
            <a href="#kontak" class="nav-link">Kontak</a>
            <a href="/login" class="nav-link btn-login">Masuk →</a>
        </div>
        <button class="ln-menu-btn" id="mobileToggle" type="button" aria-label="Menu" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
    </div>
</nav>

{{-- ─── HERO SEKOLAH ──────────────────────── --}}
<section class="hero hero-has-video" id="beranda">
    <div class="hero-media" aria-hidden="true">
        <video class="hero-video" id="heroVideo" autoplay muted loop playsinline preload="metadata"
               src="{{ asset('images/VideoDashboard.mp4') }}"></video>
        <div class="hero-overlay"></div>
    </div>
    <script>
        (function () {
            var video = document.getElementById('heroVideo');
            if (!video) return;
            var src = video.getAttribute('src');
            var query = window.matchMedia('(max-width: 767.98px), (prefers-reduced-motion: reduce)');
            function reveal() { video.classList.add('is-ready'); }
            function apply() {
                if (query.matches) {
                    video.pause();
                    video.classList.remove('is-ready');
                    if (video.getAttribute('src')) { video.removeAttribute('src'); video.load(); }
                } else if (!video.getAttribute('src')) {
                    video.setAttribute('src', src);
                    video.load();
                    video.play().catch(function () {});
                }
            }
            video.addEventListener('playing', reveal);
            if (video.readyState >= 3) reveal();
            apply();
            if (query.addEventListener) query.addEventListener('change', apply);
        })();
    </script>
    <div class="hero-shapes">
        <div class="sh sh-1"></div>
        <div class="sh sh-2"></div>
        <div class="sh sh-3"></div>
        <div class="sh sh-4"></div>
    </div>

    <div class="wrap">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Tahun Ajaran {{ $academicYearLabel }} Aktif
            </div>
            <h1>Kelola Pembelajaran Sekolah Lebih <em>Efisien</em> &amp; Terstruktur</h1>
            <p class="hero-sub">
                Platform digital terpadu untuk guru, siswa, dan staf akademik sekolah.
                Mulai dari materi pelajaran, presensi harian, penilaian tugas, hingga
                pemantauan dini perkembangan belajar siswa — semua dalam satu portal yang rapi dan mudah digunakan.
            </p>
            <div class="hero-actions">
                <a href="/login" class="btn-hero btn-hero-primary">Masuk ke Portal →</a>
                <a href="#fitur" class="btn-hero btn-hero-outline">Pelajari Fitur</a>
            </div>
        </div>

        <div class="hero-visual">
            <img src="{{ asset('images/hero-illustration.jpg') }}" alt="Ilustrasi AKSARA — Aplikasi Kegiatan Sekolah dan Akademik Terpadu" loading="eager">
            <div class="hero-visual-card hvc-1">
                <span class="hvc-icon ic-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
                </span>
                <div>
                    <div class="hvc-num">{{ $stats['subjects'] }}</div>
                    <div class="hvc-label">Mata Pelajaran</div>
                </div>
            </div>
            <div class="hero-visual-card hvc-2">
                <span class="hvc-icon ic-sage">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <div>
                    <div class="hvc-num">{{ $stats['attendance_rate'] }}%</div>
                    <div class="hvc-label">Presensi Tercatat</div>
                </div>
            </div>
        </div>
    </div>
</section>
