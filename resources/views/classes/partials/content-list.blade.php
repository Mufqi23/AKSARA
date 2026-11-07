<div id="learning" class="visually-hidden">Learning</div>

<div class="lw-pane is-active" data-lw-pane="lessons">
    @include('classes.partials.lessons-panel', [
        'lessons' => $content['lessons'] ?? collect(),
        'showSubjectBadge' => false,
    ])
</div>

<div class="lw-pane" data-lw-pane="materials">
    @include('classes.partials.materials-panel', [
        'materials' => $content['materials'] ?? collect(),
        'showSubjectBadge' => false,
    ])
</div>

<div class="lw-pane" data-lw-pane="assignments">
    @include('classes.partials.assignments-panel', [
        'assignments' => $content['assignments'] ?? collect(),
        'showSubjectBadge' => false,
    ])
</div>

@if(isset($content['quizzes']))
    <div class="lw-pane" data-lw-pane="quizzes">
        <section id="quizzes" class="lw-category-shell lw-theme-tugas">
            <div class="lw-category-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="lw-head-badge lw-badge-tugas" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="h5 mb-0 fw-bold">Kuis &amp; Evaluasi Daring</h2>
                            <span class="lw-count-pill lw-count-tugas">{{ $content['quizzes']->count() }} Kuis</span>
                        </div>
                        <p class="small text-secondary mb-0">Evaluasi pilihan ganda daring berbatas waktu untuk mengukur pemahaman materi.</p>
                    </div>
                </div>
                @if($teaching)
                    <a class="btn btn-sm btn-primary" href="/classes/{{ $classroom->id }}/content/quizzes/create">+ Buat Kuis Baru</a>
                @endif
            </div>
            <div class="lw-category-body">
                @forelse($content['quizzes'] as $item)
                    <article class="lw-tugas-card">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div style="flex: 1; min-width: 240px;">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="lw-type-tag lw-tag-tugas">KUIS DARING</span>
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
                                <div class="lw-tugas-meta-strip mt-2">
                                    <span class="lw-meta-chip">Durasi: <strong>{{ $item->time_limit }} menit</strong></span>
                                    <span class="lw-meta-chip">Tersedia: <strong>{{ $item->available_from->format('d M, H:i') }} &ndash; {{ $item->available_until->format('d M, H:i') }}</strong></span>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <a class="btn btn-sm btn-primary" href="/quizzes/{{ $item->id }}">Buka Kuis</a>
                                @if($teaching)
                                    <a class="btn btn-sm btn-outline-secondary" href="/classes/{{ $classroom->id }}/content/quizzes/{{ $item->id }}/edit">Edit</a>
                                    <form method="post" action="/classes/{{ $classroom->id }}/content/quizzes/{{ $item->id }}" data-confirm="Hapus kuis ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty">Belum ada kuis daring yang tersedia pada kelas ini.</div>
                @endforelse
            </div>
        </section>
    </div>
@endif
