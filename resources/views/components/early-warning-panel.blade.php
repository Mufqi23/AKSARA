@if(config('lms.ai_ews.enabled', true) && auth()->check())
@php
    $ewsViewer = auth()->user();
@endphp

@if($ewsViewer->role === 'student')
    @php
        $ewsAttRate = $rate ?? null;
        $ewsGradeAvg = $gradeAverage ?? null;
        $ewsOverdue = isset($studentAttention)
            ? $studentAttention->filter(fn ($a) => $a->due_at->isPast() && $a->latest_submission_status !== 'returned')->count()
            : 0;
        $ewsReturned = isset($studentAttention)
            ? $studentAttention->where('latest_submission_status', 'returned')->count()
            : 0;
        $ewsScoreData = \App\Services\Ai\EarlyWarningCalculator::scoreMetrics($ewsAttRate, 0, $ewsOverdue, 0, $ewsReturned, $ewsGradeAvg);
        $ewsNarrative = \App\Services\Ai\EarlyWarningNarrative::forStudent([
            'attendance_rate' => $ewsAttRate,
            'absent_count' => 0,
            'missing_overdue_count' => $ewsOverdue,
            'late_submission_count' => 0,
            'returned_count' => $ewsReturned,
            'grade_average' => $ewsGradeAvg,
            'tier' => $ewsScoreData['tier'],
        ]);
        $ewsBadgeClass = match($ewsScoreData['tier']) {
            'high' => 'ews-tier-high',
            'medium' => 'ews-tier-medium',
            default => 'ews-tier-low',
        };
    @endphp
    <section class="ews-widget-banner" aria-label="AI Asisten Peringatan Dini Akademik">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="ews-ai-chip">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>
                    Panduan Belajar AI
                </span>
                <span class="ews-tier-badge {{ $ewsBadgeClass }}">
                    Status: {{ $ewsScoreData['tier_label'] }} (Skor Risiko {{ $ewsScoreData['risk_score'] }}/100)
                </span>
            </div>
            <a href="/early-warning" class="btn btn-sm btn-outline-primary">Buka Analisis AI Lengkap →</a>
        </div>
        <p class="mb-0 small text-secondary">{{ $ewsNarrative['student_advice'] }}</p>
    </section>
@else
    @php
        $ewsTargetClassId = isset($ewsClassId) ? (int) $ewsClassId : null;
        $ewsCohort = \App\Services\Ai\EarlyWarningCalculator::analyzeCohort($ewsViewer, $ewsTargetClassId, allowExternalAi: false);
        $ewsCounts = $ewsCohort['counts'];
        $ewsExec = $ewsCohort['executive'];
        $ewsScope = isset($classroom) ? $classroom->label : ($ewsViewer->role === 'admin' ? 'Seluruh Sekolah' : 'Kelas Ajar Anda');
        $ewsLink = $ewsTargetClassId ? '/early-warning?class_id='.$ewsTargetClassId : '/early-warning';
    @endphp
    <section class="ews-widget-banner" aria-label="AI Deteksi Dini Siswa Berisiko">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="ews-ai-chip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"></path><path d="m16.2 7.8 2.9-2.9"></path><path d="M18 12h4"></path><path d="m16.2 16.2 2.9 2.9"></path><path d="M12 18v4"></path><path d="m4.9 19.1 2.9-2.9"></path><path d="M2 12h4"></path><path d="m4.9 4.9 2.9 2.9"></path></svg>
                        AI Peringatan Dini Siswa
                    </span>
                    <strong class="small text-primary">{{ $ewsExec['headline'] }} · {{ $ewsScope }}</strong>
                </div>
                <p class="mb-2 small text-secondary">{{ $ewsExec['narrative'] }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="ews-tier-badge ews-tier-high">Risiko Tinggi: {{ $ewsCounts['high'] }}</span>
                    <span class="ews-tier-badge ews-tier-medium">Risiko Sedang: {{ $ewsCounts['medium'] }}</span>
                    <span class="ews-tier-badge ews-tier-low">Terkendali: {{ $ewsCounts['low'] }}</span>
                </div>
            </div>
            <a href="{{ $ewsLink }}" class="btn btn-sm btn-primary align-self-center">
                Analisis & Rekomendasi AI →
            </a>
        </div>
    </section>
@endif
@endif
