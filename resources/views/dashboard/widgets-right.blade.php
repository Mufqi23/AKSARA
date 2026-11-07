<div class="widget-hub" id="widgetHub" role="region" aria-label="Panel Aktivitas dan Ringkasan">
    {{-- 1. Agenda Tugas & Kuis Mendatang --}}
    <section class="widget-card">
        <div class="widget-card-head">
            <div class="d-flex align-items-center gap-2">
                <span class="widget-head-icon ic-indigo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </span>
                <div>
                    <h2 class="widget-card-title">Tugas & Kuis Mendatang</h2>
                    <span class="widget-card-sub">Jadwal tenggat terdekat di kelas aktif</span>
                </div>
            </div>
            <span class="widget-count-pill">{{ $upcoming->count() + $quizzes->count() }}</span>
        </div>
        <div class="widget-card-body">
            @if($upcoming->isEmpty() && (!config('lms.show_advanced_features') || $quizzes->isEmpty()))
                <div class="widget-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Tidak ada tugas atau kuis aktif mendatang.</span>
                </div>
            @else
                <div class="wf-timeline">
                    @foreach($upcoming as $item)
                        @php $isUrgent = $item->due_at->lte(now()->addDays(2)); @endphp
                        <div class="wf-timeline-item {{ $isUrgent ? 'is-urgent' : '' }}">
                            <div class="wf-date-box">
                                <span class="wf-date-day">{{ $item->due_at->format('d') }}</span>
                                <span class="wf-date-mon">{{ strtoupper($item->due_at->format('M')) }}</span>
                            </div>
                            <div class="wf-item-main">
                                <div class="wf-item-tags">
                                    <span class="class-code">{{ $item->classroom->subject->code }}</span>
                                    <span class="wf-type-tag tag-task">Tugas</span>
                                    @if($isUrgent)
                                        <span class="wf-type-tag tag-urgent">Segera</span>
                                    @endif
                                </div>
                                <h3 class="wf-item-title">
                                    <a href="/assignments/{{ $item->id }}">{{ $item->title }}</a>
                                </h3>
                                <div class="wf-item-meta">
                                    Tenggat {{ $item->due_at->format('d M Y, H:i') }} WIB
                                </div>
                            </div>
                            <a href="/assignments/{{ $item->id }}" class="wf-item-action" title="Buka Tugas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </div>
                    @endforeach

                    @if(config('lms.show_advanced_features'))
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
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- 2. Ringkasan Performa Akademik --}}
    <section class="widget-card">
        <div class="widget-card-head">
            <div class="d-flex align-items-center gap-2">
                <span class="widget-head-icon ic-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
                </span>
                <div>
                    <h2 class="widget-card-title">{{ $user->role === 'teacher' ? 'Ringkasan Kelas yang Diampu' : 'Ringkasan Capaian Sekolah' }}</h2>
                    <span class="widget-card-sub">Rekap seluruh kelas aktif</span>
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
                    <span class="wf-gauge-label">Rata-rata Kehadiran</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="wf-status-chip {{ $rate === null ? 'chip-neutral' : ($rate >= 80 ? 'chip-good' : 'chip-warn') }}">
                            {{ $rate === null ? 'Belum Ada Data' : ($rate >= 80 ? 'Baik' : 'Perlu Pantauan') }}
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
                    <span class="wf-gauge-label">Rata-rata Nilai Tugas</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="wf-status-chip {{ $gradeAverage === null ? 'chip-neutral' : ($gradeAverage >= 75 ? 'chip-good' : 'chip-warn') }}">
                            {{ $gradeAverage === null ? 'Belum Dinilai' : ($gradeAverage >= 75 ? 'Sangat Baik' : 'Cukup') }}
                        </span>
                        <strong class="wf-gauge-val text-success">{{ $gradeAverage === null ? 'N/A' : $gradeAverage.'%' }}</strong>
                    </div>
                </div>
                <div class="wf-progress-track">
                    <div class="wf-progress-fill fill-emerald" style="width: {{ $gradePercent }}%"></div>
                </div>
            </div>

            <p class="small-muted mt-3 mb-0">
                Rata-rata dihitung dari akumulasi poin di seluruh kelas aktif.
            </p>
        </div>
    </section>

    {{-- 3. Akun Pengguna Terbaru (Admin) --}}
    @if($recentUsers->isNotEmpty())
        <section class="widget-card">
            <div class="widget-card-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="widget-head-icon ic-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                    </span>
                    <div>
                        <h2 class="widget-card-title">Akun Pengguna Terbaru</h2>
                        <span class="widget-card-sub">Pendaftaran & status aktivasi</span>
                    </div>
                </div>
            </div>
            <div class="widget-card-body">
                <div class="wf-user-list">
                    @foreach($recentUsers as $recent)
                        <div class="wf-user-row">
                            <span class="wf-user-avatar">{{ strtoupper(substr($recent->name, 0, 2)) }}</span>
                            <div class="wf-user-meta">
                                <a class="wf-user-name" href="{{ $recent->role === 'admin' ? '/admin/manage/users' : '/admin/manage/users/'.$recent->id.'/edit' }}">{{ $recent->name }}</a>
                                <span class="wf-user-role">{{ match($recent->role) { 'admin' => 'Administrator', 'teacher' => 'Guru', 'student' => 'Siswa', default => ucfirst($recent->role) } }}</span>
                            </div>
                            <span class="wf-status-chip {{ $recent->status === 'active' ? 'chip-good' : 'chip-neutral' }}">
                                {{ $recent->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

