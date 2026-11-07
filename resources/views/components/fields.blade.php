@foreach($fields as $field=>$type)
@php
    $value = old($field, $row->$field ?? '');
    $choices = $fieldChoices[$field] ?? \App\Services\Catalog::choices($type);
    $label = \App\Services\Catalog::fieldLabel($field, $resource ?? null);
    $labelId = \App\Services\Catalog::fieldLabelId($field, $resource ?? null);
    $isSelect = (bool) ($choices || !in_array($type, ['text', 'email', 'date', 'time', 'number', 'file', 'datetime-local', 'textarea']));
    $invalid = $errors->has($field) ? ' is-invalid' : '';
    $decimal = in_array($field, ['total_points', 'points']) || (($resource ?? '') === 'subjects' && in_array($field, ['units', 'lecture_units', 'laboratory_units']));
    $optional = ($resource ?? '') === 'subjects' && in_array($field, ['program_id', 'year_level_id', 'semester', 'lecture_units', 'laboratory_units']);
    $locked = ($resource ?? '') === 'subjects' && $row->program_id && in_array($field, ['code', 'program_id', 'year_level_id', 'semester']);
@endphp
<div class="mb-3">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
        <label class="form-label mb-0" for="field_{{ $field }}">
            @if(in_array($field, ['program_id', 'year_level_id']) && ($resource ?? '') === 'blocks')
                {{ $label }}
            @else
                {{ $labelId }}@if($labelId !== $label) <span class="visually-hidden">{{ $label }}</span>@endif
            @endif
            @if($field === 'day')
                <span class="text-secondary small">(1 = Senin, 7 = Minggu)</span>
            @endif
        </label>
        @if(in_array($field, ['program_id', 'year_level_id']) && ($resource ?? '') === 'blocks')
            <span class="text-secondary small fw-semibold">({{ $labelId }})</span>
        @endif
    </div>
    @if($type === 'textarea')
        <textarea class="form-control{{ $invalid }}" id="field_{{ $field }}" name="{{ $field }}" rows="{{ in_array($field, ['content', 'instructions']) ? 8 : 3 }}" @required(in_array($field, ['content', 'instructions'])) placeholder="Klik di sini untuk mengetik {{ strtolower($labelId) }}...">{{ $value }}</textarea>
    @elseif($choices || !in_array($type, ['text', 'email', 'date', 'time', 'number', 'file', 'datetime-local']))
        @php
            $dayNames = [1 => '1 — Senin', 2 => '2 — Selasa', 3 => '3 — Rabu', 4 => '4 — Kamis', 5 => '5 — Jumat', 6 => '6 — Sabtu', 7 => '7 — Minggu'];
            $semesterNames = [1 => 'Semester 1 (Ganjil)', 2 => 'Semester 2 (Genap)', 3 => 'Semester 3 (Pendek)'];
        @endphp
        <select class="form-select{{ $invalid }}" id="field_{{ $field }}" name="{{ $field }}" @required(!$optional) @disabled($locked)>
            <option value="">{{ $optional ? 'Umum (Semua Jurusan)' : '— Pilih '.$labelId.' —' }}</option>
            @foreach($choices as $key => $choice)
                <option value="{{ $key }}" @selected((string)$value === (string)$key)>
                    @if($field === 'allow_text')
                        {{ $key ? 'Ya (Boleh Mengetik Jawaban Langsung)' : 'Tidak (Hanya Unggah Berkas)' }}
                    @elseif($field === 'day' && isset($dayNames[(int)$key]))
                        {{ $dayNames[(int)$key] }}
                    @elseif($field === 'semester' && isset($semesterNames[(int)$key]))
                        {{ $semesterNames[(int)$key] }}
                    @else
                        {{ match((string)$choice) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Ditangguhkan', 'student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Administrator', 'draft' => 'Draf', 'published' => 'Terbit', default => $choice } }}
                    @endif
                </option>
            @endforeach
        </select>
        @if($locked)
            <input type="hidden" name="{{ $field }}" value="{{ $row->$field }}">
        @endif
        @if(!$choices)
            <div class="form-text text-danger">Pilihan {{ strtolower($labelId) }} belum tersedia. Buat data terkait terlebih dahulu.</div>
        @endif
    @elseif($type === 'file')
        <input class="form-control{{ $invalid }}" type="file" id="field_{{ $field }}" name="{{ $field }}" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.jpeg,.png,.txt" @required(($kind ?? '') === 'materials' && !$row->exists)>
        <div class="form-text">Mendukung format PDF, dokumen Office, ZIP, gambar, atau TXT (Maksimal 20 MB). @if($row->path) Berkas telah terlampir; biarkan kosong jika tidak ingin mengubah. @endif</div>
    @else
        @php($formatted = $value instanceof \Carbon\CarbonInterface ? $value->format($type === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i') : ($type === 'time' ? substr((string)$value, 0, 5) : $value))
        <input class="form-control{{ $invalid }}" type="{{ $type }}" id="field_{{ $field }}" name="{{ $field }}" value="{{ $formatted }}" @if($type === 'number') min="{{ in_array($field, ['lecture_units', 'laboratory_units']) ? '0' : ($decimal ? '0.01' : '1') }}" step="{{ $decimal ? '0.01' : '1' }}" @if(in_array($field, ['level', 'units', 'lecture_units', 'laboratory_units'])) max="12" @endif @endif @required(!$optional) @readonly($locked) placeholder="Ketik {{ strtolower($labelId) }} di sini...">
        @if($field === 'level')
            <div class="form-text">Angka bulat tingkat kelas, contoh: 10, 11, 12 (atau 1, 2, 3).</div>
        @endif
    @endif
    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
@endforeach
