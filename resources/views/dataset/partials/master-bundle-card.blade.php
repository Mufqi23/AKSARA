<section class="dsh-bundle-card mb-4" aria-label="Impor Terpadu Data Akademik Sekolah">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge text-bg-primary">Impor Terpadu Multi-Sheet</span>
                <span class="small text-secondary fw-semibold">1 Buku Kerja Excel (.xlsx) &middot; 7 Lembar Data</span>
            </div>
            <h2 class="h4 mb-2">Impor Data Akademik Sekolah Sekaligus</h2>
            <p class="text-secondary small mb-3">
                Unduh templat buku kerja Excel resmi di bawah ini, isi ke-7 lembar kerja secara berurutan, lalu unggah kembali. Sistem akan memeriksa kecocokan data antar-lembar dan jadwal pelajaran sebelum menyimpan seluruh data sekaligus dengan aman.
            </p>
            <div class="dsh-bundle-highlight">
                <div class="dsh-sheet-pills">
                    <span class="dsh-sheet-pill">01 Tahun Ajaran</span>
                    <span class="dsh-sheet-pill">02 Jurusan &amp; Tingkat</span>
                    <span class="dsh-sheet-pill">03 Rombel</span>
                    <span class="dsh-sheet-pill">04 Data Guru</span>
                    <span class="dsh-sheet-pill">05 Mata Pelajaran</span>
                    <span class="dsh-sheet-pill">06 Kelas &amp; Jadwal</span>
                    <span class="dsh-sheet-pill">07 Data Siswa</span>
                </div>
            </div>
            <ul class="dsh-check-list">
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Pemeriksaan otomatis kecocokan kode jurusan, rombel, guru, dan mata pelajaran</span>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Deteksi bentrok jadwal kelas dan jam mengajar guru sebelum disimpan</span>
                </li>
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Data diperiksa terlebih dahulu per baris sebelum benar-benar disimpan</span>
                </li>
            </ul>
            <a href="/dataset/templates/master_bundle?format=xlsx" class="btn btn-navy btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Unduh Templat Data Terpadu (.xlsx)
            </a>
        </div>
        <div class="col-lg-5">
            <div class="card p-3">
                <h3 class="h6 mb-2">Unggah Berkas Data Terpadu (.xlsx)</h3>
                <form method="post" action="/dataset/preview" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="module" value="master_bundle">
                    <div class="mb-3">
                        <label for="master_bundle_file" class="form-label small text-secondary mb-1">
                            Pilih berkas <code>.xlsx</code> hasil pengisian 7 lembar:
                        </label>
                        <input id="master_bundle_file" type="file" name="file" accept=".xlsx" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        Periksa &amp; Pratinjau Berkas &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
