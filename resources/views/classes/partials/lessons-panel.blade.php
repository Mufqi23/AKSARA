@php
    $showSubjectBadge = $showSubjectBadge ?? false;
@endphp
<section id="lessons" class="lw-category-shell lw-theme-modul">
    <div class="lw-category-head">
        <div class="d-flex align-items-center gap-3">
            <span class="lw-head-badge lw-badge-modul" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            </span>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="h5 mb-0 fw-bold">Modul &amp; Bacaan Pelajaran</h2>
                    <span class="lw-count-pill lw-count-modul">{{ $lessons->count() }} Modul</span>
                </div>
                <p class="small text-secondary mb-0">Materi teks per bab/pertemuan yang dapat langsung dibaca di layar tanpa mengunduh berkas.</p>
            </div>
        </div>
    </div>

    <div class="lw-category-body">
        @forelse($lessons as $item)
            @php
                $cid = $classroom->id ?? $item->teacher_assignment_id;
                $canManageItem = ($teaching ?? false) && (!isset($isTeacher) || $isTeacher);
                $numLabel = str_pad((string) ($item->position ?: $loop->iteration), 2, '0', STR_PAD_LEFT);
            @endphp
            <article class="lw-modul-card">
                <div class="lw-modul-num" aria-hidden="true">
                    <small>MODUL</small>
                    <strong>{{ $numLabel }}</strong>
                </div>

                <div class="lw-modul-main">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div style="flex: 1; min-width: 220px;">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="lw-type-tag lw-tag-modul">MODUL BACAAN</span>
                                @if($showSubjectBadge && $item->relationLoaded('classroom') && $item->classroom)
                                    <a href="/classes/{{ $item->teacher_assignment_id }}#lessons" class="lw-class-pill">
                                        {{ $item->classroom->subject->code }} &middot; {{ $item->classroom->subject->name }}
                                    </a>
                                @endif
                                @unless($student)
                                    <span class="badge {{ $item->status === 'published' ? 'text-bg-success' : 'badge-soft' }}">
                                        {{ $item->status === 'published' ? 'Dipublikasikan' : 'Draf' }}
                                    </span>
                                @endunless
                            </div>
                            <h3 class="lw-item-title">{{ $item->title }}</h3>
                            @if($item->description)
                                <p class="text-secondary small mb-2">{{ $item->description }}</p>
                            @endif
                        </div>

                        @if($canManageItem)
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <a class="btn btn-sm btn-outline-secondary" href="/classes/{{ $cid }}/content/lessons/{{ $item->id }}/edit">
                                    Edit
                                </a>
                                <form method="post" action="/classes/{{ $cid }}/content/lessons/{{ $item->id }}" data-confirm="Hapus modul bacaan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <details class="lw-modul-reader mt-2">
                        <summary>
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            <span>Baca isi modul lengkap</span>
                        </summary>
                        <div class="content-text lw-modul-text">{{ $item->content }}</div>
                    </details>
                </div>
            </article>
        @empty
            <div class="empty">
                Belum ada modul bacaan teks yang tersedia pada bagian ini.
                <span class="visually-hidden">No lessons available yet.</span>
                @if($teaching ?? false)
                    Gunakan tombol <strong>+ Tambah Modul Bacaan</strong> untuk menyusun materi bab baru.
                @endif
            </div>
        @endforelse
    </div>
</section>

