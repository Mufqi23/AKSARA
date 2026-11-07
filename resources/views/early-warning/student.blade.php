@extends('layouts.app')
@section('title', 'AI Evaluasi & Peringatan Dini Belajar')

@section('content')
@php
    $overallTierClass = match($overall['tier'] ?? 'low') {
        'high' => 'ews-tier-high',
        'medium' => 'ews-tier-medium',
        default => 'ews-tier-low',
    };
@endphp

<section class="hero mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <span class="ews-ai-chip">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>
            Pendamping Belajar AI
        </span>
        <span class="ews-tier-badge {{ $overallTierClass }}">
            Status Keseluruhan: {{ $overall['tier_label'] ?? 'Terkendali' }} (Skor {{ $overall['risk_score'] ?? 0 }}/100)
        </span>
    </div>
    <h1 class="h2 mb-2">Evaluasi Dini & Panduan Belajar AI</h1>
    <p class="text-secondary mb-0">
        Pantau perkembangan belajar Anda berdasarkan tingkat kehadiran, ketepatan pengumpulan tugas, serta capaian nilai di setiap mata pelajaran.
    </p>
</section>

<section class="ews-exec-card mb-4" aria-label="Ringkasan Evaluasi Belajar Anda">
    <h2 class="h5 mb-2">Ringkasan Perkembangan Belajar — {{ $user->name }}</h2>
    <p class="mb-2 text-secondary">{{ $overall['summary'] ?? '' }}</p>
    <div class="ews-advice-box">
        <strong>Saran Belajar AI untuk Anda:</strong> {{ $overall['student_advice'] ?? '' }}
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-total">
            <div>
                <div class="ews-kpi-label">Tingkat Kehadiran</div>
                <div class="ews-kpi-val">{{ ($overall['attendance_rate'] ?? null) === null ? 'N/A' : $overall['attendance_rate'].'%' }}</div>
            </div>
            <span class="badge badge-soft">Alpa: {{ $overall['absent_count'] ?? 0 }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-high">
            <div>
                <div class="ews-kpi-label">Tugas Lewat Tenggat</div>
                <div class="ews-kpi-val {{ ($overall['missing_overdue_count'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $overall['missing_overdue_count'] ?? 0 }}</div>
            </div>
            <span class="badge badge-soft">Revisi: {{ $overall['returned_count'] ?? 0 }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-low">
            <div>
                <div class="ews-kpi-label">Rata-rata Nilai</div>
                <div class="ews-kpi-val">{{ ($overall['grade_average'] ?? null) === null ? 'N/A' : $overall['grade_average'].'%' }}</div>
            </div>
            <span class="badge badge-soft">Akumulasi</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-medium">
            <div>
                <div class="ews-kpi-label">Skor Risiko Belajar</div>
                <div class="ews-kpi-val">{{ $overall['risk_score'] ?? 0 }}<small class="fs-6 fw-normal">/100</small></div>
            </div>
            <span class="ews-tier-badge {{ $overallTierClass }}">{{ $overall['tier_label'] ?? 'Terkendali' }}</span>
        </div>
    </div>
</div>

<section class="card p-0 overflow-hidden" aria-label="Rincian Risiko per Mata Pelajaran">
    <div class="p-3 border-bottom">
        <h2 class="h5 mb-0">Rincian Evaluasi AI per Mata Pelajaran</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mata Pelajaran &amp; Guru</th>
                    <th>Kehadiran</th>
                    <th>Tugas &amp; Nilai</th>
                    <th>Status Risiko</th>
                    <th>Catatan &amp; Panduan AI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    @php
                        $cTierClass = match($course['tier']) {
                            'high' => 'ews-tier-high',
                            'medium' => 'ews-tier-medium',
                            default => 'ews-tier-low',
                        };
                        $cFillClass = match($course['tier']) {
                            'high' => 'fill-high',
                            'medium' => 'fill-medium',
                            default => 'fill-low',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="class-code mb-1">{{ $course['subject_code'] }}</span>
                            <div class="fw-semibold">
                                <a href="/classes/{{ $course['class_id'] }}">{{ $course['subject_name'] }}</a>
                            </div>
                            <div class="small text-secondary">{{ $course['teacher_name'] }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $course['attendance_rate'] === null ? 'Belum Ada Sesi' : $course['attendance_rate'].'%' }}</div>
                            <div class="small text-secondary">Alpa: {{ $course['absent_count'] }} · Izin: {{ $course['excused_count'] }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">Nilai: {{ $course['grade_average'] === null ? 'N/A' : $course['grade_average'].'%' }}</div>
                            <div class="small text-secondary">Lewat Tenggat: {{ $course['missing_overdue_count'] }} · Revisi: {{ $course['returned_count'] }}</div>
                        </td>
                        <td style="min-width: 155px;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="ews-tier-badge {{ $cTierClass }}">{{ $course['tier_label'] }}</span>
                                <strong class="small">{{ $course['risk_score'] }}/100</strong>
                            </div>
                            <div class="ews-score-meter">
                                <div class="ews-score-fill {{ $cFillClass }}" style="width: {{ $course['risk_score'] }}%"></div>
                            </div>
                        </td>
                        <td style="max-width: 360px;">
                            <ul class="ews-factor-list mb-1">
                                @foreach($course['factors'] as $factor)
                                    <li>{{ $factor }}</li>
                                @endforeach
                            </ul>
                            <div class="small text-secondary">{{ $course['student_advice'] }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-secondary">Belum ada mata pelajaran terdaftar pada semester ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
