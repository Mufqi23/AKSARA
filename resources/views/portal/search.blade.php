@extends('layouts.app')
@section('title', 'Pencarian Materi')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PENCARIAN GLOBAL</div>
        <h1 class="mb-1">Cari Materi & Modul Pelajaran</h1>
        <p class="text-secondary mb-0">Temukan pelajaran, berkas materi, tugas, dan kuis di seluruh kelas yang Anda ikuti.</p>
    </div>
</div>

<form class="d-flex gap-2 mb-4" method="get" action="/search">
    <input class="form-control form-control-lg" name="q" value="{{ request('q') }}" placeholder="Ketik kata kunci judul modul, materi, tugas, atau kuis..." aria-label="Kata kunci pencarian" autofocus>
    <button class="btn btn-primary px-4" type="submit">Cari Materi</button>
</form>

<div class="card">
    <div class="card-body">
        @forelse($results as $result)
            <div class="list-line">
                <span class="small-muted">
                    <span class="badge badge-soft me-1">{{ match($result['kind']) { 'lesson', 'lessons' => 'Modul Bacaan', 'material', 'materials' => 'Berkas Materi', 'assignment', 'assignments' => 'Tugas', 'quiz', 'quizzes' => 'Kuis', default => ucfirst($result['kind']) } }}</span>
                    {{ $result['class'] }}
                </span>
                <h3 class="h6 mt-2 mb-1">
                    <a href="{{ $result['url'] }}" class="text-main fw-bold">{{ $result['title'] }}</a>
                </h3>
            </div>
        @empty
            <div class="empty">
                {{ request('q') ? 'Tidak ditemukan materi atau tugas yang cocok dengan kata kunci "'.request('q').'".' : 'Ketik kata kunci untuk mulai mencari materi di seluruh kelas Anda.' }}
            </div>
        @endforelse
    </div>
</div>
@endsection
