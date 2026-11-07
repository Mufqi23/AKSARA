@extends('layouts.app')
@section('title', $assignment->title)

@section('content')
<div class="mb-3">
    <a href="/classes/{{ $assignment->teacher_assignment_id }}#assignments" class="text-secondary small fw-semibold">
        ← Kembali ke {{ $assignment->classroom->label }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">TUGAS & PENILAIAN</div>
        <h1 class="mb-2">{{ $assignment->title }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-3 text-secondary">
            <span>Tenggat Waktu: <strong>{{ $assignment->due_at->format('d M Y, H:i') }}</strong></span>
            <span>·</span>
            <span>Total Nilai: <strong>{{ $assignment->total_points }} Poin</strong></span>
            @if($assignment->due_at->isPast())
                <span class="badge text-bg-warning">Lewat Tenggat · Pengumpulan terlambat tetap diterima <span class="visually-hidden">Overdue</span></span>
            @endif
            @unless($student)
                <span class="badge {{ $assignment->status === 'published' ? 'text-bg-success' : 'badge-soft' }}">
                    {{ $assignment->status === 'published' ? 'Dipublikasikan' : 'Draf' }} <span class="visually-hidden">{{ ucfirst($assignment->status) }}</span>
                </span>
            @endunless
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="h5 mb-0">Petunjuk Pengerjaan Tugas</h2>
    </div>
    <div class="card-body">
        @if($assignment->description)
            <p class="text-secondary mb-3">{{ $assignment->description }}</p>
        @endif
        @if($assignment->instructions)
            <div class="content-text p-3 bg-body rounded border mb-3">{{ $assignment->instructions }}</div>
        @endif
        @if($assignment->path)
            <a class="btn btn-outline-primary" href="/files/assignments/{{ $assignment->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Unduh Berkas Lampiran Soal
            </a>
        @endif
    </div>
</div>

@if($student)
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Kumpulkan Tugas Anda</h2>
        </div>
        <div class="card-body">
            <p class="small-muted mb-3">
                {{ $submissions->count() ? 'Mengunggah tugas kembali akan menggantikan tugas yang sebelumnya dikirim.' : 'Anda belum mengumpulkan tugas ini.' }}
                Tugas yang dikumpulkan setelah tenggat waktu akan ditandai sebagai terlambat.
            </p>
            <form method="post" action="/assignments/{{ $assignment->id }}/submit" enctype="multipart/form-data">
                @csrf
                @if($assignment->allow_text)
                    <div class="mb-3">
                        <label class="form-label">Jawaban Teks / Penjelasan</label>
                        <textarea class="form-control @error('answer') is-invalid @enderror" name="answer" rows="5" maxlength="100000" placeholder="Tuliskan jawaban Anda di sini...">{{ old('answer') }}</textarea>
                        @error('answer')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="mb-4">
                    <label class="form-label">Unggah Berkas Tugas (Maksimal 20 MB) <span class="visually-hidden">Choose File</span></label>
                    <input class="form-control @error('attachment') is-invalid @enderror" type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.jpeg,.png,.txt" @required(!$assignment->allow_text)>
                    @error('attachment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary" type="submit">
                    Kirim Pengumpulan Tugas <span class="visually-hidden">Submit Assignment</span>
                </button>
            </form>
        </div>
    </div>

    <h2 class="h5 mb-3">Riwayat Pengumpulan Tugas Anda</h2>
    @forelse($submissions as $submission)
        <div class="card mb-3">
            <div class="card-body">
                @include('classes.submission-details')
            </div>
        </div>
    @empty
        <div class="empty">
            Belum ada berkas tugas yang dikirim. Baca petunjuk di atas, lalu lampirkan berkas jawaban Anda.
        </div>
    @endforelse

@else
    @php($teaching = auth()->user()->role === 'teacher')
    @php($latestSubmissions = $submissions->unique('student_id')->keyBy('student_id'))

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Pengumpulan & Penilaian Siswa</h2>
        <span class="badge badge-soft">{{ $latestSubmissions->count() }} Terkumpul</span>
    </div>

    @if($teaching)
        <p class="small-muted mb-3">
            Berikan nilai dan umpan balik tugas untuk masing-masing siswa di bawah ini. Nilai berkisar antara 0 hingga {{ $assignment->total_points }} poin.
        </p>
        <form method="post" action="/assignments/{{ $assignment->id }}/grades">
            @csrf
            @method('PUT')
            <input type="hidden" name="expected_count" value="{{ $enrollments->filter(fn($row) => $latestSubmissions->has($row->student_id))->count() }}">
    @endif

    @forelse($enrollments as $enrollment)
        @php($submission = $latestSubmissions->get($enrollment->student_id))
        <div class="card mb-3 bulk-grading-row">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0 fw-bold">
                        {{ $enrollment->student->user?->name ?? 'Siswa Dinonaktifkan' }}
                        <span class="small-muted ms-2">NIS: {{ $enrollment->student->student_number }}</span>
                    </h3>
                </div>

                @if($enrollment->getAttribute('former_enrollment'))
                    <div class="badge text-bg-warning mb-2">Pernah terdaftar sebelumnya · Tugas tetap dapat ditinjau <span class="visually-hidden">Former enrollment</span></div>
                @endif

                @if($submission)
                    @include('classes.submission-details')

                    @if($teaching)
                        @php($key = 'grades.'.$submission->id)
                        @php($legacyEditing = (string)old('submission_id') === (string)$submission->id)
                        <div class="row g-3 mt-3 pt-3 border-top">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Nilai / {{ $assignment->total_points }}</label>
                                <input class="form-control @error($key.'.score') is-invalid @enderror" name="grades[{{ $submission->id }}][score]" type="number" step="0.01" min="0" max="{{ $assignment->total_points }}" value="{{ old($key.'.score', $legacyEditing ? old('score') : $submission->score) }}" placeholder="0 - {{ $assignment->total_points }}">
                                @error($key.'.score')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Status Penilaian</label>
                                <select class="form-select @error($key.'.status') is-invalid @enderror" name="grades[{{ $submission->id }}][status]">
                                    <option value="graded" @selected(old($key.'.status', $legacyEditing ? old('status') : $submission->status) !== 'returned')>Dinilai (Selesai)</option>
                                    <option value="returned" @selected(old($key.'.status', $legacyEditing ? old('status') : $submission->status) === 'returned')>Perlu Revisi Siswa</option>
                                </select>
                                @error($key.'.status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan / Umpan Balik</label>
                                <textarea class="form-control @error($key.'.feedback') is-invalid @enderror" name="grades[{{ $submission->id }}][feedback]" rows="2" maxlength="10000" placeholder="Komentar atau alasan revisi...">{{ old($key.'.feedback', $legacyEditing ? old('feedback') : $submission->feedback) }}</textarea>
                                @error($key.'.feedback')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-secondary small mb-0">Belum ada pengumpulan tugas dari siswa ini. <span class="visually-hidden">No submission yet</span></p>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">Tidak ada siswa yang terdaftar di kelas ini.</div>
    @endforelse

    @if($teaching)
        <div class="mt-4">
            <button class="btn btn-primary" type="submit" @disabled($latestSubmissions->isEmpty())>
                Simpan Seluruh Nilai Tugas <span class="visually-hidden">Save All Grades</span>
            </button>
        </div>
        </form>
    @endif

    @if(config('lms.show_advanced_features') && $submissions->isNotEmpty())
        <details class="mt-4 p-3 bg-body rounded border">
            <summary class="fw-semibold text-primary" style="cursor: pointer;">Riwayat Seluruh Versi Pengumpulan</summary>
            @foreach($submissions as $submission)
                <div class="card mt-3">
                    <div class="card-body">
                        <h4 class="h6 fw-bold mb-2">{{ $submission->student->user?->name ?? 'Siswa' }}</h4>
                        @include('classes.submission-details')
                    </div>
                </div>
            @endforeach
        </details>
    @endif
@endif
@endsection
