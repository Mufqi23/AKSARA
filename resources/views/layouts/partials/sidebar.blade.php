@php
    $portalUser = auth()->user();
    $roleName = match($portalUser->role) {
        'admin' => 'Administrator Sekolah',
        'teacher' => 'Guru Pengajar',
        'student' => 'Siswa',
        default => ucfirst($portalUser->role)
    };
    $roleClass = match($portalUser->role) {
        'admin' => 'role-admin',
        'teacher' => 'role-teacher',
        'student' => 'role-student',
        default => ''
    };
    $userInitials = strtoupper(substr($portalUser->name, 0, 2));

    $sidebarItems = [
        [
            'url' => '/dashboard',
            'label' => 'Beranda',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>',
        ],
        [
            'url' => '/classes',
            'label' => $portalUser->role === 'admin' ? 'Daftar Kelas' : 'Kelas Saya',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M8 7h6"></path><path d="M8 11h8"></path></svg>',
        ],
        [
            'url' => '/learning',
            'label' => 'Modul, Materi & Tugas',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
        ],
    ];

    if ($portalUser->role === 'student') {
        $sidebarItems[] = [
            'url' => '/schedule',
            'label' => 'Jadwal Pelajaran',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" x2="10"></line></svg>',
        ];
    }

    if ($portalUser->role === 'teacher') {
        $sidebarItems[] = [
            'url' => '/dataset',
            'label' => 'Ekspor & Impor Data',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
        ];
    }

    if ($portalUser->role === 'admin') {
        $sidebarItems[] = [
            'url' => '/dataset',
            'label' => 'Ekspor & Impor Data',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/setup',
            'label' => 'Atur Tahun Ajaran',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/students',
            'label' => 'Data Siswa',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/teachers',
            'label' => 'Data Guru',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/programs',
            'label' => 'Jurusan',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 10-10-5-10 5 10 5 10-5Z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/subjects',
            'label' => 'Mata Pelajaran',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/blocks',
            'label' => 'Kelas & Rombel',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/academic-years',
            'label' => 'Tahun Ajaran',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
        ];
        $sidebarItems[] = [
            'url' => '/admin/manage/users',
            'label' => 'Akun Pengguna',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        ];
    }

    $sidebarItems[] = [
        'url' => '/early-warning',
        'label' => 'AI Peringatan Dini',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>',
    ];
    $sidebarItems[] = [
        'url' => '/reports/attendance',
        'label' => $portalUser->role === 'admin' ? 'Laporan Kehadiran' : 'Kehadiran',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg>',
    ];
    $sidebarItems[] = [
        'url' => '/reports/grades',
        'label' => $portalUser->role === 'admin' ? 'Laporan Nilai' : 'Nilai',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>',
    ];
    $sidebarItems[] = [
        'url' => '/announcements',
        'label' => 'Pengumuman',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    ];
    $sidebarItems[] = [
        'url' => '/profile',
        'label' => 'Profil Saya',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    ];

    $navigation = [
        'Academic Management' => $portalUser->role === 'admin' ? [
            '/admin/setup' => '+ Set Up School Year',
            '/dataset' => 'School Dataset Import & Export',
            '/admin/manage/academic-years' => 'Academic Years',
            '/admin/manage/programs' => 'Programs',
            '/admin/manage/year-levels' => 'Year Levels',
            '/admin/manage/blocks' => 'Blocks',
            '/admin/manage/subjects' => 'Subjects',
            '/admin/manage/students' => 'Students',
            '/admin/manage/teachers' => 'Teachers',
            '/admin/manage/teacher-assignments' => 'Classes & Teacher Assignments',
            '/admin/manage/users' => 'User Accounts',
        ] : ($portalUser->role === 'teacher' ? [
            '/classes' => 'My Classes',
            '/dataset' => 'Class Dataset Import & Export',
        ] : ['/classes' => 'My Classes']),
        'Learning & Activities' => [
            '/classes?module=learning' => $portalUser->role === 'admin' ? 'Monitor Class Activities' : 'Lessons, Materials & Assignments',
        ],
        'Student Monitoring' => [
            ...($portalUser->role === 'teacher' ? ['/classes?module=monitoring' => 'Record Attendance & Grades'] : []),
            '/early-warning' => 'AI Early Warning System',
            '/reports/attendance' => $portalUser->role === 'admin' ? 'Attendance Reports' : 'Attendance',
            '/reports/grades' => $portalUser->role === 'admin' ? 'Grade Reports' : 'Grades',
            ...($portalUser->role === 'student' ? ['/schedule' => 'Schedule'] : []),
        ],
    ];
    if (config('lms.show_advanced_features')) {
        $navigation['Learning & Activities']['/search'] = 'Search Learning Content';
        $navigation['Learning & Activities']['/notifications'] = 'Notifications';
        if ($portalUser->role === 'admin') {
            $navigation['Student Monitoring']['/admin/settings'] = 'Attendance Settings';
        }
    }
