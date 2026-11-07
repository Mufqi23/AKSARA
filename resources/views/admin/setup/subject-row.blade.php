<div class="border rounded p-3 mb-3 bg-body" data-repeat-row>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="catalog_{{ $rowIndex }}">Mata Pelajaran Terdaftar (Opsional)</label>
            <select id="catalog_{{ $rowIndex }}" class="form-select" name="subjects[{{ $rowIndex }}][id]" data-subject-choice>
                <option value="">— Atau Masukkan Rincian Mata Pelajaran Baru di Bawah —</option>
                @foreach($subjectChoices as $choice)
                    <option value="{{ $choice->id }}" data-code="{{ $choice->code }}" data-name="{{ $choice->name }}" data-units="{{ $choice->units }}" @selected((string)$subject['id'] === (string)$choice->id)>
                        {{ $choice->catalog_label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="subject_code_{{ $rowIndex }}">Kode Mata Pelajaran</label>
            <input id="subject_code_{{ $rowIndex }}" class="form-control" name="subjects[{{ $rowIndex }}][code]" value="{{ $subject['code'] }}" required maxlength="120" placeholder="Contoh: MTK10" data-subject-code>
        </div>
        <div class="col-sm-6">
            <label class="form-label" for="subject_name_{{ $rowIndex }}">Nama Mata Pelajaran</label>
            <input id="subject_name_{{ $rowIndex }}" class="form-control" name="subjects[{{ $rowIndex }}][name]" value="{{ $subject['name'] }}" required maxlength="120" placeholder="Contoh: Matematika Wajib" data-subject-name>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="subject_units_{{ $rowIndex }}">Jam Pelajaran (JP)</label>
            <input id="subject_units_{{ $rowIndex }}" class="form-control" type="number" min="0.01" max="12" step="0.01" name="subjects[{{ $rowIndex }}][units]" value="{{ $subject['units'] }}" required data-subject-units>
        </div>
        <div class="col-12">
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove>Hapus Mata Pelajaran</button>
        </div>
    </div>
</div>
