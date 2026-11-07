<div class="dsh-module-grid">
    @foreach($items as $key => $meta)
        @php
            $querySuffix = $selectedClassId && ($meta['class_scoped'] ?? false) ? '&class_id='.$selectedClassId : '';
        @endphp
        <article class="dsh-module-card">
            <div>
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h3 class="h6 mb-0">{{ $meta['title'] }}</h3>
                    <span class="dsh-module-badge {{ $isClassroomGroup ? 'is-classroom' : '' }}">{{ $meta['badge'] }}</span>
                </div>
                <p class="text-secondary small mb-2">{{ $meta['description'] }}</p>
                <div class="dsh-col-list" title="Struktur kolom lembar kerja">
                    Kolom: {{ implode(', ', $meta['headers']) }}
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                @if($meta['exportable'] ?? false)
                    <div class="dsh-action-box d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="small fw-semibold">Unduh Data:</span>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="/dataset/export/{{ $key }}?format=xlsx{{ $querySuffix }}" class="btn btn-sm btn-outline-primary">
                                Excel (.xlsx)
                            </a>
                            <a href="/dataset/export/{{ $key }}?format=csv{{ $querySuffix }}" class="btn btn-sm btn-outline-secondary">
                                CSV
                            </a>
                            <a href="/dataset/export/{{ $key }}?format=pdf{{ $querySuffix }}" class="btn btn-sm btn-outline-secondary">
                                PDF
                            </a>
                        </div>
                    </div>
                @endif

                @if($meta['importable'] ?? false)
                    <div class="dsh-action-box">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-2">
                            <span class="small fw-semibold">Unggah Berkas:</span>
                            <div class="d-flex gap-2 small">
                                <a href="/dataset/templates/{{ $key }}?format=xlsx{{ $querySuffix }}" class="fw-semibold">
                                    Unduh Templat .xlsx
                                </a>
                                <span class="text-muted">·</span>
                                <a href="/dataset/templates/{{ $key }}?format=csv{{ $querySuffix }}">
                                    .csv
                                </a>
                            </div>
                        </div>
                        <form method="post" action="/dataset/preview" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="module" value="{{ $key }}">
                            @if($selectedClassId && ($meta['class_scoped'] ?? false))
                                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                            @endif
                            <input
                                type="file"
                                name="file"
                                accept=".xlsx,.csv"
                                class="form-control form-control-sm"
                                aria-label="Unggah berkas {{ $meta['title'] }}"
                                required
                            >
                            <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">
                                Pratinjau
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </article>
    @endforeach
</div>

