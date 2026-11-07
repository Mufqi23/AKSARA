<p class="text-secondary">Tugaskan masing-masing mata pelajaran kepada guru pengajar dan tentukan rombel kelas penerima. Setiap pasangan rombel dan mata pelajaran diajar oleh satu guru.</p>

@if($teachers->isEmpty())
    <div class="alert alert-warning">
        Belum ada data guru terdaftar. <a href="/admin/manage/teachers/create" class="fw-bold">Tambahkan data guru</a> sebelum melanjutkan.
    </div>
@endif

<div data-repeat="assignments">
    @foreach(old('assignments', $draft['assignments'] ?? [['subject' => 0, 'teacher_id' => '', 'blocks' => []]]) as $index => $assignment)
        @include('admin.setup.assignment-row', ['rowIndex' => $index])
    @endforeach
</div>

<template data-template="assignments">
    @include('admin.setup.assignment-row', ['rowIndex' => '__index__', 'assignment' => ['subject' => 0, 'teacher_id' => '', 'blocks' => []]])
</template>

<div class="mt-3">
    <button class="btn btn-outline-primary" type="button" data-add="assignments" data-max="100">
        + Tambah Penugasan Guru
    </button>
</div>
