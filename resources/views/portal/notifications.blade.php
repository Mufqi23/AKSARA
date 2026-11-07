@extends('layouts.app')
@section('title', 'Notifikasi & Pemberitahuan')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PUSAT PEMBERITAHUAN</div>
        <h1 class="mb-1">Notifikasi Akademik</h1>
        <p class="text-secondary mb-0">Semua informasi terbaru seputar tugas, materi, dan pengumuman pembelajaran sekolah.</p>
    </div>
    @if(auth()->user()->unreadNotifications()->exists())
        <form method="post" action="/notifications/read">
            @csrf
            <button class="btn btn-outline-primary d-inline-flex align-items-center gap-1" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Tandai Semua Sudah Dibaca
            </button>
        </form>
    @endif
</div>

<div class="card">
    <div class="card-body">
        @php
            $notificationTitles = [
                'Assignment graded' => 'Tugas Telah Dinilai',
                'Welcome to your workspace' => 'Selamat Datang di Portal Sekolah',
                'Class update' => 'Pengumuman Kelas Baru',
                'Due within 24 hours' => 'Pengingat Tenggat Tugas (Kurang dari 24 Jam)',
            ];
        @endphp
        @forelse($notifications as $notification)
            @php
                $rawTitle = $notification->data['title'] ?? 'Pemberitahuan Sistem';
                $displayTitle = $notificationTitles[$rawTitle] ?? $rawTitle;
            @endphp
            <div class="list-line">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small-muted">{{ $notification->created_at->diffForHumans() }}</span>
                    @unless($notification->read_at)
                        <span class="badge text-bg-primary">Baru</span>
                    @endunless
                </div>
                <h3 class="h6 mb-1">
                    <a href="{{ $notification->data['url'] ?? '/dashboard' }}" class="text-main fw-bold">
                        {{ $displayTitle }}
                        @if($displayTitle !== $rawTitle)
                            <span class="visually-hidden">{{ $rawTitle }}</span>
                        @endif
                    </a>
                </h3>
                <p class="mb-0 text-secondary small">{{ $notification->data['message'] ?? '' }}</p>
            </div>
        @empty
            <div class="empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <p class="mb-0 fw-semibold">Tidak ada pemberitahuan baru saat ini. Anda telah membaca semuanya!</p>
            </div>
        @endforelse

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
