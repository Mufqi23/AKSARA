<div class="lw-pane" data-lw-pane="monitoring">
    <section id="monitoring" class="lw-category-shell lw-theme-presensi mb-4">
        <div class="lw-category-head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="lw-head-badge lw-badge-presensi" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
                <div>
                    <h2 class="h5 mb-0 fw-bold">Kehadiran Pertemuan &amp; Buku Nilai Kelas</h2>
                    <p class="small text-secondary mb-0">Rekap kehadiran harian siswa dan pencatatan nilai mata pelajaran.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-primary" href="/classes/{{ $classroom->id }}/gradebook">
                    Lihat Buku Nilai Kelas <span class="visually-hidden">View Grades</span>
                </a>
                <a class="btn btn-sm btn-outline-primary" href="/reports/attendance?class_id={{ $classroom->id }}">
                    Rekap Kehadiran
                </a>
                @if($student)
                    <a class="btn btn-sm btn-outline-secondary" href="/schedule">Jadwal Pelajaran Saya <span class="visually-hidden">My Schedule</span></a>
                @endif
            </div>
        </div>

        <div id="attendance" class="lw-category-body">
            @if($student)
                <div class="p-3 rounded border bg-body">
                    <p class="mb-2 text-secondary small">Gunakan tombol <strong>Lihat Buku Nilai Kelas</strong> untuk melihat rincian nilai tugas Anda atau <strong>Rekap Kehadiran</strong> untuk melihat catatan kehadiran pada mata pelajaran ini.</p>
                </div>
            @else
                @if($teaching)
                    <form id="take-attendance" method="post" action="/classes/{{ $classroom->id }}/attendance" class="row g-3 mb-4 p-3 bg-body rounded border">
                        @csrf
                        <div class="col-12"><h3 class="h6 fw-bold mb-0">Buat Sesi Kehadiran Pertemuan Baru</h3></div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Pertemuan</label>
                            <input class="form-control @error('date') is-invalid @enderror" name="date" type="date" value="{{ old('date', today()->format('Y-m-d')) }}" min="{{ today()->subDays(7)->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required>
                            @error('date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Waktu Mulai</label>
                            <input class="form-control @error('start_time') is-invalid @enderror" name="start_time" type="time" value="{{ old('start_time') }}" required>
                            @error('start_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Waktu Selesai</label>
                            <input class="form-control @error('end_time') is-invalid @enderror" name="end_time" type="time" value="{{ old('end_time') }}" required>
                            @error('end_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 align-self-end">
                            <button class="btn btn-primary w-100" type="submit">+ Buka Sesi Kehadiran</button>
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal Pertemuan</th>
                                <th>Jam Pelajaran</th>
                                <th>Status &amp; Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td><strong>{{ $session->date->format('d M Y') }}</strong></td>
                                    <td>{{ $session->start_time }} &ndash; {{ $session->end_time }}</td>
                                    <td>
                                        @if($teaching && !$session->date->lt(today()->subDays(7)))
                                            <a href="/attendance/sessions/{{ $session->id }}" class="btn btn-sm btn-primary">
                                                Catat Kehadiran &rarr;
                                            </a>
                                        @else
                                            <a href="/reports/attendance?class_id={{ $classroom->id }}&amp;from={{ $session->date->format('Y-m-d') }}&amp;to={{ $session->date->format('Y-m-d') }}" class="btn btn-sm btn-outline-secondary">
                                                Lihat Catatan &rarr;
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada sesi kehadiran pertemuan yang dibuat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>

@unless($student)
    <div class="lw-pane" data-lw-pane="students">
        <section id="students" class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="h5 mb-0">Daftar Siswa Kelas Ini ({{ $students->count() }})</h2>
                @if(config('lms.show_advanced_features'))
                    <a href="/reports/students?class_id={{ $classroom->id }}" class="btn btn-sm btn-outline-secondary">Unduh Daftar Siswa &rarr; <span class="visually-hidden">Export roster</span></a>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nomor Induk / NIS</th>
                                <th>Nama Siswa</th>
                                <th>Alamat Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $person)
                                <tr>
                                    <td><code>{{ $person->student_number }}</code></td>
                                    <td class="fw-semibold">{{ $person->user?->name ?? '—' }}</td>
                                    <td>{{ $person->user?->email ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Belum ada siswa yang terdaftar di kelas ini. <span class="visually-hidden">No enrolled students.</span>
                                        @if(auth()->user()->role === 'admin')
                                            <a href="/admin/manage/enrollments/create" class="ms-1 fw-bold">Daftarkan siswa sekarang</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endunless
