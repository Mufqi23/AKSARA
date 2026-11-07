<section class="card" aria-label="Riwayat Audit Ekspor dan Impor Dataset">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Riwayat Aktivitas Ekspor &amp; Impor Dataset</h2>
            <small class="text-secondary">Seluruh proses sinkronisasi data tercatat otomatis demi keamanan dan akuntabilitas sekolah.</small>
        </div>
        <span class="badge badge-soft">12 Aktivitas Terbaru</span>
    </div>
    <div class="card-body p-0">
        @if($recentLogs->isEmpty())
            <p class="text-secondary small p-4 mb-0 text-center">
                Belum ada riwayat aktivitas ekspor atau impor dataset yang tercatat.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aksi</th>
                            <th>Jenis Data</th>
                            <th>Format</th>
                            <th>Ringkasan Baris</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                            <tr>
                                <td class="small text-secondary">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="fw-semibold small">{{ $log->user?->name ?? 'Pengguna' }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $log->scope_label ?? 'Sekolah' }}</div>
                                </td>
                                <td>
                                    @if($log->action === 'import')
                                        <span class="badge text-bg-success">Impor</span>
                                    @else
                                        <span class="badge badge-soft">Ekspor</span>
                                    @endif
                                </td>
                                <td class="small fw-semibold">{{ $moduleTitles[$log->module] ?? $log->module }}</td>
                                <td><code class="small text-uppercase">{{ $log->format }}</code></td>
                                <td class="small">
                                    @if($log->action === 'import')
                                        <strong>{{ $log->created_count }}</strong> baru · <strong>{{ $log->updated_count }}</strong> diperbarui
                                        <span class="text-muted">(dari {{ $log->total_rows }} baris)</span>
                                    @else
                                        <strong>{{ $log->total_rows }}</strong> baris diekspor
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