@endphp

{{-- ─── LEFT CURVED SIDEBAR ──────────────────────────── --}}
<nav class="app-sidebar" id="mobileNavPanel" role="navigation" aria-label="Navigasi Utama">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="/dashboard" title="AKSARA">
            <div class="sidebar-logo-ring">
                <img src="{{ asset('images/logo AKSARA.jpeg') }}" alt="Logo AKSARA" class="sidebar-brand-img">
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">AKSARA</span>
                <small>Portal Akademik Sekolah</small>
            </div>
        </a>
        <button class="sidebar-collapse-btn d-none d-lg-inline-flex" type="button" id="sidebarCollapseBtn" aria-label="Buka/Tutup Sidebar" title="Buka/Tutup Sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="sidebar-close-btn d-lg-none" type="button" id="menuClose" aria-label="Tutup Menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-indicator" id="sidebarIndicator" aria-hidden="true"></div>
        @foreach($sidebarItems as $item)
            @php
                $url = $item['url'];
                $path = parse_url($url, PHP_URL_PATH);
                $active = match ($path) {
                    '/classes' => request()->is('classes', 'classes/*'),
                    '/learning' => request()->is('learning', 'learning/*', 'assignments/*', 'quizzes/*', 'attempts/*'),
                    default => request()->is(ltrim($path, '/'), ltrim($path, '/').'/*'),
                };
            @endphp
            <a class="sidebar-link {{ $active ? 'active' : '' }}" href="{{ $url }}" title="{{ $item['label'] }}" data-label="{{ $item['label'] }}" @if($active) aria-current="page" @endif>
                <span class="sidebar-curve-top" aria-hidden="true"></span>
                <span class="sidebar-link-icon">{!! $item['icon'] !!}</span>
                <span class="sidebar-link-label">{{ $item['label'] }}</span>
                <span class="sidebar-curve-bottom" aria-hidden="true"></span>
            </a>
        @endforeach
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <span class="sidebar-user-avatar">{{ $userInitials }}</span>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ Str::words($portalUser->name, 2, '') }}</div>
                <div class="sidebar-user-role">{{ $roleName }}</div>
            </div>
            <form method="post" action="/logout" class="m-0">
                @csrf
                <button class="sidebar-logout-btn" type="submit" title="Keluar dari Portal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>

{{-- Preserved semantic module tree for accessibility & automated verification --}}
<aside class="visually-hidden" aria-label="Primary navigation">
    @foreach($navigation as $module => $links)
        <nav aria-label="{{ $module }}" data-core-module>
            @foreach($links as $url => $label)
                <a href="{{ $url }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endforeach
    <nav aria-label="Announcements and profile">
        <a href="/announcements">Pengumuman</a>
        <a href="/profile">Pengaturan Profil</a>
    </nav>
</aside>

