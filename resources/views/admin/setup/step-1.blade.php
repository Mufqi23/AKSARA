<p class="text-secondary">Pilih tahun ajaran dan semester pembelajaran. Anda dapat memilih tahun ajaran yang sudah ada atau membuat baru.</p>

@if(!empty($draft['source']))
    <div class="alert alert-info">
        Pengaturan sebelumnya berhasil dimuat. Pilih tahun/semester target. Penempatan siswa baru tetap menjaga riwayat akademik sebelumnya.
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="copy_schedules" id="copy_schedules_step1" value="1" @checked($draft['copy_schedules'] ?? false)>
        <label class="form-check-label small" for="copy_schedules_step1">Salin jadwal dasar ke pengaturan ini</label>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label for="academic_year_id" class="form-label">Tahun Ajaran Terdaftar</label>
        <select class="form-select" id="academic_year_id" name="academic_year_id" data-existing-year>
            <option value="">— Buat Tahun Ajaran Baru —</option>
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected((string)old('academic_year_id', $draft['academic_year_id'] ?? '') === (string)$year->id)>
                    {{ $year->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="semester" class="form-label">Semester Pembelajaran</label>
        <select class="form-select" id="semester" name="semester" required>
            @foreach([1 => 'Semester 1 (Ganjil)', 2 => 'Semester 2 (Genap)', 3 => 'Remedial / Pengayaan'] as $value => $label)
                <option value="{{ $value }}" @selected((int)old('semester', $draft['semester'] ?? 1) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12" data-new-year>
        <div class="row g-3 p-3 bg-body rounded border mt-1">
            <div class="col-md-6">
                <label for="academic_year_name" class="form-label">Nama Tahun Ajaran Baru</label>
                <input class="form-control" id="academic_year_name" name="academic_year_name" value="{{ old('academic_year_name', $draft['academic_year_name'] ?? '') }}" maxlength="120" placeholder="Ketik contoh: 2026/2027" data-new-year-required>
            </div>
            <div class="col-md-3">
                <label for="starts_on" class="form-label">Tanggal Mulai Semester</label>
                <input class="form-control" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $draft['starts_on'] ?? '') }}" data-new-year-required>
            </div>
            <div class="col-md-3">
                <label for="ends_on" class="form-label">Tanggal Selesai Semester</label>
                <input class="form-control" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $draft['ends_on'] ?? '') }}" data-new-year-required>
            </div>
        </div>
    </div>
</div>
