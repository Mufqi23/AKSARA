@php
    $printTitle = match(strtolower((string) $title)) {
        'attendance' => 'Laporan Kehadiran Siswa',
        'grades' => 'Laporan Rekap Nilai Siswa',
        'students' => 'Daftar Siswa Terdaftar',
        'enrollments' => 'Data Pendaftaran Kelas',
        default => 'Laporan '.ucfirst($title),
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $printTitle }} · AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #0f172a; line-height: 1.5; margin: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; color: #4f46e5; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-weight: bold; color: #334155; }
        tr:nth-child(even) td { background: #fafafa; }
        footer { margin-top: 30px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <h1>AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu) · {{ $printTitle }}</h1>
    <div class="meta">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} · Rekapitulasi Data Akademik Resmi Sekolah</div>
    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ match($header) { 'Student' => 'Siswa', 'Student ID' => 'NIS', 'Name' => 'Nama Siswa', 'Email' => 'Email', 'Program' => 'Jurusan', 'Year Level' => 'Tingkat Kelas', 'Block' => 'Rombel', 'Subject' => 'Mata Pelajaran', 'Teacher' => 'Guru Pengajar', 'Class' => 'Kelas', 'Date' => 'Tanggal', 'Time' => 'Waktu', 'Status' => 'Status', 'Minutes Late' => 'Menit Terlambat', 'Remarks' => 'Keterangan', 'Assessment' => 'Tugas/Kuis', 'Score' => 'Nilai', 'Total' => 'Total', 'Percentage' => 'Persentase', default => $header } }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ match(strtolower((string)$value)) { 'present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpa', 'excused' => 'Izin / Sakit', default => $value } }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data pada laporan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <footer>AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu) · Dokumen Resmi Akademik Sekolah</footer>
</body>
</html>
