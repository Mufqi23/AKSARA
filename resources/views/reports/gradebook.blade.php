@extends('layouts.app')
@section('title', 'Buku Nilai Kelas')

@section('content')
<div class="mb-3">
    <a href="/classes/{{ $classroom->id }}" class="text-secondary small fw-semibold">
        ← Kembali ke {{ $classroom->label }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">EVALUASI & PENILAIAN</div>
        <h1 class="mb-1">Buku Nilai Mata Pelajaran</h1>
        <p class="text-secondary mb-0">
            Matriks nilai tugas dan kuis siswa pada kelas <strong>{{ $classroom->subject->name }}</strong>.
        </p>
    </div>
    <a class="btn btn-outline-primary align-self-center" href="/reports/grades?class_id={{ $classroom->id }}">
        Buka Laporan Nilai Lengkap
    </a>
</div>

<div class="card">
    <div class="card-body">
        <p class="small-muted mb-4">
            Persentase akhir dihitung berdasarkan total akumulasi poin yang diperoleh dibandingkan dengan total poin maksimum seluruh tugas/kuis yang telah diberikan.
        </p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        @foreach($items as $item)
                            <th class="text-center">
                                {{ $item['title'] }}<br>
                                <span class="badge badge-soft mt-1">/ {{ $item['total'] }} Poin</span>
                            </th>
                        @endforeach
                        <th class="text-end">Nilai Akhir (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        @php($studentGrades = ($grades[$enrollment->student_id] ?? collect())->keyBy(fn($g) => $g->source_type.'-'.$g->source_id))
                        @php($earned = 0)
                        <tr>
                            <td class="fw-semibold">{{ $enrollment->student->user?->name ?? 'Siswa Dinonaktifkan' }}</td>
                            @foreach($items as $item)
                                @php($grade = $studentGrades[$item['key']] ?? null)
                                @php($earned += $grade?->score ?? 0)
                                <td class="text-center">{{ $grade?->score ?? '—' }}</td>
                            @endforeach
                            <td class="text-end">
                                <span class="badge text-bg-primary fs-6">
                                    {{ $items->sum('total') ? round($earned / $items->sum('total') * 100, 2).'%' : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $items->count() + 2 }}" class="text-center text-muted py-4">Belum ada siswa yang terdaftar di kelas ini. <span class="visually-hidden">No enrolled students.</span></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
