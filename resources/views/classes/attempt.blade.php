@extends('layouts.app')
@section('title', $attempt->quiz->title)

@section('content')
<div class="mb-3">
    <a href="/quizzes/{{ $attempt->quiz_id }}" class="text-secondary small fw-semibold">
        ← Kembali ke Halaman Kuis
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">LEMBAR PENGERJAAN KUIS</div>
        <h1 class="mb-1">{{ $attempt->quiz->title }}</h1>
        <p class="text-secondary mb-0">
            Percobaan ke-{{ $attempt->attempt_number }} · {{ $attempt->student->user?->name }}
        </p>
    </div>
</div>

@if($attempt->completed_at)
    <div class="hero mb-4">
        <div class="eyebrow">HASIL PENGERJAAN KUIS</div>
        <h1 class="display-6 fw-bold mb-2">{{ $attempt->score }} / {{ $questions->sum('points') }} Poin</h1>
        <p class="mb-0 text-secondary">
            Diselesaikan pada {{ $attempt->completed_at->format('d M Y, H:i') }}
        </p>
    </div>

    <h2 class="h5 mb-3">Pembahasan Soal &amp; Jawaban</h2>
    @foreach($questions as $question)
        @php($answer = $attempt->answers->firstWhere('quiz_question_id', $question->id))
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="h6 fw-bold mb-2">{{ $loop->iteration }}. {{ $question->question }}</h3>
                <div class="p-2 bg-body rounded border mb-2">
                    <span class="text-muted small">Jawaban Anda:</span>
                    <strong class="ms-1">{{ $answer?->answer ? strtoupper($answer->answer).'. '.$question->{'option_'.$answer->answer} : 'Tidak menjawab' }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small-muted">Poin Diperoleh: <strong>{{ $answer?->points ?? 0 }} / {{ $question->points }}</strong></span>
                    @if(auth()->user()->role !== 'student' || now()->gt($attempt->quiz->available_until))
                        <span class="badge text-bg-success">
                            Kunci Jawaban: {{ strtoupper($question->correct_answer) }}. {{ $question->{'option_'.$question->correct_answer} }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        <a class="btn btn-primary" href="/quizzes/{{ $attempt->quiz_id }}">
            Kembali ke Detail Kuis
        </a>
    </div>

@else
    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Sisa Waktu Pengerjaan: <strong id="quizClock" class="fs-5 text-danger">{{ $attempt->expires_at->diffForHumans() }}</strong></span>
        </div>
        <span class="small text-secondary">Jawaban akan otomatis dikirim saat waktu habis.</span>
    </div>

    <form method="post" action="/attempts/{{ $attempt->id }}" @if(auth()->user()->role === 'student') data-quiz-deadline="{{ $attempt->expires_at->toIso8601String() }}" @endif>
        @csrf
        @foreach($questions as $question)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="h6 fw-bold mb-0">{{ $loop->iteration }}. {{ $question->question }}</h3>
                        <span class="badge badge-soft">{{ $question->points }} Poin</span>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        @foreach(['a', 'b', 'c', 'd'] as $letter)
                            <label class="d-flex align-items-center gap-2 p-2 bg-body rounded border hover-lift" style="cursor: pointer;">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $letter }}" class="form-check-input mt-0">
                                <span><strong>{{ strtoupper($letter) }}.</strong> {{ $question->{'option_'.$letter} }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        @if(auth()->user()->role === 'student')
            <div class="mt-4">
                <button class="btn btn-primary btn-lg" type="submit">
                    Kumpulkan Lembar Jawaban Kuis
                </button>
            </div>
        @endif
    </form>
@endif
@endsection
