@php
    $portalUser = auth()->user();
    $userInitials = strtoupper(substr($portalUser->name, 0, 2));
@endphp
<header class="workspace-topbar">
    <div class="workspace-topbar-left">
        <button class="mobile-menu-btn d-lg-none" type="button" id="mobileMenuBtn" aria-label="Buka menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <div>
            <div class="workspace-welcome-title">
                Selamat Datang di <strong>AKSARA</strong>
            </div>
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Portal</a></li>
                    <li class="breadcrumb-item active" aria-current="page">@yield('title', 'Beranda')</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="workspace-topbar-right">
        <div class="workspace-year-pill d-none d-md-inline-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M8 7h6"></path></svg>
            <span>Tahun Ajaran {{ date('Y') }} / {{ date('Y') + 1 }}</span>
        </div>

        <button class="icon-btn" type="button" data-theme-toggle aria-label="Ganti Tema" title="Ganti Tema"></button>

        <a class="user-chip" href="/profile" title="Profil: {{ $portalUser->name }}">
            <span class="user-chip-avatar">{{ $userInitials }}</span>
            <span class="user-chip-name d-none d-sm-inline">{{ Str::words($portalUser->name, 2, '') }}</span>
        </a>
    </div>
</header>

