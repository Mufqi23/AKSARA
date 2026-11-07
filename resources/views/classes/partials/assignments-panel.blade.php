@php
    $showSubjectBadge = $showSubjectBadge ?? false;
    $studentSubmissions = $studentSubmissions ?? collect();
    $quizzes = $quizzes ?? collect();
@endphp
<section id="assignments" class="lw-category-shell lw-theme-tugas">
    <div class="lw-category-head">
        <div class="d-flex align-items-center gap-3">
            <span class="lw-head-badge lw-badge-tugas" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
            </span>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="h5 mb-0 fw-bold">Tugas &amp; Evaluasi Siswa</h2>
                    <span class="lw-count-pill lw-count-tugas">{{ $assignments->count() }} Tugas</span>
                </div>
                <p class="small text-secondary mb-0">Penugasan kelas lengkap dengan batas waktu pengumpulan, bobot poin, dan ruang penilaian.</p>
            </div>
        </div>
    </div>

    <div class="lw-category-body">
        @forelse($assignments as $item)
            @php
                $cid = $classroom->id ?? $item->teacher_assignment_id;
                $canManageItem = ($teaching ?? false) && (!isset($isTeacher) || $isTeacher);
                $isOverdue = $item->due_at->isPast();
                $mySub = $student ? $studentSubmissions->get($item->id) : null;
            @endphp
            <article class="lw-tugas-card {{ $isOverdue && !$mySub && $student ? 'is-overdue' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div style="flex: 1; min-width: 240px;">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="lw-type-tag lw-tag-tugas">TUGAS SISWA</span>
                            @if($showSubjectBadge && $item->relationLoaded('classroom') && $item->classroom)
                                <a href="/classes/{{ $item->teacher_assignment_id }}#assignments" class="lw-class-pill">
                                    {{ $item->classroom->subject->code }} &middot; {{ $item->classroom->subject->name }}
                                </a>
                            @endif

                            @if($student && $studentSubmissions->isNotEmpty() || ($student && $showSubjectBadge))
                                @if($mySub && $mySub->status === 'graded' && $mySub->score !== null)
                                    <span class="badge text-bg-success">Nilai: {{ $mySub->score }} / {{ $item->total_points }}</span>
                                @elseif($mySub && $mySub->status === 'returned')
                                    <span class="badge text-bg-warning">Perlu Revisi</span>
                                @elseif($mySub)
                                    <span class="badge text-bg-primary">Sudah Dikumpulkan</span>
                                @elseif($isOverdue)
                                    <span class="badge text-bg-danger">Melewati Tenggat</span>
                                @else
                                    <span class="badge badge-soft">Belum Dikumpulkan</span>
                                @endif
                            @endif

                            @unless($student)
                                <span class="badge {{ $item->status === 'published' ? 'text-bg-success' : 'badge-soft' }}">
                                    {{ $item->status === 'published' ? 'Dipublikasikan' : 'Draf' }}
                                </span>
                                @if(isset($item->latest_submissions_count))
                                    <span class="badge badge-soft">{{ $item->latest_submissions_count }} Terkumpul</span>
                                @endif
                                @if(($item->ungraded_submissions_count ?? 0) > 0)
                                    <span class="badge text-bg-warning">{{ $item->ungraded_submissions_count }} Perlu Dinilai</span>
                                @endif
                            @endunless
                        </div>

                        <h3 class="lw-item-title">{{ $item->title }}</h3>
                        @if($item->description)
                            <p class="text-secondary small mb-2">{{ $item->description }}</p>
                        @endif

                        <div class="lw-tugas-meta-strip mt-2">
                            <span class="lw-meta-chip {{ $isOverdue ? 'is-danger' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                Tenggat: <strong>{{ $item->due_at->format('d M Y, H:i') }}</strong>
                            </span>
                            <span class="lw-meta-chip">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                Maksimal: <strong>{{ $item->total_points }} poin</strong>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <a class="btn btn-sm btn-primary" href="/assignments/{{ $item->id }}">
                            {{ ($teaching ?? false) ? 'Pengumpulan & Penilaian' : 'Buka Tugas' }}
                            @if($teaching ?? false) <span class="visually-hidden">Submissions &amp; Grades</span> @endif
                        </a>

                        @if($canManageItem)
                            <a class="btn btn-sm btn-outline-secondary" href="/classes/{{ $cid }}/content/assignments/{{ $item->id }}/edit">
                                Edit
                            </a>
                            <form method="post" action="/classes/{{ $cid }}/content/assignments/{{ $item->id }}" data-confirm="Hapus tugas ini? Data pengumpulan siswa yang sudah ada tetap terlindungi.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="empty">
                Belum ada tugas yang diterbitkan pada bagian ini.
                @if($teaching ?? false)
                    Gunakan tombol <strong>+ Buat Tugas Baru</strong> untuk memberikan penugasan kepada siswa.
                @endif
            </div>
        @endforelse
    </div>
</section>

