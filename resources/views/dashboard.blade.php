@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
@include('dashboard.welcome-stats')
@include('components.early-warning-panel')

@if($user->role === 'student')
    @include('dashboard.student')
@else
    @if($needsAttention->isNotEmpty())
        <div class="attention-card">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Perlu Perhatian Segera <span class="visually-hidden">Needs Attention</span>
            </h2>
            @php
                $attentionLabels = [
                    'Complete academic setup' => ['Lengkapi pengaturan awal tahun ajaran', 'Pilih tahun ajaran, jurusan, dan tingkat kelas sebelum membuat kelas pelajaran.'],
                    'Blocks without subjects' => ['Rombel belum memiliki mata pelajaran', 'Tugaskan mata pelajaran dan guru pengajar untuk rombel ini.'],
                    'Classes with unavailable teachers' => ['Kelas dengan akun guru belum aktif', 'Periksa akun guru yang diarsipkan, belum aktif, atau ditangguhkan.'],
                    'Students missing required information' => ['Data siswa belum lengkap', 'Lengkapi profil siswa, NIS/NISN, atau penempatan rombel siswa.'],
                    'Classes missing schedules' => ['Kelas belum memiliki jadwal pelajaran', 'Tambahkan hari, jam pelajaran, dan ruang kelas untuk setiap mata pelajaran.'],
                ];
            @endphp
            <div class="row g-2">
                @foreach($needsAttention as $item)
                    @php
                        [$itemLabel, $itemDetail] = $attentionLabels[$item['label']] ?? [$item['label'], $item['detail']];
                    @endphp
                    <div class="col-md-6">
                        <a href="{{ $item['url'] }}" class="attention-item">
                            <div>
                                <strong>
                                    {{ $itemLabel }}
                                    @if($itemLabel !== $item['label'])
                                        <span class="visually-hidden">{{ $item['label'] }}</span>
                                    @endif
                                </strong>
                                <div class="detail">{{ $itemDetail }}</div>
                            </div>
                            @if($item['count'] !== null)
                                <span class="badge text-bg-warning">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            @include('dashboard.classes')
            @include('dashboard.announcements')

            <section class="widget-card mb-4">
                <div class="widget-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="widget-head-icon ic-blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line></svg>
                        </span>
                        <div>
                            <h2 class="widget-card-title">Pratinjau Jadwal Pelajaran</h2>
                            <span class="widget-card-sub">Daftar sesi kegiatan belajar mingguan</span>
                        </div>
                    </div>
                </div>
                <div class="widget-card-body">
                    @include('schedule.table', ['schedules' => $schedules])
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            @include('dashboard.widgets-right')
        </div>
    </div>
@endif
@endsection
