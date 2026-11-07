@extends('layouts.app')
@php
    $kindName = match($kind) {
        'lessons' => 'Pelajaran & Modul',
        'materials' => 'Berkas Materi',
        'assignments' => 'Tugas Siswa',
        'quizzes' => 'Kuis Online',
        default => ucfirst($kind)
    };
@endphp
@section('title', ($row->exists ? 'Ubah ' : 'Tambah ') . $kindName)

@section('content')
<div class="mb-3">
    <a href="/classes/{{ $classroom->id }}#learning" class="text-secondary small fw-semibold">
        ← Kembali ke {{ $classroom->label }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PENGELOLAAN MATERI KELAS</div>
        <h1 class="mb-1">{{ $row->exists ? 'Ubah' : 'Buat' }} {{ $kindName }}</h1>
        <p class="text-secondary mb-0">{{ $classroom->label }}</p>
    </div>
</div>

<div class="card" style="max-width: 850px">
    <div class="card-body">
        @if($kind === 'quizzes')
            <div class="alert alert-info mb-4">
                <strong>Panduan Pembuatan Kuis:</strong> Simpan sebagai draf terlebih dahulu, lalu tambahkan daftar soal pada halaman kuis sebelum menerbitkannya kepada siswa.
            </div>
        @endif

        <form method="post" enctype="multipart/form-data" action="/classes/{{ $classroom->id }}/content/{{ $kind }}{{ $row->exists ? '/'.$row->id : '' }}">
            @csrf
            @if($row->exists) @method('PUT') @endif
            @php($contentFields = $fields)

            @if($kind === 'assignments')
                @include('components.fields', ['fields' => array_intersect_key($contentFields, array_flip(['title','instructions','due_at','total_points','attachment']))])
                <details class="mb-4 p-3 bg-body rounded border" @if($errors->has('description') || $errors->has('allow_text')) open @endif>
                    <summary class="fw-semibold text-primary" style="cursor: pointer;">Opsi Tambahan (Deskripsi & Pengumpulan Teks) <span class="visually-hidden">More Options</span></summary>
                    <div class="mt-3 pt-2 border-top">
                        @include('components.fields', ['fields' => array_intersect_key($contentFields, array_flip(['description','allow_text']))])
                    </div>
                </details>
            @else
                @include('components.fields', ['fields' => array_diff_key($contentFields, ['status' => true])])
            @endif

            <div class="p-3 bg-body rounded border mb-4">
                <span class="small-muted">
                    Status saat ini: <strong class="text-primary">{{ ($row->status ?? 'draft') === 'published' ? 'Dipublikasikan' : 'Draf (Hanya Guru)' }}</strong>.
                    Materi berstatus draf hanya dapat dilihat oleh guru pengajar, sedangkan materi yang dipublikasikan langsung dapat diakses oleh seluruh siswa kelas ini.
                </span>
            </div>

            @error('status')<p class="text-danger small">{{ $message }}</p>@enderror

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary" name="status" value="draft" type="submit">
                    Simpan Sebagai Draf <span class="visually-hidden">Save Draft</span>
                </button>
                <button class="btn btn-primary" name="status" value="published" type="submit">
                    Publikasikan Sekarang <span class="visually-hidden">Publish</span>
                </button>
                <a href="/classes/{{ $classroom->id }}#learning" class="btn btn-light">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
