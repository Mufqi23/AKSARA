<?php

namespace App\Services\Ai;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EarlyWarningNarrative
{
    public static function forStudent(array $metrics): array
    {
        $factors = [];
        $att = $metrics['attendance_rate'];
        $absents = $metrics['absent_count'];
        $missing = $metrics['missing_overdue_count'];
        $lateSub = $metrics['late_submission_count'];
        $returned = $metrics['returned_count'];
        $grade = $metrics['grade_average'];
        $tier = $metrics['tier'];

        if ($att !== null && $att < 75) {
            $factors[] = "Kehadiran kritis ({$att}%, di bawah batas minimum sekolah 75%)";
        } elseif ($att !== null && $att < 85) {
            $factors[] = "Kehadiran perlu perhatian ({$att}%)";
        }

        if ($absents >= 2) {
            $factors[] = "Tercatat {$absents} kali Alpa tanpa keterangan";
        } elseif ($absents === 1) {
            $factors[] = 'Tercatat 1 kali Alpa tanpa keterangan';
        }

        if ($missing >= 2) {
            $factors[] = "{$missing} tugas wajib telah melewati tenggat tanpa pengumpulan";
        } elseif ($missing === 1) {
            $factors[] = '1 tugas wajib melewati tenggat belum dikumpulkan';
        }

        if ($returned > 0) {
            $factors[] = "{$returned} tugas dikembalikan guru dan menunggu perbaikan";
        } elseif ($lateSub > 0) {
            $factors[] = "{$lateSub} tugas dikumpulkan melewati batas waktu";
        }

        if ($grade !== null && $grade < 55) {
            $factors[] = "Rata-rata capaian nilai sangat rendah ({$grade}%)";
        } elseif ($grade !== null && $grade < 70) {
            $factors[] = "Rata-rata nilai evaluasi di bawah KKM optimal ({$grade}%)";
        }

        if (empty($factors)) {
            $factors[] = 'Kehadiran, kedisiplinan tugas, dan nilai evaluasi berjalan konsisten';
        }

        if ($tier === 'high') {
            $summary = 'Terdeteksi risiko tinggi ketertinggalan belajar: '.implode('; ', $factors).'.';
            $recommendation = 'Jadwalkan pendampingan bersama guru mata pelajaran atau wali kelas, konfirmasi kendala kehadiran siswa, dan berikan penugasan susulan atau remedial terarah.';
            $studentAdvice = 'Segera temui guru mata pelajaran untuk konsultasi kehadiran dan selesaikan tugas yang tertunda agar tidak menghambat ketuntasan nilai rapor.';
        } elseif ($tier === 'medium') {
            $summary = 'Terdeteksi gejala awal penurunan performa belajar: '.implode('; ', $factors).'.';
            $recommendation = 'Berikan pengingat kehadiran sebelum penilaian akhir serta umpan balik pada materi atau tugas yang masih perlu diperbaiki siswa.';
            $studentAdvice = 'Tingkatkan kehadiran pada jam pelajaran berikutnya dan periksa kembali tenggat pengumpulan tugas serta catatan revisi dari guru.';
        } else {
            $summary = 'Progres kegiatan belajar berjalan stabil dan terkendali pada indikator presensi maupun penugasan.';
            $recommendation = 'Pertahankan pemantauan berkala dan berikan apresiasi atas kedisiplinan belajar siswa.';
            $studentAdvice = 'Pertahankan kedisiplinan hadir di sekolah dan ketepatan waktu pengumpulan tugas hingga akhir semester.';
        }

        return [
            'factors' => $factors,
            'summary' => $summary,
            'recommendation' => $recommendation,
            'student_advice' => $studentAdvice,
        ];
    }

