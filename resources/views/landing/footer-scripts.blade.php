{{-- ─── FOOTER SEKOLAH ──────────────────────────── --}}
<footer class="ln-footer" id="kontak">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="{{ asset('images/logo AKSARA.jpeg') }}" alt="Logo AKSARA" class="footer-brand-img">
                    <span class="footer-brand-name">AKSARA</span>
                </div>
                <p class="footer-about">
                    Aplikasi Kegiatan Sekolah dan Akademik Terpadu — Portal digital pengelolaan pembelajaran, presensi harian, penilaian, dan pemantauan akademik siswa di lingkungan sekolah.
                </p>
            </div>
            <div class="footer-col">
                <h4>Portal Sekolah</h4>
                <a href="/login">Masuk Portal</a>
                <a href="#fitur">Fitur Unggulan</a>
                <a href="#tentang">Peran Warga Sekolah</a>
            </div>
            <div class="footer-col">
                <h4>Layanan Akademik</h4>
                <a href="/login">Presensi Harian</a>
                <a href="/login">Buku Nilai &amp; Tugas</a>
                <a href="/login">AI Peringatan Dini</a>
            </div>
            <div class="footer-col">
                <h4>Pusat Bantuan</h4>
                <a href="#tentang">Panduan Guru &amp; Siswa</a>
                <a href="/login">Bantuan Akun</a>
                <a href="mailto:admin@sekolah.sch.id">Hubungi Tata Usaha</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} AKSARA &middot; Aplikasi Kegiatan Sekolah dan Akademik Terpadu</span>
            <span>Sistem Informasi Akademik &amp; Pembelajaran Sekolah</span>
            <span class="visually-hidden">Delivery, Interaction, Assessment</span>
        </div>
    </div>
</footer>

<script>
(function () {
    const nav = document.getElementById('landingNav');
    const navContainer = document.getElementById('landingNavLinks');
    const links = Array.from(document.querySelectorAll('.ln-links .nav-link'));
    const btn = document.getElementById('mobileToggle');
    let animating = false;

    /* ── Theme Toggle Synced with Inner Portal (lms_theme) ── */
    const themeBtn = document.getElementById('landingThemeToggle');
    const themeLbl = document.getElementById('landingThemeLabel');
    function syncThemeUI() {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        if (themeLbl) themeLbl.textContent = cur === 'dark' ? 'Mode Gelap' : 'Mode Terang';
    }
    syncThemeUI();
    themeBtn?.addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', cur);
        localStorage.setItem('lms_theme', cur);
        syncThemeUI();
    });

    /* ── Navbar Pill Animation & Scroll Spy ── */
    function animate(oldLink, newLink) {
        if (!newLink || oldLink === newLink) return;
        animating = true;
        if (oldLink) {
            oldLink.classList.remove('active');
            oldLink.classList.add('is-releasing');
        }
        newLink.classList.add('is-pressing');
        setTimeout(() => {
            if (oldLink) oldLink.classList.remove('is-releasing');
            newLink.classList.remove('is-pressing');
            newLink.classList.add('active');
            animating = false;
        }, 320);
    }

    links.forEach(link => {
        link.addEventListener('click', e => {
            const href = link.getAttribute('href') || '';
            const currentActive = document.querySelector('.ln-links .nav-link.active');
            if (navContainer?.classList.contains('is-open')) {
                navContainer.classList.remove('is-open');
                btn?.setAttribute('aria-expanded', 'false');
            }
            if (href.startsWith('#')) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    animate(currentActive, link);
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    const sectionIds = ['beranda', 'fitur', 'tentang', 'kontak'];
    window.addEventListener('scroll', () => {
        nav?.classList.toggle('stuck', window.scrollY > 36);
        if (animating) return;
        let activeId = 'beranda';
        const offset = window.scrollY + 180;
        for (const id of sectionIds) {
            const el = document.getElementById(id);
            if (el && el.offsetTop <= offset) activeId = id;
        }
        const match = links.find(l => l.getAttribute('href') === '#' + activeId);
        const current = document.querySelector('.ln-links .nav-link.active');
        if (match && match !== current) {
            current?.classList.remove('active');
            match.classList.add('active');
        }
    }, { passive: true });

    btn?.addEventListener('click', () => {
        const open = navContainer.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(open));
    });
})();
</script>
