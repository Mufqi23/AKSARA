<div class="mb-3">
    <label class="form-label">Judul Pengumuman</label>
    <input class="form-control" name="title" value="{{ $announcement->title }}" placeholder="Tuliskan judul pengumuman..." required maxlength="180">
</div>

<div class="mb-3">
    <label class="form-label">Isi Pesan Pengumuman</label>
    <textarea class="form-control" name="body" rows="4" placeholder="Tuliskan isi informasi pengumuman..." required>{{ $announcement->body }}</textarea>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tujuan Kelas Pelajaran</label>
        <select class="form-select" name="teacher_assignment_id" @required(auth()->user()->role === 'teacher')>
            <option value="">— Semua / Tidak Dipilih —</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected($announcement->teacher_assignment_id === $class->id)>{{ $class->label }}</option>
            @endforeach
        </select>
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="col-md-4">
            <label class="form-label">Tujuan Jurusan</label>
            <select class="form-select" name="program_id">
                <option value="">— Semua / Tidak Dipilih —</option>
                @foreach(($programs ?? \App\Services\Catalog::choices('programs')) as $key => $label)
                    <option value="{{ $key }}" @selected($announcement->program_id == $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Tujuan Rombel Kelas</label>
            <select class="form-select" name="block_id">
                <option value="">— Semua / Tidak Dipilih —</option>
                @foreach(($blocks ?? \App\Services\Catalog::choices('blocks')) as $key => $label)
                    <option value="{{ $key }}" @selected($announcement->block_id == $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <p class="small-muted mb-0">Pilih salah satu tujuan penerima, atau kosongkan ketiganya untuk pengumuman seluruh warga sekolah.</p>
        </div>
    @endif
</div>