    public static function cohortExecutiveSummary(array $counts, Collection $highRiskStudents, ?string $scopeLabel = null, bool $allowExternalAi = true): array
    {
        $scope = $scopeLabel ?: 'Seluruh Kelas yang Dipantau';
        $localNarrative = self::buildLocalCohortText($counts, $highRiskStudents, $scope);

        $provider = config('lms.ai_ews.provider', 'local');
        $apiKey = trim((string) config('lms.ai_ews.gemini_api_key', ''));

        if ($allowExternalAi && $provider === 'gemini' && $apiKey !== '') {
            $cacheKey = 'ai_ews_summary_'.md5(json_encode([$counts, $scope, $highRiskStudents->pluck('student_number')->take(5)]));
            $geminiText = Cache::remember($cacheKey, 600, function () use ($counts, $scope, $apiKey) {
                return self::requestGeminiSummary($counts, $scope, $apiKey);
            });

            if ($geminiText) {
                return [
                    'engine' => 'Google Gemini AI · Analisis Akademik AKSARA',
                    'mode' => 'gemini',
                    'headline' => $localNarrative['headline'],
                    'narrative' => $geminiText,
                    'action_plan' => $localNarrative['action_plan'],
                ];
            }
        }

        return [
            'engine' => 'Mesin Diagnostik Cerdas AKSARA',
            'mode' => 'local',
            'headline' => $localNarrative['headline'],
            'narrative' => $localNarrative['narrative'],
            'action_plan' => $localNarrative['action_plan'],
        ];
    }

    private static function buildLocalCohortText(array $counts, Collection $highRiskStudents, string $scope): array
    {
        $total = $counts['total'];
        $high = $counts['high'];
        $med = $counts['medium'];
        $low = $counts['low'];

        if ($total === 0) {
            return [
                'headline' => 'Belum Ada Data Siswa pada Cakupan Ini',
                'narrative' => "Sistem AI Peringatan Dini belum menemukan siswa terdaftar pada cakupan {$scope}.",
                'action_plan' => 'Pastikan siswa telah ditempatkan pada rombel dan kelas pelajaran aktif.',
            ];
        }

        if ($high > 0) {
            $names = $highRiskStudents->take(3)->pluck('name')->implode(', ');
            $more = $high > 3 ? ' dan '.($high - 3).' siswa lainnya' : '';
            $headline = "Perhatian Segera: {$high} Siswa Berisiko Tinggi Terdeteksi";
            $narrative = "Dari total {$total} siswa pada {$scope}, analisis AI mendeteksi {$high} siswa berstatus Risiko Tinggi ({$names}{$more}) dan {$med} siswa berstatus Risiko Sedang, sementara {$low} siswa berada pada kategori Terkendali.";
            $action = 'Prioritaskan pendampingan belajar minggu ini bersama guru pengajar/wali kelas bagi siswa berstatus Risiko Tinggi, terutama yang kehadirannya di bawah 75% atau memiliki tugas lewat tenggat.';
        } elseif ($med > 0) {
            $headline = "Pemantauan Preventif: {$med} Siswa Berisiko Sedang";
            $narrative = "Tidak ditemukan siswa berstatus Risiko Tinggi dari {$total} siswa pada {$scope}. Namun terdapat {$med} siswa pada kategori Risiko Sedang yang memerlukan pengingat kehadiran atau penyelesaian revisi tugas, sedangkan {$low} siswa berada pada kondisi Terkendali.";
            $action = 'Kirimkan pengingat melalui fitur Pengumuman Kelas atau berikan umpan balik langsung pada pengumpulan tugas siswa yang berstatus Risiko Sedang.';
        } else {
            $headline = "Kondisi Akademik Optimal: Seluruh {$total} Siswa Terkendali";
            $narrative = "Seluruh {$total} siswa yang terdaftar pada {$scope} menunjukkan tingkat kehadiran yang sehat (≥ 85%), pengumpulan tugas tepat waktu, serta rata-rata nilai di atas KKM.";
            $action = 'Pertahankan ritme kegiatan belajar mengajar dan lanjutkan pencatatan presensi serta penilaian tugas secara berkala.';
        }

        return ['headline' => $headline, 'narrative' => $narrative, 'action_plan' => $action];
    }

    private static function requestGeminiSummary(array $counts, string $scope, string $apiKey): ?string
    {
        try {
            $model = config('lms.ai_ews.gemini_model', 'gemini-2.0-flash');
            $prompt = "Anda adalah asisten akademik untuk guru sekolah di Indonesia pada sistem AKSARA. Buat ringkasan singkat (maksimal 3 kalimat bahasa Indonesia formal, jelas, dan sopan) berdasarkan data objektif berikut pada cakupan {$scope}: Total siswa={$counts['total']}, Risiko Tinggi={$counts['high']}, Risiko Sedang={$counts['medium']}, Terkendali={$counts['low']}. Jangan mengarang angka di luar data tersebut.";
            $response = Http::timeout(4)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);
            if ($response->successful()) {
                return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));
            }
        } catch (\Throwable) {
            // Automatic fallback to deterministic local diagnostic engine
        }

        return null;
    }
}
