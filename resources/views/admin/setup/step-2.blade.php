@php
    $emptyLevel = ['year_level_id' => null, 'name' => '', 'level' => '', 'blocks' => [['name' => '']]];
    $legacyStructure = [['program_id' => $draft['program_id'] ?? null, 'year_levels' => [['year_level_id' => $draft['year_level_id'] ?? null, 'name' => '', 'level' => '', 'blocks' => $draft['blocks'] ?? [['name' => '']]]]]];
    $structureRows = old('structures', $draft['structures'] ?? $legacyStructure);
    $structureRows = is_array($structureRows) && $structureRows ? array_values($structureRows) : $legacyStructure;
@endphp
<p class="text-secondary">Tentukan struktur akademik sekolah: pilih Jurusan, kemudian tambahkan Tingkat Kelas dan nama Rombel di bawahnya.</p>
<div class="alert alert-info">Nama rombel kelas dapat digunakan kembali pada jurusan atau tingkat kelas yang berbeda. Dalam satu jurusan dan tingkat kelas yang sama, nama rombel harus unik.</div>

@if($programs->isEmpty())
    <div class="alert alert-warning">Tambahkan setidaknya satu <a href="/admin/manage/programs/create" class="fw-bold">Jurusan</a> sebelum melanjutkan.</div>
@endif

<input type="hidden" name="expected_structure_count" value="{{ count($structureRows) }}" data-expected-structures>
<div data-structures>
    @foreach($structureRows as $programIndex => $structure)
        @include('admin.setup.program-row', ['programIndex' => $programIndex, 'structure' => is_array($structure) ? $structure : ['program_id' => null, 'year_levels' => [$emptyLevel]]])
    @endforeach
</div>

<template data-program-template>@include('admin.setup.program-row', ['programIndex' => '__program__', 'structure' => ['program_id' => null, 'year_levels' => [$emptyLevel]]])</template>
<template data-year-level-template>@include('admin.setup.year-level-row', ['programIndex' => '__program__', 'levelIndex' => '__level__', 'levelRow' => $emptyLevel])</template>
<template data-block-template>@include('admin.setup.block-row', ['programIndex' => '__program__', 'levelIndex' => '__level__', 'blockIndex' => '__block__', 'blockName' => ''])</template>

<div class="mt-3">
    <button type="button" class="btn btn-outline-primary" data-add-program>+ Tambah Jurusan Lain</button>
</div>
<div class="small text-secondary mt-3" aria-live="polite" data-structure-summary></div>
