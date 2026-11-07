@extends('layouts.app')
@section('title', 'Jadwal Pelajaran Mingguan')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <div class="eyebrow">JADWAL AKADEMIK SEKOLAH</div>
        <h1 class="mb-1">Jadwal Pelajaran Mingguan</h1>
        <p class="text-secondary mb-0">Daftar jadwal mata pelajaran berdasarkan hari, jam pelajaran, guru pengajar, dan ruang kelas.</p>
    </div>
    @if(auth()->user()->role === 'admin')
        <a href="/admin/manage/schedules" class="btn btn-outline-primary">
            + Kelola Seluruh Jadwal
        </a>
    @endif
</div>

<div class="card">
    <div class="card-body">
        @include('schedule.table')
    </div>
</div>
@endsection
