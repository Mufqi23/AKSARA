@php
    $days = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $englishDays = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
@endphp
<div class="row g-3" data-weekly-schedule>
    @forelse($schedules->groupBy('day') as $day => $dailySchedules)
        <section class="col-md-6 col-xl-4" aria-label="Jadwal hari {{ $days[$day] ?? 'Lainnya' }}">
            <div class="border rounded p-3 h-100 bg-surface">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h3 class="h6 fw-bold mb-0 text-primary">
                        {{ $days[$day] ?? 'Hari '.$day }}
                        <span class="visually-hidden">{{ $englishDays[$day] ?? '' }}</span>
                    </h3>
                    <span class="badge badge-soft">{{ $dailySchedules->count() }} Kelas</span>
                </div>
                @foreach($dailySchedules as $schedule)
                    <div class="list-line pt-2">
                        <div class="fw-semibold text-main mb-1 d-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-secondary"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <time>
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                <span class="visually-hidden">{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}</span>
                            </time> – 
                            <time>
                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                <span class="visually-hidden">{{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}</span>
                            </time> WIB
                        </div>
                        <span class="class-code mb-2">{{ $schedule->classroom->subject->code }}</span>
                        <h4 class="h6 mb-1">
                            <a href="/classes/{{ $schedule->teacher_assignment_id }}" class="text-main fw-bold">{{ $schedule->classroom->subject->name }}</a>
                        </h4>
                        <div class="small-muted">{{ $schedule->classroom->teacher->user?->name ?? 'Guru Belum Ditentukan' }}</div>
                        <div class="small-muted">{{ $schedule->classroom->block->program->code }} · Rombel {{ $schedule->classroom->block->name }}</div>
                        <div class="mt-2 small d-inline-block bg-body px-2 py-1 rounded border">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9v.01"></path><path d="M9 12v.01"></path><path d="M9 15v.01"></path><path d="M9 18v.01"></path></svg>Ruang: <strong>{{ filled($schedule->room) ? $schedule->room : 'Menunggu Pengumuman' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="col-12">
            <div class="empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <p class="mb-0 fw-semibold">Belum ada jadwal pelajaran mingguan yang diterbitkan.{{ auth()->user()->role === 'student' ? ' Administrator atau guru akan mengumumkan jadwal kelas Anda.' : '' }} <span class="visually-hidden">No scheduled classes yet.</span></p>
                @if(auth()->user()->role === 'admin')
                    <div class="mt-3">
                        <a class="btn btn-primary btn-sm" href="/admin/manage/schedules/create">+ Tambah Jadwal Pelajaran</a>
                    </div>
                @endif
            </div>
        </div>
    @endforelse
</div>
