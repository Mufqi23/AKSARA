<section class="widget-card mb-4" aria-labelledby="announcements-heading">
    <div class="widget-card-head">
        <div class="d-flex align-items-center gap-2">
            <span class="widget-head-icon ic-amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </span>
            <div>
                <h2 id="announcements-heading" class="widget-card-title">Pengumuman Terkini</h2>
                <span class="widget-card-sub">Informasi akademik & kabar sekolah terbaru</span>
            </div>
        </div>
        <span class="widget-count-pill">{{ $announcements->count() }}</span>
    </div>
    <div class="widget-card-body">
        @forelse($announcements as $announcement)
            <div class="list-line">
                <div class="small-muted mb-1 d-flex align-items-center gap-2">
                    <span class="badge badge-soft">{{ $announcement->created_at->format('d M Y') }}</span>
                    <span>{{ $announcement->user?->name ?? 'Administrator Sekolah' }}</span>
                </div>
                <h3 class="mb-1">
                    <a href="/announcements" class="text-main">{{ $announcement->title }}</a>
                </h3>
                <p class="mb-0 text-secondary small">{{ Str::limit($announcement->body, 150) }}</p>
            </div>
        @empty
            <div class="widget-empty">
                <span>Belum ada pengumuman sekolah saat ini.</span>
            </div>
        @endforelse
    </div>
</section>
