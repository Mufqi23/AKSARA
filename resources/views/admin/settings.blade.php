@extends('layouts.app')
@section('title', 'Pengaturan Sistem')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">PENGATURAN SEKOLAH</div>
        <h1 class="mb-1">Pengaturan Kehadiran Siswa</h1>
        <p class="text-secondary mb-0">Atur batas toleransi keterlambatan siswa saat jam pelajaran dimulai.</p>
    </div>
</div>

<div class="card" style="max-width: 600px">
    <div class="card-body">
        <form method="post" action="/admin/settings">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="late_threshold">Batas Toleransi Keterlambatan (Menit)</label>
                <input class="form-control" id="late_threshold" type="number" name="late_threshold" value="{{ $threshold }}" min="1" max="120" required>
            </div>
            <p class="small-muted mb-4">
                Siswa yang hadir sebelum atau tepat pada batas menit ini akan dicatat sebagai <strong>Hadir</strong>, sedangkan setelah batas ini akan otomatis tercatat sebagai <strong>Terlambat</strong>. Pengaturan ini berlaku untuk setiap sesi absensi baru.
            </p>
            <button class="btn btn-primary" type="submit">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
@endsection
