<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    @php
        $isSubmitted = in_array($submission->status, ['submitted', 'late']);
        $statusLabel = match($submission->status) {
            'submitted', 'late' => 'Terkirim',
            'graded' => 'Telah Dinilai',
            'returned' => 'Perlu Revisi',
            default => ucfirst($submission->status)
        };
    @endphp
    <span class="badge {{ $submission->status === 'graded' ? 'text-bg-success' : ($submission->status === 'returned' ? 'text-bg-warning' : 'text-bg-primary') }}">
        {{ $statusLabel }}
        @if($isSubmitted)<span class="visually-hidden">Submitted</span>@endif
        @if($submission->status === 'graded')<span class="visually-hidden">Graded</span>@endif
        @if($submission->status === 'returned')<span class="visually-hidden">Needs revision</span>@endif
        {{ ($submission->is_late || $submission->status === 'late') ? ' · Terlambat' : '' }}@if($submission->is_late || $submission->status === 'late')<span class="visually-hidden">Late</span>@endif
    </span>
    @if(config('lms.show_advanced_features'))
        <span class="small-muted">Versi {{ $submission->version }}<span class="visually-hidden">Version {{ $submission->version }}</span></span>
        <span class="text-muted">·</span>
    @endif
    <span class="small-muted">Dikirim pada {{ $submission->submitted_at->format('d M Y, H:i') }}</span>
</div>

@if($submission->answer)
    <details class="mb-3 p-3 bg-body rounded border" @if($student) open @endif>
        <summary class="fw-semibold text-primary" style="cursor: pointer;">Lihat Jawaban Teks</summary>
        <div class="content-text mt-2 pt-2 border-top">{{ $submission->answer }}</div>
    </details>
@endif

@if($submission->path)
    <div class="mb-2">
        <a class="btn btn-sm btn-outline-primary" href="/files/submissions/{{ $submission->id }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Unduh Berkas Lampiran: <strong>{{ $submission->original_name }}</strong>
        </a>
    </div>
@endif

@if($submission->graded_at)
    <div class="alert alert-success mt-3 mb-0 border-0 rounded-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <strong>Nilai: {{ $submission->score }} / {{ $assignment->total_points }} Poin</strong>
            <span class="small text-success">Dinilai pada {{ $submission->graded_at->format('d M Y') }}</span>
        </div>
        @if($submission->feedback)
            <div class="content-text mt-2 pt-2 border-top border-success-subtle">
                <strong>Catatan / Umpan Balik Guru:</strong>
                <div>{{ $submission->feedback }}</div>
            </div>
        @endif
    </div>
@endif
