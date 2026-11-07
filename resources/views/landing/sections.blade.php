{{-- ─── INDIKATOR UTAMA SEKOLAH (CLEAN STAT STRIP) ────────── --}}
<section class="ln-stat-strip" aria-label="Indikator Utama Sekolah">
    <div class="wrap">
        <div class="ln-stat-grid">
            <div class="ln-stat-card">
                <span class="ln-stat-icon ic-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <div>
                    <div class="ln-stat-num">{{ $stats['students'] }}</div>
                    <div class="ln-stat-label">Siswa Terdaftar</div>
                </div>
            </div>
            <div class="ln-stat-card">
                <span class="ln-stat-icon ic-amber">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <div>
                    <div class="ln-stat-num">{{ $stats['teachers'] }}</div>
                    <div class="ln-stat-label">Guru Pengajar</div>
                </div>
            </div>
            <div class="ln-stat-card">
                <span class="ln-stat-icon ic-sage">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M8 7h6"></path><path d="M8 11h8"></path></svg>
                </span>
                <div>
                    <div class="ln-stat-num">{{ $stats['subjects'] }}</div>
                    <div class="ln-stat-label">Mata Pelajaran</div>
                </div>
            </div>
            <div class="ln-stat-card">
                <span class="ln-stat-icon ic-plum">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <div>
                    <div class="ln-stat-num">{{ $stats['attendance_rate'] }}%</div>
                    <div class="ln-stat-label">Rata-rata Kehadiran</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── FITUR UNGGULAN SEKOLAH ─── --}}
<section class="features" id="fitur">
    <div class="wrap">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Fitur Unggulan Sekolah</div>
                <h2 class="section-title">Semua Kebutuhan Kegiatan Belajar Mengajar dalam Satu Portal</h2>
            </div>
            <p class="section-desc mb-0">
                Dirancang khusus untuk lingkungan sekolah dengan antarmuka yang bersih, terstruktur, dan mudah dioperasikan oleh guru maupun siswa.
            </p>
        </div>

        <div class="feat-grid">
            <div class="feat-card">
                <div class="feat-top">
                    <div class="feat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg>
                    </div>
                    <span class="feat-step-tag tag-blue">01 · MATERI &amp; KELAS</span>
                </div>
                <h3>Manajemen Kelas &amp; Mata Pelajaran</h3>
                <p>Guru dapat menyusun materi pelajaran per pertemuan, mengunggah berkas bahan ajar, serta mengelola jadwal kegiatan belajar untuk setiap rombongan belajar.</p>
            </div>

            <div class="feat-card">
                <div class="feat-top">
                    <div class="feat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
                    </div>
                    <span class="feat-step-tag tag-amber">02 · KEHADIRAN HARIAN</span>
                </div>
                <h3>Absensi Siswa Harian Secara Langsung</h3>
                <p>Pencatatan kehadiran harian siswa pada setiap jam pelajaran (Hadir, Terlambat, Izin/Sakit, Alpa) secara akurat tanpa rekap kertas manual.</p>
            </div>

            <div class="feat-card">
                <div class="feat-top">
                    <div class="feat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </div>
                    <span class="feat-step-tag tag-sage">03 · TUGAS &amp; NILAI</span>
                </div>
                <h3>Penugasan, Evaluasi &amp; Buku Nilai</h3>
                <p>Pengumpulan tugas siswa lengkap dengan catatan revisi dari guru, kuis pilihan ganda daring, serta rekapitulasi nilai mata pelajaran yang transparan.</p>
            </div>

            <div class="feat-card">
                <div class="feat-top">
                    <div class="feat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>
                    </div>
                    <span class="feat-step-tag tag-plum">04 · AI PERINGATAN DINI</span>
                </div>
                <h3>AI Deteksi Dini Siswa Berisiko</h3>
                <p>Menganalisis kehadiran, ketidakhadiran tanpa keterangan, dan ketepatan pengumpulan tugas secara otomatis untuk membantu guru membimbing siswa sejak dini.</p>
            </div>
        </div>
    </div>
</section>

{{-- ─── PERAN WARGA SEKOLAH & RINGKASAN SEMESTER ────── --}}
<section class="why-section" id="tentang">
    <div class="wrap">
        <div class="section-eyebrow">Ekosistem Sekolah Digital</div>
        <h2 class="section-title">Ruang Kerja Terpadu untuk Seluruh Warga Sekolah</h2>
        <p class="section-desc">Setiap peran di sekolah mendapatkan tampilan dan fitur yang sesuai dengan kebutuhannya tanpa menu yang membingungkan.</p>

        <div class="why-grid">
            <ul class="why-list">
                <li>
                    <span class="why-list-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <div>
                        <h4>Guru Mata Pelajaran &amp; Wali Kelas</h4>
                        <p>Mengunggah materi ajar, mencatat kehadiran harian kelas, menilai tugas siswa, serta memantau saran pembimbingan dari <strong>AI Peringatan Dini</strong>.</p>
                    </div>
                </li>
                <li>
                    <span class="why-list-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 10-10-5-10 5 10 5 10-5Z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
                    </span>
                    <div>
                        <h4>Siswa / Peserta Didik</h4>
                        <p>Melihat jadwal pelajaran mingguan, mengunduh bahan belajar, mengumpulkan tugas tepat waktu, serta memantau progres kehadiran dan nilai secara mandiri.</p>
                    </div>
                </li>
                <li>
                    <span class="why-list-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </span>
                    <div>
                        <h4>Administrator &amp; Tata Usaha Sekolah</h4>
                        <p>Mengatur tahun ajaran, jurusan, tingkat kelas, rombongan belajar (rombel), penugasan guru mengajar, serta rekap laporan presensi dan nilai sekolah.</p>
                    </div>
                </li>
            </ul>

            <div class="why-visual">
                <div class="why-visual-inner">
                    <div class="d-flex-between mb-3">
                        <h4 class="wv-head-title">Ringkasan Semester Berjalan</h4>
                        <span class="wv-pill wv-pill-blue">Data Aktif</span>
                    </div>
                    <div class="wv-row">
                        <span>Kelas Pelajaran Aktif</span>
                        <span class="wv-pill wv-pill-blue">{{ $stats['classes'] }} Kelas</span>
                    </div>
                    <div class="wv-row">
                        <span>Rata-rata Kehadiran Siswa</span>
                        <span class="wv-pill wv-pill-sage">{{ $stats['attendance_rate'] }}%</span>
                    </div>
                    <div class="wv-row">
                        <span>Tugas Siswa Terkumpul</span>
                        <span class="wv-pill wv-pill-amber">{{ $stats['submissions'] }} Tugas</span>
                    </div>
                    <div class="wv-row">
                        <span>Evaluasi Kuis Terselesaikan</span>
                        <span class="wv-pill wv-pill-blue">{{ $stats['quizzes_completed'] }} Selesai</span>
                    </div>
                    <div class="wv-row">
                        <span>AI Peringatan Dini Siswa</span>
                        <span class="wv-pill wv-pill-sage">Aktif Memantau</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── CTA ──────────────────────────── --}}
<section class="cta">
    <div class="wrap">
        <h2>Siap Memulai Kegiatan Belajar Mengajar?</h2>
        <p>Gunakan akun resmi sekolah Anda untuk masuk ke portal AKSARA dan kelola pembelajaran hari ini dengan lebih rapi dan efektif.</p>
        <a href="/login" class="btn-hero btn-hero-primary">Masuk ke Portal AKSARA →</a>
    </div>
</section>
