<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="eyebrow">PERSENTASE KEHADIRAN TERFILTER</div>
                <div class="stat-value text-primary mb-2">{{ $rate === null ? 'N/A' : $rate.'%' }}</div>
                <p class="small-muted">
                    Rumus: (Hadir + Terlambat) ÷ (Hadir + Terlambat + Alpa). Sesi izin/sakit tidak mengurangi nilai persentase kehadiran.
                </p>

                <div class="p-3 bg-body rounded border mt-3">
                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa', 'excused' => 'Izin / Sakit'] as $status => $sLabel)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom last-border-0">
                            <span>{{ $sLabel }}</span>
                            <strong class="badge {{ $status === 'present' ? 'text-bg-success' : ($status === 'late' || $status === 'excused' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                {{ $all->where('status', $status)->count() }}
                            </strong>
                        </div>
                    @endforeach
                </div>

                @if(!empty($summaries))
                    <div class="mt-3">
                        @foreach($summaries as $label => $value)
                            <div class="list-line small d-flex justify-content-between">
                                <span>{{ $label }}</span>
                                <strong>{{ $value === null ? 'N/A' : $value.'%' }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="h5 mb-0">Kalender Presensi · {{ $month->translatedFormat('F Y') }}</h2>
            </div>
            <div class="card-body">
                <p class="small-muted">H = Hadir · T = Terlambat · A = Alpa · I = Izin / Sakit.</p>
                <div class="calendar">
                    @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                        <div class="small-muted fw-bold text-center">{{ $day }}</div>
                    @endforeach

                    @for($i = 1; $i < $month->dayOfWeekIso; $i++)
                        <div></div>
                    @endfor

                    @for($day = 1; $day <= $month->daysInMonth; $day++)
                        @php($date = $month->copy()->day($day)->format('Y-m-d'))
                        <div class="calendar-day">
                            <strong>{{ $day }}</strong>
                            @foreach(($calendar[$date] ?? collect())->groupBy('status') as $status => $group)
                                @php($stChar = match($status) { 'present' => 'H', 'late' => 'T', 'absent' => 'A', 'excused' => 'I', default => strtoupper(substr($status, 0, 1)) })
                                <span class="badge {{ $status === 'present' ? 'text-bg-success' : ($status === 'late' || $status === 'excused' ? 'text-bg-warning' : 'text-bg-danger') }} d-block mb-1 text-center" title="{{ ucfirst($status) }}">
                                    {{ $stChar }} {{ $group->count() }}
                                </span>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

