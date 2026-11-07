@extends('layouts.app')
@section('title', $quiz->title)

@section('content')
<div class="mb-3">
    <a href="/classes/{{ $quiz->teacher_assignment_id }}#quizzes" class="text-secondary small fw-semibold">
        ← Kembali ke {{ $quiz->classroom->label }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">KUIS & EVALUASI ONLINE</div>
        <h1 class="mb-2">{{ $quiz->title }}</h1>
        @if($quiz->description)
            <p class="text-secondary mb-0">{{ $quiz->description }}</p>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 text-center text-md-start">
            <div class="col-6 col-md-3">
                <div class="stat-label">Batas Waktu</div>
                <div class="h3 fw-bold text-main mt-1 mb-0">{{ $quiz->time_limit }} Menit</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-label">Total Poin</div>
                <div class="h3 fw-bold text-main mt-1 mb-0">{{ $quiz->questions->sum('points') }} Poin</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-label">Kesempatan Percobaan</div>
                <div class="h3 fw-bold text-main mt-1 mb-0">{{ $quiz->max_attempts }} Kali</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-label">Status Kuis</div>
                <div class="h3 fw-bold text-primary mt-1 mb-0">{{ $quiz->status === 'published' ? 'Dipublikasikan' : 'Draf' }}</div>
            </div>
        </div>

        <div class="border-top pt-3 mt-3">
            <p class="small-muted mb-0">
                Kuis dapat diakses mulai tanggal <strong>{{ $quiz->available_from->format('d M Y, H:i') }}</strong> hingga <strong>{{ $quiz->available_until->format('d M Y, H:i') }}</strong>. Nilai tertinggi dari percobaan yang diizinkan akan dihitung ke dalam buku nilai.
            </p>
        </div>

        @if($student && now()->between($quiz->available_from, $quiz->available_until))
            <form method="post" action="/quizzes/{{ $quiz->id }}/start" class="mt-3">
                @csrf
                <button class="btn btn-primary btn-lg" type="submit">
                    Mulai / Lanjutkan Pengerjaan Kuis
                </button>
            </form>
        @elseif($student)
            <div class="alert alert-warning mt-3 mb-0">
                Kuis ini sedang di luar jadwal akses yang ditentukan.
            </div>
        @endif
    </div>
</div>

@unless($student)
    @php($quizEditable = $quiz->status === 'draft' && !($hasAttempts ?? $quiz->attempts()->exists()))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h5 mb-0">Daftar Soal Kuis ({{ $quiz->questions->count() }})</h2>
        </div>
        <div class="card-body">
            @foreach($quiz->questions as $question)
                <div class="list-line p-3 bg-body rounded border mb-2">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div class="fw-semibold text-main">
                            {{ $loop->iteration }}. {{ $question->question }} <span class="badge badge-soft ms-2">{{ $question->points }} Poin</span>
                        </div>
                        @if($quizEditable)
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-popup-target="#edit-question-{{ $question->id }}" data-popup-title="Edit Soal #{{ $loop->iteration }}">
                                    Edit
                                </button>
                                <form method="post" class="m-0" action="/quizzes/{{ $quiz->id }}/questions/{{ $question->id }}" data-confirm="Hapus soal ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        @endif
                    </div>
                    @if($quizEditable)
                        <div id="edit-question-{{ $question->id }}" class="d-none">
                            <form method="post" action="/quizzes/{{ $quiz->id }}/questions/{{ $question->id }}">
                                @csrf
                                @method('PUT')
                                @include('classes.question-fields', ['question' => $question])
                                <div class="d-flex gap-2 mt-3 pt-3 border-top">
                                    <button class="btn btn-primary" type="submit">Simpan Soal</button>
                                    <button class="btn btn-light" type="button" data-popup-close>Batal</button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="mt-2 pt-2 border-top">
                            <span class="badge text-bg-success">Kunci Jawaban: {{ strtoupper($question->correct_answer) }}</span>
                            <span class="ms-2">{{ $question->{'option_'.$question->correct_answer} }}</span>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($quizEditable)
                <h3 class="h6 fw-bold mt-4 mb-3">Tambah Soal Baru</h3>
                <form method="post" action="/quizzes/{{ $quiz->id }}/questions" class="p-3 bg-body rounded border">
                    @csrf
                    @include('classes.question-fields', ['question' => new \App\Models\QuizQuestion])
                    <button class="btn btn-primary" type="submit">+ Tambahkan ke Kuis</button>
                </form>
                <div class="mt-3">
                    <a class="btn btn-outline-primary" href="/classes/{{ $quiz->teacher_assignment_id }}/content/quizzes/{{ $quiz->id }}/edit">
                        Atur Pengaturan / Publikasikan Kuis
                    </a>
                </div>
            @endif
        </div>
    </div>
@endunless

<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Riwayat Percobaan & Hasil Pengerjaan</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Percobaan Ke</th>
                        <th>Waktu Mulai</th>
                        <th>Hasil Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                        <tr>
                            <td class="fw-semibold">{{ $attempt->student->user?->name }}</td>
                            <td>Percobaan ke-{{ $attempt->attempt_number }}</td>
                            <td>{{ $attempt->started_at->format('d M Y, H:i') }}</td>
                            <td>
                                @if($attempt->completed_at)
                                    <span class="badge text-bg-success">{{ $attempt->score }} Poin</span>
                                @else
                                    <span class="badge text-bg-warning">Sedang Berlangsung</span>
                                @endif
                            </td>
                            <td>
                                <a href="/attempts/{{ $attempt->id }}" class="btn btn-sm btn-outline-primary">
                                    {{ $attempt->completed_at ? 'Lihat Lembar Jawaban' : 'Lanjutkan Pengerjaan' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada siswa yang mengambil kuis ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
