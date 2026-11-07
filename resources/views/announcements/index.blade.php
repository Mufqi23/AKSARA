@extends('layouts.app')
@section('title', 'Pengumuman Sekolah')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PUSAT INFORMASI</div>
        <h1 class="mb-1">Pengumuman Sekolah</h1>
        <p class="text-secondary mb-0">Papan informasi resmi pembelajaran, jadwal kegiatan, dan pemberitahuan sekolah.</p>
    </div>
</div>

@if(auth()->user()->role !== 'student')
    <div class="card mb-4">
        <div class="card-body">
            <details>
                <summary class="fw-bold text-primary" style="cursor: pointer;">
                    + Buat & Terbitkan Pengumuman Baru
                </summary>
                <form method="post" action="/announcements" class="mt-3 pt-3 border-top">
                    @csrf
                    @include('announcements.fields', ['announcement' => new \App\Models\Announcement])

                    @if(auth()->user()->role === 'teacher' && $classes->isEmpty())
                        <div class="alert alert-warning mt-3">Penugasan kelas diperlukan sebelum Anda dapat mempublikasikan pengumuman.</div>
                    @endif

                    <div class="mt-3">
                        <button class="btn btn-primary" type="submit" @disabled(auth()->user()->role === 'teacher' && $classes->isEmpty())>
                            Publikasikan Pengumuman
                        </button>
                    </div>
                </form>
            </details>
        </div>
    </div>
@endif

@forelse($announcements as $announcement)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="small-muted d-flex align-items-center gap-2">
                    <span class="badge badge-soft">{{ $announcement->created_at->format('d M Y, H:i') }}</span>
                    <span>Oleh: <strong>{{ $announcement->user?->name ?? 'Administrator' }}</strong></span>
                </div>
                <span class="badge text-bg-primary">
                    {{ $announcement->classroom?->label ?? ($announcement->block ? 'Rombel '.$announcement->block->name : ($announcement->program?->name ?? 'Seluruh Sekolah')) }}
                </span>
            </div>

            <h2 class="h5 fw-bold text-main mb-2">{{ $announcement->title }}</h2>
            <div class="content-text text-secondary mb-3">{{ $announcement->body }}</div>

            @if(auth()->user()->role === 'admin' || (auth()->user()->role === 'teacher' && $announcement->user_id === auth()->id()))
                <div class="d-flex gap-2 pt-2 border-top flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-popup-target="#edit-announcement-{{ $announcement->id }}" data-popup-title="Edit Pengumuman">
                        Edit
                    </button>
                    <form method="post" action="/announcements/{{ $announcement->id }}" data-confirm="Hapus pengumuman ini secara permanen?" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus Pengumuman</button>
                    </form>
                </div>
                <div id="edit-announcement-{{ $announcement->id }}" class="d-none">
                    <form method="post" action="/announcements/{{ $announcement->id }}">
                        @csrf
                        @method('PUT')
                        @include('announcements.fields')
                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                            <button class="btn btn-light" type="button" data-popup-close>Batal</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <p class="mb-0 fw-semibold">Belum ada pengumuman terbaru saat ini.</p>
    </div>
@endforelse

<div class="mt-3">
    {{ $announcements->links() }}
</div>
@endsection
