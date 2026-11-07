@php
    $hour = now()->hour;
    $salam = match(true) {
        $hour >= 4 && $hour < 11 => 'Selamat Pagi',
        $hour >= 11 && $hour < 15 => 'Selamat Siang',
        $hour >= 15 && $hour < 18 => 'Selamat Sore',
        default => 'Selamat Malam'
    };
    $hariIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulanIndo = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
    $tglString = ($hariIndo[now()->format('l')] ?? now()->format('l')) . ', ' . now()->format('j') . ' ' . ($bulanIndo[now()->format('F')] ?? now()->format('F')) . ' ' . now()->format('Y');

    $statTranslations = [
        'Students' => 'Total Siswa',
        'Teachers' => 'Total Guru',
        'Programs' => 'Jurusan',
        'Blocks' => 'Kelas & Rombel',
        'Subjects' => 'Mata Pelajaran',
        'Active classes' => 'Kelas Aktif',
        'Assignments' => 'Total Tugas',
        'Awaiting grading' => 'Perlu Dinilai',
        'My classes' => 'Kelas Saya',
        'Upcoming assignments' => 'Tugas Mendatang',
        'Available / upcoming quizzes' => 'Kuis Tersedia',
        'Attendance' => 'Presensi Kehadiran',
    ];

    $statIcons = [
        'Students' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'Teachers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        'Programs' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 10-10-5-10 5 10 5 10-5Z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>',
        'Blocks' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        'Subjects' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>',
        'Active classes' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
        'My classes' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>',
        'Assignments' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>',
        'Upcoming assignments' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        'Awaiting grading' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
        'Attendance' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        'Available / upcoming quizzes' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    ];
@endphp

<div class="page-welcome">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="page-welcome-role {{ match($user->role) { 'admin' => 'role-admin', 'teacher' => 'role-teacher', 'student' => 'role-student', default => '' } }}">
                    <span class="dot {{ match($user->role) { 'admin' => 'dot-blue', 'teacher' => 'dot-orange', 'student' => 'dot-green', default => 'dot-blue' } }}"></span>
                    {{ match($user->role) { 'admin' => 'Administrator', 'teacher' => 'Guru', 'student' => 'Siswa', default => ucfirst($user->role) } }}
                </span>
                <span class="page-welcome-date">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line></svg>
                    {{ $tglString }}
                </span>
            </div>
            <h1>{{ $salam }}, {{ $user->name }}.</h1>
            <p>
                {{ $user->role === 'student' ? 'Pantau tugas, materi pelajaran, dan progres belajar Anda.' : ($user->role === 'teacher' ? 'Kelola kelas, materi ajar, serta pantau kehadiran dan nilai siswa.' : 'Pusat kendali sekolah: kelola data guru, siswa, jurusan, rombel, dan aktivitas pembelajaran.') }}
            </p>
        </div>

        @if($user->role === 'admin')
            <div class="page-welcome-actions mt-0">
                <a class="btn btn-primary" href="/admin/setup">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Atur Tahun Ajaran <span class="visually-hidden">+ Set Up School Year</span>
                </a>
                <a class="btn btn-outline-secondary" href="/admin/setup?duplicate=1">Duplikat Pengaturan <span class="visually-hidden">Duplicate Previous Setup</span></a>
            </div>
        @endif
    </div>

    @if($user->role === 'student' && $user->student?->block)
        <div class="student-info-bar mt-3">
            <span class="fw-bold" style="color:var(--blue)">{{ $user->student->block->program->name }}</span>
            <span class="divider"></span>
            <span>{{ $user->student->block->yearLevel->name }}</span>
            <span class="divider"></span>
            <span>Rombel {{ $user->student->block->name }}</span>
            <span class="divider"></span>
            <span>{{ $user->student->block->academicYear->name }}</span>
        </div>
    @endif
</div>

<div class="stats-row">
    @foreach($stats as $label => $value)
        <div class="stat-card" style="--stat-delay: {{ $loop->index * 70 }}ms">
            <div class="stat-top">
                <span class="stat-label">{{ $statTranslations[$label] ?? $label }}</span>
                <span class="stat-icon" aria-hidden="true">
                    {!! $statIcons[$label] ?? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>' !!}
                </span>
            </div>
            <div class="stat-band">
                <div class="stat-value" data-count-up="{{ $value }}">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

