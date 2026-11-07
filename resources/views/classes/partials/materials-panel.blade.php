@php
    $showSubjectBadge = $showSubjectBadge ?? false;
@endphp
<section id="materials" class="lw-category-shell lw-theme-materi">
    <div class="lw-category-head">
        <div class="d-flex align-items-center gap-3">
            <span class="lw-head-badge lw-badge-materi" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="m9 15 3 3 3-3"></path></svg>
            </span>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="h5 mb-0 fw-bold">Berkas &amp; Bahan Ajar Unduhan</h2>
                    <span class="lw-count-pill lw-count-materi">{{ $materials->count() }} Berkas</span>
                </div>
                <p class="small text-secondary mb-0">Dokumen panduan belajar, slide presentasi (PPT), dan bahan bacaan tambahan yang dapat diunduh siswa.</p>
            </div>
        </div>
    </div>

    <div class="lw-category-body">
        @if($materials->isNotEmpty())
            <div class="row g-3">
                @foreach($materials as $item)
                    @php
                        $cid = $classroom->id ?? $item->teacher_assignment_id;
                        $canManageItem = ($teaching ?? false) && (!isset($isTeacher) || $isTeacher);
                        $rawName = $item->original_name ?: ($item->path ?: '');
                        $ext = strtoupper((string) pathinfo($rawName, PATHINFO_EXTENSION));
                        if ($ext === '' || strlen($ext) > 5) {
                            $ext = 'FILE';
                        }
                    @endphp
                    <div class="col-md-6">
                        <article class="lw-materi-card h-100">
                            <div class="d-flex align-items-start gap-3">
                                <div class="lw-file-badge" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    <span>{{ $ext }}</span>
                                </div>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="lw-type-tag lw-tag-materi">BAHAN AJAR</span>
                                        @if($showSubjectBadge && $item->relationLoaded('classroom') && $item->classroom)
                                            <a href="/classes/{{ $item->teacher_assignment_id }}#materials" class="lw-class-pill">
                                                {{ $item->classroom->subject->code }} &middot; {{ $item->classroom->subject->name }}
                                            </a>
                                        @endif
                                        @unless($student)
                                            <span class="badge {{ $item->status === 'published' ? 'text-bg-success' : 'badge-soft' }}">
                                                {{ $item->status === 'published' ? 'Dipublikasikan' : 'Draf' }}
                                            </span>
                                        @endunless
                                    </div>
                                    <h3 class="lw-item-title mb-1">{{ $item->title }}</h3>
                                    @if($item->description)
                                        <p class="text-secondary small mb-2">{{ $item->description }}</p>
                                    @endif
                                    @if($item->original_name)
                                        <div class="lw-filename-chip" title="{{ $item->original_name }}">{{ $item->original_name }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="lw-materi-footer">
                                <div>
                                    @if($item->path)
                                        <a class="btn btn-sm btn-materi-dl" href="/files/materials/{{ $item->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            Unduh Berkas
                                        </a>
                                    @else
                                        <span class="small-muted">Tidak ada lampiran <span class="visually-hidden">No file attached</span></span>
                                    @endif
                                </div>

                                @if($canManageItem)
                                    <div class="d-flex gap-2 align-items-center">
                                        <a class="btn btn-sm btn-outline-secondary" href="/classes/{{ $cid }}/content/materials/{{ $item->id }}/edit">
                                            Edit
                                        </a>
                                        <form method="post" action="/classes/{{ $cid }}/content/materials/{{ $item->id }}" data-confirm="Hapus berkas materi ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty">
                Belum ada berkas bahan ajar yang diunggah pada bagian ini.
                @if($teaching ?? false)
                    Gunakan tombol <strong>+ Unggah Berkas Materi</strong> untuk membagikan dokumen pelajaran.
                @endif
            </div>
        @endif
    </div>
</section>

