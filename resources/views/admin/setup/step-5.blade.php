<div class="alert alert-info">
    Pilih hingga 500 siswa dari daftar yang sudah ada atau impor menggunakan berkas CSV. Riwayat nilai dan kehadiran siswa sebelumnya tetap tersimpan aman.
</div>

<h3 class="h5 mb-2">A. Pilih Siswa yang Sudah Terdaftar</h3>
<p class="small text-secondary mb-3">{{ count($draft['students'] ?? []) }} siswa dipilih di seluruh halaman. Klik <strong>Simpan Pilihan Siswa</strong> sebelum berpindah halaman atau melakukan pencarian.</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <input class="form-control setup-search" id="student_search" aria-label="Cari nama atau email siswa" placeholder="Cari nama atau email siswa..." value="{{ request('q') }}">
    <button type="button" class="btn btn-outline-secondary" data-student-search>Cari</button>
    <a href="/admin/setup?step=5" class="btn btn-light">Reset</a>
</div>

@php($placements = collect($draft['students'] ?? [])->keyBy('id'))
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th style="width: 50px;">Pilih</th>
                <th>Siswa</th>
                <th>Rombel Saat Ini</th>
                <th>Rombel Tujuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>
                        <input type="hidden" name="visible_ids[]" value="{{ $student->id }}">
                        <input class="form-check-input" aria-label="Daftarkan {{ $student->user?->name ?? 'Siswa' }}" type="checkbox" name="selected[]" value="{{ $student->id }}" @checked($placements->has($student->id))>
                    </td>
                    <td>
                        <strong>{{ $student->user?->name ?? '—' }}</strong>
                        <div class="small text-secondary">NIS: {{ $student->student_number }} · {{ $student->user?->email ?? '—' }}</div>
                    </td>
                    <td>
                        @if($student->block)
                            {{ $student->block->program?->code }} / {{ $student->block->yearLevel?->name }} / Rombel {{ $student->block->name }}
                        @else
                            <span class="text-muted">Belum ada rombel</span>
                        @endif
                    </td>
                    <td>
                        <select class="form-select form-select-sm" aria-label="Rombel tujuan untuk {{ $student->user?->name ?? 'Siswa' }}" name="placements[{{ $student->id }}]">
                            @foreach($blockChoices ?? collect($draft['blocks'])->map(fn($block, $index) => ['index' => $index, 'label' => $block['name']]) as $block)
                                <option value="{{ $block['index'] }}" @selected((int)($placements->get($student->id)['block'] ?? 0) === $block['index'])>
                                    {{ $block['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data siswa yang cocok dengan pencarian. Gunakan opsi CSV untuk menambahkan siswa baru.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="my-3">
    <button class="btn btn-outline-primary" name="action" value="selection" type="submit">
        Simpan Pilihan Siswa
    </button>
</div>
{{ $students->links() }}

<hr class="my-4">

<h3 class="h5 mb-2">B. Impor Data Siswa via Berkas CSV</h3>
<p class="small text-secondary">
    Kolom wajib: <strong>student_id, name, email, block</strong>. Tambahkan <strong>program</strong> dan <strong>year_level</strong> bila nama rombel serupa terdapat pada lebih dari satu jurusan.
</p>
<p class="small text-secondary mb-3">
    Header CSV: <code>student_id,name,email,block,program,year_level</code>. Format CSV berkode UTF-8, maksimal 1 MB / 500 baris.
</p>

<p>
    <a href="/admin/setup/template?scoped=1" class="btn btn-sm btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Unduh Templat Format CSV
    </a>
</p>

<div class="row g-2 align-items-end p-3 bg-body rounded border">
    <div class="col-md-8">
        <label class="form-label" for="csv_file">Pilih Berkas CSV Siswa</label>
        <input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv,text/csv">
    </div>
    <div class="col-md-4">
        <button class="btn btn-outline-primary w-100" name="action" value="preview" type="submit">
            Pratinjau Data CSV
        </button>
    </div>
</div>

@if(!empty($draft['csv_preview']))
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 mb-2">
        <strong class="text-main">{{ $csv['valid_count'] }} baris valid · {{ $csv['invalid_count'] }} baris tidak valid</strong>
        <button class="btn btn-sm btn-outline-danger" name="action" value="clear_csv" type="submit">Hapus Data CSV</button>
    </div>

    @if(isset($csv['errors'][0]))
        <div class="alert alert-danger">
            @foreach($csv['errors'][0] as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Baris</th>
                    <th>Siswa</th>
                    <th>Alamat Email</th>
                    <th>Target Jurusan / Tingkat / Rombel</th>
                    <th>Status Validasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($csvRows as $row)
                    <tr>
                        <td>{{ $row['row'] }}</td>
                        <td>
                            <strong>{{ $row['name'] }}</strong>
                            <div class="small text-secondary">NIS: {{ $row['student_number'] }}</div>
                        </td>
                        <td>{{ $row['email'] }}</td>
                        <td>
                            {{ filled($row['block_label'] ?? '') ? $row['block_label'] : $row['block'] }}
                            @if(!filled($row['block_label'] ?? '') && (filled($row['program'] ?? '') || filled($row['year_level'] ?? '')))
                                <div class="small text-secondary">{{ $row['program'] ?? '' }} / {{ $row['year_level'] ?? '' }}</div>
                            @endif
                        </td>
                        <td>
                            @if($row['valid'])
                                <span class="badge text-bg-success">{{ $row['action'] === 'existing' ? 'Gunakan akun yang ada' : 'Kirim undangan akun baru' }}</span>
                            @else
                                <ul class="text-danger mb-0 ps-3 small">
                                    @foreach($row['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $csvRows->links() }}
    </div>
@endif
