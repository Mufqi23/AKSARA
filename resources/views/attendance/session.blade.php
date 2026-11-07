@extends('layouts.app')
@section('title', 'Pencatatan Presensi Kelas')

@section('content')
<div class="mb-3">
    <a href="/classes/{{ $session->teacher_assignment_id }}#monitoring" class="text-secondary small fw-semibold">
        ← Kembali ke {{ $session->classroom->label }}
    </a>
</div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">ABSENSI &amp; KEHADIRAN SISWA</div>
        <h1 class="mb-1">Kehadiran Pertemuan · {{ $session->date->format('d M Y') }}</h1>
        <p class="text-secondary mb-0">
            Waktu: <strong>{{ $session->start_time }} – {{ $session->end_time }}</strong> · Batas Terlambat: <strong>{{ $session->late_threshold }} menit</strong>.
            Guru dapat mengubah data dalam 7 hari. Perubahan setelah 7 hari dilakukan melalui admin sekolah.
        </p>
    </div>
</div>

@php($editable = auth()->user()->role === 'admin' || !$session->date->lt(today()->subDays(7)))
@if(!$editable)
    <div class="alert alert-info mb-4">
        Catatan kehadiran pertemuan ini tidak dapat diubah lagi karena sudah melewati 7 hari. Hubungi admin sekolah jika diperlukan perbaikan data. <span class="visually-hidden">This session is read only</span>
    </div>
@endif

@if($enrollments->isNotEmpty())
    <form method="post" action="/attendance/sessions/{{ $session->id }}/bulk-records" data-bulk-attendance data-late-threshold="{{ $session->late_threshold }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="expected_count" value="{{ $enrollments->count() }}">

        @if($editable)
            <div class="d-flex gap-2 flex-wrap align-items-center mb-4 p-3 bg-body rounded border">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-mark-all-present>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Tandai Semua Hadir <span class="visually-hidden">Mark All Present</span>
                </button>
                <span class="small-muted">Ubah status siswa yang terlambat, izin, atau alpa, lalu klik Simpan.</span>
            </div>
        @endif

        @foreach($enrollments as $enrollment)
            @php($record = $recordsByStudent->get($enrollment->student_id))
            @php($key = 'records.'.$enrollment->student_id)
            <div class="card mb-3 bulk-attendance-row">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h6 mb-0 fw-bold">
                            {{ $enrollment->student->user?->name ?? 'Siswa Dinonaktifkan' }}
                            <span class="small-muted ms-2">NIS: {{ $enrollment->student->student_number }}</span>
                        </h3>
                    </div>

                    <fieldset @disabled(!$editable)>
                        <input type="hidden" name="records[{{ $enrollment->student_id }}][student_id]" value="{{ $enrollment->student_id }}">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Status Kehadiran</label>
                                <select class="form-select @error($key.'.status') is-invalid @enderror" name="records[{{ $enrollment->student_id }}][status]" data-attendance-status required>
                                    <option value="">— Pilih Status —</option>
                                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa / Tanpa Keterangan', 'excused' => 'Izin / Sakit'] as $status => $label)
                                        <option value="{{ $status }}" @selected(old($key.'.status', $record?->status) === $status)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error($key.'.status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">Menit Terlambat</label>
                                <input class="form-control @error($key.'.minutes_late') is-invalid @enderror" type="number" min="0" max="1440" name="records[{{ $enrollment->student_id }}][minutes_late]" value="{{ old($key.'.minutes_late', $record?->minutes_late) }}" placeholder="0" data-attendance-minutes>
                                @error($key.'.minutes_late')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Catatan / Keterangan</label>
                                <input class="form-control @error($key.'.remarks') is-invalid @enderror" name="records[{{ $enrollment->student_id }}][remarks]" maxlength="2000" value="{{ old($key.'.remarks', $record?->remarks) }}" placeholder="Keterangan tambahan (opsional)">
                                @error($key.'.remarks')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            @if(auth()->user()->role === 'admin')
                                <div class="col-12">
                                    <label class="form-label">Alasan Koreksi Administrator (Wajib diisi)</label>
                                    <input class="form-control @error($key.'.reason') is-invalid @enderror" name="records[{{ $enrollment->student_id }}][reason]" value="{{ old($key.'.reason') }}" maxlength="2000" placeholder="Alasan perubahan data presensi" required>
                                    @error($key.'.reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            @else
                                <div class="col-12">
                                    <details>
                                        <summary class="small text-secondary" style="cursor: pointer;">Catatan koreksi (opsional)</summary>
                                        <label class="form-label w-100 mt-2">
                                            Alasan Perubahan
                                            <input class="form-control" name="records[{{ $enrollment->student_id }}][reason]" value="{{ old($key.'.reason') }}" maxlength="2000" placeholder="Catatan bila ada perbaikan presensi">
                                        </label>
                                    </details>
                                </div>
                            @endif
                        </div>
                    </fieldset>

                    @if(config('lms.show_advanced_features') && $record && $record->logs->isNotEmpty())
                        @php($logStatusMap = ['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa', 'excused' => 'Izin/Sakit'])
                        <details class="mt-3 p-2 bg-body rounded border">
                            <summary class="small fw-semibold text-primary" style="cursor: pointer;">Riwayat Perubahan ({{ $record->logs->count() }})</summary>
                            @foreach($record->logs->sortByDesc('id') as $log)
                                @php($beforeLabel = $logStatusMap[$log->before['status'] ?? ''] ?? ($log->before['status'] ?? 'Belum Dicatat'))
                                @php($afterLabel = $logStatusMap[$log->after['status'] ?? ''] ?? ($log->after['status'] ?? '-'))
                                <div class="list-line small">
                                    <strong>{{ $log->created_at->format('d M Y, H:i') }} · {{ $log->user?->name ?? 'Pengguna' }}</strong><br>
                                    Status: {{ $beforeLabel }} → {{ $afterLabel }} · Alasan: {{ $log->reason }}
                                </div>
                            @endforeach
                        </details>
                    @endif
                </div>
            </div>
        @endforeach

        @if($editable)
            <div class="mt-4">
                <button class="btn btn-primary btn-lg" type="submit">
                    Simpan Kehadiran <span class="visually-hidden">Save Attendance</span>
                </button>
            </div>
        @endif
    </form>

    @if(config('lms.show_advanced_features'))
        @foreach($recordsByStudent as $record)
            @if($record->excuse)
                <div class="alert alert-info mt-3">
                    <strong>{{ $record->student->user?->name ?? 'Siswa' }} · Pengajuan Izin/Sakit: {{ match($record->excuse->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu Verifikasi' } }}</strong>
                    <p class="mb-2">{{ $record->excuse->reason }}</p>
                    @if($record->excuse->status === 'pending' && $editable)
                        <form method="post" action="/excuses/{{ $record->excuse->id }}" class="d-flex gap-2 flex-wrap">
                            @csrf
                            @method('PUT')
                            <select class="form-select w-auto" name="status">
                                <option value="approved">Setujui → Status Izin</option>
                                <option value="rejected">Tolak → Status Alpa</option>
                            </select>
                            <input class="form-control w-auto" name="review_note" placeholder="Catatan persetujuan" required>
                            <button class="btn btn-primary" type="submit">Simpan Verifikasi</button>
                        </form>
                    @else
                        <p class="mb-0 small text-muted">{{ $record->excuse->review_note }}</p>
                    @endif
                </div>
            @endif
        @endforeach
    @endif
@else
    <div class="empty">Belum ada siswa yang terdaftar di kelas ini untuk dicatat kehadirannya.</div>
@endif
@endsection
