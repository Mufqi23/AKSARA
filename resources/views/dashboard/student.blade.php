<div class="row g-4">
    <div class="col-xl-8">
        <section class="widget-card mb-4" aria-labelledby="student-attention-heading">
            <div class="widget-card-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="widget-head-icon ic-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <div>
                        <h2 id="student-attention-heading" class="widget-card-title">Tugas yang Perlu Dikerjakan <span class="visually-hidden">Needs Attention</span></h2>
                        <span class="widget-card-sub">Daftar tugas pelajaran aktif & perlu revisi</span>
                    </div>
                </div>
                <span class="widget-count-pill">{{ $studentAttention->count() }}</span>
            </div>
            <div class="widget-card-body">
                @forelse($studentAttention as $assignment)
                    @php
                        $returned = $assignment->latest_submission_status === 'returned';
                        $overdue = $assignment->due_at->isPast();
                        $dueSoon = ! $overdue && $assignment->due_at->lte(now()->addDays(7));
                    @endphp
                    <div class="list-line">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <span class="class-code mb-2">{{ $assignment->classroom->subject->code }}</span>
                                <h3 class="mb-1 mt-1">
                                    <a href="/assignments/{{ $assignment->id }}">{{ $assignment->title }}</a>
                                </h3>
                                <div class="small-muted">
                                    Tenggat Waktu: {{ $assignment->due_at->format('d M Y, H:i') }}
                                    @if($returned && $overdue)
                                        · <span class="text-danger fw-semibold">Terlambat (Perlu Revisi) <span class="visually-hidden">Overdue Needs revision</span></span>
                                    @elseif($returned)
                                        · <span class="text-warning fw-semibold">Dikembalikan untuk Revisi <span class="visually-hidden">Needs revision</span></span>
                                    @elseif($overdue)
                                        · <span class="text-danger fw-semibold">Lewat Tenggat <span class="visually-hidden">Overdue</span></span>
                                    @else
                                        · <span class="text-secondary">Belum Dikumpulkan</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-2">
                                <span class="badge {{ $overdue ? 'text-bg-danger' : ($returned ? 'text-bg-warning' : ($dueSoon ? 'text-bg-warning' : 'badge-soft')) }}">
                                    {{ $returned ? 'Perlu Revisi' : ($overdue ? 'Terlambat' : ($dueSoon ? 'Segera Berakhir' : 'Belum Selesai')) }}
                                    <span class="visually-hidden">{{ $returned ? 'Needs revision' : ($overdue ? 'Overdue' : ($dueSoon ? 'Due soon' : 'Pending')) }}</span>
                                </span>
                                <a class="btn btn-sm {{ $returned ? 'btn-warning' : 'btn-outline-primary' }}" href="/assignments/{{ $assignment->id }}">
                                    {{ $returned ? 'Buka Revisi & Kirim' : 'Kerjakan Tugas' }}
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <p class="mb-0 fw-semibold">{{ $classCount ? 'Semua tugas telah diselesaikan dengan baik! Tidak ada tugas tertunda.' : 'Belum ada tugas yang tersedia. Administrator atau guru akan menugaskan kelas Anda.' }}</p>
                    </div>
                @endforelse
                @if($studentAttention->count() === 6)
                    <p class="small-muted mt-3 mb-0">Menampilkan 6 tugas terdekat. Kunjungi menu kelas untuk melihat seluruh daftar tugas.</p>
                @endif
            </div>
        </section>

        <section class="widget-card mb-4" aria-labelledby="recent-activity-heading">
            <div class="widget-card-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="widget-head-icon ic-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    </span>
                    <div>
                        <h2 id="recent-activity-heading" class="widget-card-title">Aktivitas Belajar Terbaru <span class="visually-hidden">Recent Activity</span></h2>
                        <span class="widget-card-sub">Materi baru, tugas, & pembaruan nilai</span>
                    </div>
                </div>
                <span class="widget-count-pill">{{ $recentActivity->count() }}</span>
            </div>
            <div class="widget-card-body">
                @forelse($recentActivity as $activity)
                    <div class="list-line">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="small-muted mb-1">
                                    <span class="badge badge-soft me-1">{{ match($activity['label']) { 'New lesson' => 'Materi Baru', 'New material' => 'Berkas Baru', 'New assignment' => 'Tugas Baru', 'Recent grade' => 'Nilai Masuk', default => $activity['label'] } }}</span>
                                    {{ $activity['subject'] }} · {{ $activity['date']->format('d M Y, H:i') }}
                                </div>
                                <h3 class="mb-1">
                                    <a href="{{ $activity['url'] }}">{{ $activity['title'] }}</a>
                                </h3>
                            </div>
                            @if($activity['score'] !== null)
                                <div class="badge text-bg-success fs-6 py-2 px-3">
                                    {{ $activity['score'] }}
                                    <span class="visually-hidden">{{ $activity['score'] }} points</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        Belum ada aktivitas baru di kelas Anda. Materi, pelajaran, dan pengumuman terbaru akan muncul di sini.
                    </div>
                @endforelse
            </div>
        </section>

        @include('dashboard.classes')
    </div>

    <div class="col-xl-4">
        <div class="widget-hub" id="studentWidgetHub" role="region" aria-label="Panel Progres dan Aktivitas Siswa">
            <section class="widget-card" aria-labelledby="my-progress-heading">
                <div class="widget-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="widget-head-icon ic-emerald">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
                        </span>
                        <div>
                            <h2 id="my-progress-heading" class="widget-card-title">Progres Belajar Saya <span class="visually-hidden">My Progress</span></h2>
                            <span class="widget-card-sub">Kehadiran & capaian nilai pelajaran</span>
                        </div>
                    </div>
                </div>
                <div class="widget-card-body">
                    @php
                        $attPercent = $rate !== null ? min(100, max(0, (float) $rate)) : 0;
                        $gradePercent = $gradeAverage !== null ? min(100, max(0, (float) $gradeAverage)) : 0;
                    @endphp
                    <div class="wf-gauge-box mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <a href="/reports/attendance" class="wf-gauge-label text-decoration-none">Tingkat Kehadiran</a>
                            <div class="d-flex align-items-center gap-2">
                                <span class="wf-status-chip {{ $rate === null ? 'chip-neutral' : ($rate >= 80 ? 'chip-good' : 'chip-warn') }}">
                                    {{ $rate === null ? 'Belum Ada Data' : ($rate >= 80 ? 'Aman' : 'Perlu Ditingkatkan') }}
                                </span>
                                <strong class="wf-gauge-val text-primary">{{ $rate === null ? 'N/A' : $rate.'%' }}</strong>
                            </div>
                        </div>
                        <div class="wf-progress-track">
                            <div class="wf-progress-fill fill-blue" style="width: {{ $attPercent }}%"></div>
                        </div>
                    </div>

                    <div class="wf-gauge-box">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <a href="/reports/grades" class="wf-gauge-label text-decoration-none">Rata-rata Nilai</a>
                            <div class="d-flex align-items-center gap-2">
                                <span class="wf-status-chip {{ $gradeAverage === null ? 'chip-neutral' : ($gradeAverage >= 75 ? 'chip-good' : 'chip-warn') }}">
                                    {{ $gradeAverage === null ? 'Belum Ada Nilai' : ($gradeAverage >= 75 ? 'Sangat Baik' : 'Cukup') }}
                                </span>
                                <strong class="wf-gauge-val text-success">{{ $gradeAverage === null ? 'N/A' : $gradeAverage.'%' }}</strong>
                            </div>
                        </div>
                        <div class="wf-progress-track">
                            <div class="wf-progress-fill fill-emerald" style="width: {{ $gradePercent }}%"></div>
                        </div>
                    </div>

                    @if($rate === null)
                        <p class="small-muted mt-3 mb-1">Belum ada data presensi yang terekam.</p>
                    @endif
                    @if($gradeAverage === null)
                        <p class="small-muted mt-2 mb-1">Belum ada nilai tugas yang diterbitkan.</p>
                    @endif

                    <p class="small-muted mt-3 mb-0">
                        Nilai rata-rata diambil dari akumulasi seluruh tugas yang telah dinilai oleh guru pengajar.
                        <span class="visually-hidden">Attendance excludes excused absences.</span>
                    </p>
                </div>
            </section>

            @include('dashboard.announcements')

            @if(config('lms.show_advanced_features') && $quizzes->isNotEmpty())
                <section class="widget-card">
                    <div class="widget-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <span class="widget-head-icon ic-indigo">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                            <div>
                                <h2 class="widget-card-title">Kuis Mendatang</h2>
                                <span class="widget-card-sub">Kuis aktif yang dapat dikerjakan</span>
                            </div>
                        </div>
                        <span class="widget-count-pill">{{ $quizzes->count() }}</span>
                    </div>
                    <div class="widget-card-body">
                        <div class="wf-timeline">
                            @foreach($quizzes as $quiz)
                                <div class="wf-timeline-item">
                                    <div class="wf-date-box is-quiz">
                                        <span class="wf-date-day">{{ $quiz->available_until->format('d') }}</span>
                                        <span class="wf-date-mon">{{ strtoupper($quiz->available_until->format('M')) }}</span>
                                    </div>
                                    <div class="wf-item-main">
                                        <div class="wf-item-tags">
                                            <span class="class-code">{{ $quiz->classroom->subject->code }}</span>
                                            <span class="wf-type-tag tag-quiz">Kuis</span>
                                        </div>
                                        <h3 class="wf-item-title">
                                            <a href="/quizzes/{{ $quiz->id }}">{{ $quiz->title }}</a>
                                        </h3>
                                        <div class="wf-item-meta">
                                            Ditutup {{ $quiz->available_until->format('d M Y, H:i') }} WIB
                                        </div>
                                    </div>
                                    <a href="/quizzes/{{ $quiz->id }}" class="wf-item-action" title="Buka Kuis">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
