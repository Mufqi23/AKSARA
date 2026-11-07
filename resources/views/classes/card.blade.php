<div class="card class-card">
    <div class="class-card-banner">
        <div class="class-banner-top">
            <span class="class-banner-code">{{ $classroom->subject->code }}</span>
            <span class="class-banner-jp">{{ $classroom->subject->units }} JP</span>
        </div>
        <div class="class-banner-sub">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 10-10-5-10 5 10 5 10-5Z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
            <span>{{ $classroom->block->program->code }} &middot; Rombel {{ $classroom->block->name }} <span class="visually-hidden">Block {{ $classroom->block->name }}</span></span>
        </div>
    </div>
    <div class="card-body">
        <h3 class="mb-2">
            <a href="/classes/{{ $classroom->id }}{{ $classAnchor ?? '' }}" class="text-decoration-none text-main">
                {{ $classroom->subject->name }}
            </a>
        </h3>
        <div class="class-meta mb-3">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>{{ $classroom->teacher->user?->name ?? 'Guru Belum Ditentukan' }}</span>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
            <span class="small-muted">{{ $classroom->block->academicYear->name }} &middot; Sem {{ $classroom->block->semester }}</span>
            <a href="/classes/{{ $classroom->id }}{{ $classAnchor ?? '' }}" class="btn btn-sm btn-primary">
                Buka Kelas &rarr;
            </a>
        </div>
    </div>
</div>
