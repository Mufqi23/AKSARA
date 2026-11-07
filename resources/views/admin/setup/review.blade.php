@php
    $targetYear = $academicYears->firstWhere('id', $draft['academic_year_id'] ?? null);
    $teacherNames = $teachers->keyBy('id');
    $classCount = array_sum(array_map(fn($row) => count($row['blocks']), $draft['assignments']));
    $assignedBlocks = array_unique(array_merge(...array_column($draft['assignments'], 'blocks')));
    $scopedBlocks = collect($blockChoices ?? collect($draft['blocks'])->map(function ($block, $index) use ($draft, $programs, $yearLevels) {
        $program = $programs->firstWhere('id', $block['program_id'] ?? $draft['program_id'] ?? null);
        $level = $yearLevels->firstWhere('id', $block['year_level_id'] ?? $draft['year_level_id'] ?? null);
        return [
            'index' => $index,
            'name' => $block['name'],
            'program' => $program?->code ?? 'Jurusan',
            'program_name' => $program?->name ?? '',
            'year_level' => $level?->name ?? $block['new_name'] ?? 'Tingkat Kelas',
            'level' => $level?->level ?? $block['new_level'] ?? '',
            'label' => ($program?->code ?? 'Jurusan').' / '.($level?->name ?? $block['new_name'] ?? 'Tingkat Kelas').' / Rombel '.$block['name']
        ];
    }));
    $blockLabels = $scopedBlocks->keyBy('index');
    $semesterText = match((int)($draft['semester'] ?? 1)) {
        1 => 'Semester 1 (Ganjil)',
        2 => 'Semester 2 (Genap)',
        3 => 'Remedial / Pengayaan',
        default => 'Semester '.$draft['semester']
    };
@endphp

<div class="card mb-4 bg-body border">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3 text-secondary">Tahun Ajaran</dt>
            <dd class="col-sm-9 fw-bold">
                {{ $targetYear?->name ?? $draft['academic_year_name'] ?? 'Tahun yang dipilih' }}
                @if(!$targetYear && !empty($draft['academic_year_name']))
                    <span class="badge text-bg-primary ms-1">Baru</span>
                @endif
            </dd>

            <dt class="col-sm-3 text-secondary">Semester Pembelajaran</dt>
            <dd class="col-sm-9 fw-semibold">{{ $semesterText }}</dd>

            <dt class="col-sm-3 text-secondary">Pendaftaran Siswa</dt>
            <dd class="col-sm-9">{{ $studentCount }} siswa terdaftar · {{ $newAccountCount }} undangan aktivasi akun baru</dd>

            <dt class="col-sm-3 text-secondary">Penyalinan Jadwal</dt>
            <dd class="col-sm-9">{{ !empty($draft['copy_schedules']) ? 'Salin jadwal dasar (konflik jadwal akan divalidasi)' : 'Atur kemudian melalui menu manajemen jadwal' }}</dd>
        </dl>
    </div>
</div>

<h3 class="h5 mb-3">Jurusan, Tingkat Kelas & Rombel</h3>
<div class="row g-3 mb-4">
    @foreach($scopedBlocks->groupBy('program') as $programBlocks)
        <section class="col-md-6">
            <div class="border rounded p-3 h-100 bg-surface">
                <h4 class="h6 fw-bold text-primary mb-2">{{ $programBlocks->first()['program'] }} — {{ $programBlocks->first()['program_name'] }}</h4>
                @foreach($programBlocks->groupBy('level') as $levelBlocks)
                    <div class="border-top mt-2 pt-2">
                        <div class="fw-semibold text-main">{{ $levelBlocks->first()['year_level'] }} @if(filled($levelBlocks->first()['level']))<span class="small text-secondary">(Tingkat {{ $levelBlocks->first()['level'] }})</span>@endif</div>
                        <div class="small-muted mt-1">Rombel: <strong>{{ implode(', ', $levelBlocks->pluck('name')->all()) }}</strong></div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

<h3 class="h5 mb-3">{{ count($draft['subjects']) }} Mata Pelajaran · {{ $classCount }} Kelas Pelajaran</h3>
<div class="table-responsive mb-4">
    <table class="table">
        <thead>
            <tr>
                <th>Mata Pelajaran</th>
                <th>JP</th>
                <th>Guru Pengajar</th>
                <th>Rombel / Kelas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($draft['assignments'] as $row)
                @php($subject = $draft['subjects'][$row['subject']])
                <tr>
                    <td>
                        <strong>{{ $subject['label'] ?? ($subject['code'].' — '.$subject['name']) }}</strong>
                        <div class="small text-secondary">{{ $subject['id'] ? 'Mata pelajaran terdaftar' : 'Mata pelajaran baru' }}</div>
                    </td>
                    <td>{{ $subject['units'] }} JP</td>
                    <td class="fw-semibold">{{ $teacherNames->get($row['teacher_id'])?->user?->name ?? 'Belum Ditugaskan' }}</td>
                    <td>
                        @foreach($row['blocks'] as $blockIndex)
                            <span class="badge badge-soft me-1 mb-1">{{ $blockLabels->get($blockIndex)['label'] ?? $draft['blocks'][$blockIndex]['name'] }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(count($assignedBlocks) < count($draft['blocks']))
    <div class="alert alert-warning mb-4">
        Beberapa rombel belum memiliki penugasan mata pelajaran. Anda tetap dapat melengkapinya nanti melalui halaman manajemen kelas.
    </div>
@endif

<div class="alert alert-info mb-4">
    Menyimpan pengaturan ini akan memperbarui penempatan rombel siswa dan mendaftarkan kelas-kelas baru. Seluruh data riwayat akademik terdahulu tetap terjaga aman.
</div>

<form method="post" action="/admin/setup/create" data-confirm="Simpan seluruh struktur tahun ajaran yang telah ditinjau ini?" data-setup-create>
    @csrf
    <input type="hidden" name="draft_token" value="{{ $draft['token'] }}">
    <input type="hidden" name="review_hash" value="{{ $draft['review_hash'] }}">

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="confirm" id="confirm_setup" value="1" required>
        <label class="form-check-label fw-bold" for="confirm_setup">
            Saya telah meninjau dan memastikan seluruh data tahun ajaran, jurusan, kelas, dan penempatan siswa sudah benar.
        </label>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="/admin/setup?step=5">
            ← Kembali ke Penempatan Siswa
        </a>
        <button class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2" type="submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Simpan & Terapkan Struktur Akademik <span class="visually-hidden">Create Academic Setup</span>
        </button>
    </div>
</form>
