<div class="border rounded p-3 mb-3 bg-body" data-repeat-row>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="assignment_subject_{{ $rowIndex }}">Mata Pelajaran</label>
            <select class="form-select" id="assignment_subject_{{ $rowIndex }}" name="assignments[{{ $rowIndex }}][subject]" required>
                @foreach($draft['subjects'] as $subjectIndex => $subject)
                    <option value="{{ $subjectIndex }}" @selected((int)$assignment['subject'] === $subjectIndex)>
                        {{ $subject['label'] ?? ($subject['code'].' — '.$subject['name']) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="assignment_teacher_{{ $rowIndex }}">Guru Pengajar</label>
            <select class="form-select" id="assignment_teacher_{{ $rowIndex }}" name="assignments[{{ $rowIndex }}][teacher_id]" required>
                <option value="">— Pilih Guru Pengajar —</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string)$assignment['teacher_id'] === (string)$teacher->id)>
                        {{ $teacher->user?->name ?? 'Guru' }} — NIP: {{ $teacher->employee_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <fieldset class="col-12">
            <legend class="h6 mb-2">Rombel Kelas Penerima:</legend>
            <div class="row g-2 p-2 bg-surface rounded border">
                @foreach($blockChoices ?? collect($draft['blocks'])->map(fn($block, $index) => ['index' => $index, 'label' => $block['name']]) as $block)
                    <div class="col-md-6">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="assignments[{{ $rowIndex }}][blocks][]" value="{{ $block['index'] }}" @checked(in_array($block['index'], $assignment['blocks']))>
                            <span class="form-check-label">{{ $block['label'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <div class="col-12">
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove>Hapus Penugasan</button>
        </div>
    </div>
</div>
