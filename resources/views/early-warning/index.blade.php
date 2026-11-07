@extends('layouts.app')
@section('title', 'AI Peringatan Dini Siswa')

@section('content')
<section class="hero mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <span class="ews-ai-chip">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>
            Pemantauan Perkembangan Belajar Siswa
        </span>
        <span class="badge badge-soft">{{ $executive['engine'] }}</span>
    </div>
    <h1 class="h2 mb-2">AI Deteksi Dini Siswa Berisiko</h1>
    <p class="text-secondary mb-0">
        Pemantauan otomatis berdasarkan empat aspek belajar: kehadiran harian (35%), jumlah alpa tanpa keterangan (15%), ketepatan pengumpulan tugas (25%), dan rata-rata nilai (25%).
    </p>
</section>

<section class="ews-exec-card mb-4" aria-label="Ringkasan Pemantauan AI">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <h2 class="h5 mb-0">{{ $executive['headline'] }}</h2>
        <span class="badge text-bg-primary">Analisis Kelas Otomatis</span>
    </div>
    <p class="mb-2 text-secondary">{{ $executive['narrative'] }}</p>
    <div class="ews-advice-box">
        <strong>Langkah Pendampingan yang Disarankan:</strong> {{ $executive['action_plan'] }}
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-total">
            <div>
                <div class="ews-kpi-label">Total Siswa Dipantau</div>
                <div class="ews-kpi-val">{{ $counts['total'] }}</div>
            </div>
            <span class="badge badge-soft">100%</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-high">
            <div>
                <div class="ews-kpi-label">Risiko Tinggi (Skor ≥ 60)</div>
                <div class="ews-kpi-val text-danger">{{ $counts['high'] }}</div>
            </div>
            <span class="ews-tier-badge ews-tier-high">Perlu Bimbingan</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-medium">
            <div>
                <div class="ews-kpi-label">Risiko Sedang (Skor 30–59)</div>
                <div class="ews-kpi-val text-warning">{{ $counts['medium'] }}</div>
            </div>
            <span class="ews-tier-badge ews-tier-medium">Perlu Dipantau</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ews-kpi-card is-low">
            <div>
                <div class="ews-kpi-label">Terkendali (Skor 0–29)</div>
                <div class="ews-kpi-val text-success">{{ $counts['low'] }}</div>
            </div>
            <span class="ews-tier-badge ews-tier-low">Stabil</span>
        </div>
    </div>
</div>

<section class="card p-3 mb-4" aria-label="Filter Analisis Peringatan Dini">
    <form method="get" action="/early-warning" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="ews_class_id" class="form-label small fw-semibold mb-1">Kelas Pelajaran</label>
            <select id="ews_class_id" name="class_id" class="form-select form-select-sm">
                <option value="">Semua Kelas yang Dipantau</option>
                @foreach($classes as $cls)
                    <option value="{{ $cls->id }}" @selected((int) request('class_id') === $cls->id)>{{ $cls->label }}</option>
                @endforeach
            </select>
        </div>
        @if($user->role === 'admin')
            <div class="col-md-2">
                <label for="ews_program_id" class="form-label small fw-semibold mb-1">Jurusan</label>
                <select id="ews_program_id" name="program_id" class="form-select form-select-sm">
                    <option value="">Semua Jurusan</option>
                    @foreach($programs as $progId => $progLabel)
                        <option value="{{ $progId }}" @selected((int) request('program_id') === (int) $progId)>{{ $progLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="ews_block_id" class="form-label small fw-semibold mb-1">Rombel / Kelas</label>
                <select id="ews_block_id" name="block_id" class="form-select form-select-sm">
                    <option value="">Semua Rombel</option>
                    @foreach($blocks as $blkId => $blkLabel)
                        <option value="{{ $blkId }}" @selected((int) request('block_id') === (int) $blkId)>{{ $blkLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label for="ews_tier" class="form-label small fw-semibold mb-1">Tingkat Risiko</label>
            <select id="ews_tier" name="tier" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="high" @selected(request('tier') === 'high')>Risiko Tinggi</option>
                <option value="medium" @selected(request('tier') === 'medium')>Risiko Sedang</option>
                <option value="low" @selected(request('tier') === 'low')>Terkendali</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="ews_q" class="form-label small fw-semibold mb-1">Cari Siswa</label>
            <input id="ews_q" type="text" name="q" value="{{ request('q') }}" maxlength="100" class="form-control form-control-sm" placeholder="Nama atau NIS...">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            @if(request()->anyFilled(['class_id', 'program_id', 'block_id', 'tier', 'q']))
                <a href="/early-warning" class="btn btn-sm btn-outline-secondary" title="Reset Filter">Reset</a>
            @endif
        </div>
    </form>
</section>

<section class="card p-0 overflow-hidden" aria-label="Tabel Diagnostik Siswa">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Siswa &amp; Rombel</th>
                    <th>Indikator Presensi</th>
                    <th>Tugas &amp; Nilai</th>
                    <th>Skor &amp; Status Risiko</th>
                    <th>Diagnosis AI &amp; Rekomendasi Guru</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $row)
                    @php
                        $tierClass = match($row['tier']) {
                            'high' => 'ews-tier-high',
                            'medium' => 'ews-tier-medium',
                            default => 'ews-tier-low',
                        };
                        $fillClass = match($row['tier']) {
                            'high' => 'fill-high',
                            'medium' => 'fill-medium',
                            default => 'fill-low',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['name'] }}</div>
                            <div class="small text-secondary">{{ $row['student_number'] }} · {{ $row['program_code'] }} Rombel {{ $row['block_name'] }}</div>
                            @if($row['primary_class_id'])
                                <a href="/classes/{{ $row['primary_class_id'] }}" class="small text-decoration-none">Buka Ruang Kelas →</a>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">
                                {{ $row['attendance_rate'] === null ? 'Belum Ada Sesi' : $row['attendance_rate'].'%' }}
                            </div>
                            <div class="small text-secondary">
                                Alpa: <strong class="{{ $row['absent_count'] >= 2 ? 'text-danger' : '' }}">{{ $row['absent_count'] }}</strong> ·
                                Terlambat: {{ $row['late_count'] }} ·
                                Izin: {{ $row['excused_count'] }}
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                Nilai: {{ $row['grade_average'] === null ? 'N/A' : $row['grade_average'].'%' }}
                            </div>
                            <div class="small text-secondary">
                                Lewat Tenggat: <strong class="{{ $row['missing_overdue_count'] > 0 ? 'text-danger' : '' }}">{{ $row['missing_overdue_count'] }}</strong> ·
                                Revisi: {{ $row['returned_count'] }}
                            </div>
                        </td>
                        <td style="min-width: 165px;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="ews-tier-badge {{ $tierClass }}">{{ $row['tier_label'] }}</span>
                                <strong class="small">{{ $row['risk_score'] }}/100</strong>
                            </div>
                            <div class="ews-score-meter">
                                <div class="ews-score-fill {{ $fillClass }}" style="width: {{ $row['risk_score'] }}%"></div>
                            </div>
                        </td>
                        <td style="max-width: 390px;">
                            <ul class="ews-factor-list mb-2">
                                @foreach($row['factors'] as $factor)
                                    <li>{{ $factor }}</li>
                                @endforeach
                            </ul>
                            <div class="ews-advice-box">
                                <strong>Saran AI:</strong> {{ $row['recommendation'] }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-secondary">
                            Tidak ditemukan data siswa yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
