<div class="mb-3">
    <label class="form-label">Teks Pertanyaan Soal</label>
    <textarea class="form-control" name="question" rows="3" placeholder="Tuliskan butir soal di sini..." required>{{ old('question', $question->question) }}</textarea>
</div>

<div class="row g-3">
    @foreach(['a', 'b', 'c', 'd'] as $letter)
        <div class="col-md-6">
            <label class="form-label">Pilihan Jawaban {{ strtoupper($letter) }}</label>
            <input class="form-control" name="option_{{ $letter }}" value="{{ old('option_'.$letter, $question->{'option_'.$letter}) }}" placeholder="Pilihan {{ strtoupper($letter) }}" required maxlength="255">
        </div>
    @endforeach

    <div class="col-md-6">
        <label class="form-label">Kunci Jawaban Benar</label>
        <select class="form-select" name="correct_answer">
            @foreach(['a', 'b', 'c', 'd'] as $letter)
                <option value="{{ $letter }}" @selected($question->correct_answer === $letter)>Pilihan {{ strtoupper($letter) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Bobot Poin Soal</label>
        <input class="form-control" type="number" step="0.01" min="0.01" max="1000" name="points" value="{{ old('points', $question->points ?? 1) }}" required>
    </div>
</div>
