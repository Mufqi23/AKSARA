<?php

namespace App\Services\Dataset;

use App\Models\DatasetAuditLog;
use App\Models\User;
use App\Services\Dataset\Importers\ClassroomActivityImporter;
use App\Services\Dataset\Importers\MasterSchoolImporter;
use App\Services\Dataset\Importers\RosterScheduleImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DatasetImportRunner
{
    public const MAX_ROWS_PER_SHEET = 1500;

    public static function previewUploaded(User $user, string $module, UploadedFile $file, ?int $classId = null): array
    {
        if (! DatasetRegistry::canAccessModule($user, $module, 'import')) {
            abort(403);
        }

        $parsed = SpreadsheetCodec::readUploaded($file);
        if ($module === 'master_bundle' && $parsed['format'] !== 'xlsx') {
            throw ValidationException::withMessages([
                'file' => 'Impor Data Terpadu membutuhkan berkas buku kerja Excel (.xlsx) Multi-Sheet.',
            ]);
        }

        $normalizedPayload = self::extractNormalizedPayload($module, $parsed['sheets']);

        DB::beginTransaction();
        try {
            $summary = self::executeNormalized($user, $module, $normalizedPayload, $classId);
        } finally {
            DB::rollBack();
        }

        return [
            'module' => $module,
            'format' => $parsed['format'],
            'filename' => $file->getClientOriginalName(),
            'class_id' => $classId,
            'payload' => $normalizedPayload,
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'total_rows' => $summary['total_rows'],
            'errors' => $summary['errors'],
            'sheet_stats' => $summary['sheet_stats'],
            'sample_rows' => $summary['sample_rows'],
        ];
    }

    public static function commitPayload(User $user, array $previewState, ?string $ipAddress = null): array
    {
        $module = (string) ($previewState['module'] ?? '');
        $classId = isset($previewState['class_id']) ? (int) $previewState['class_id'] : null;
        $payload = (array) ($previewState['payload'] ?? []);

        if (! DatasetRegistry::canAccessModule($user, $module, 'import')) {
            abort(403);
        }

        $result = DB::transaction(function () use ($user, $module, $payload, $classId) {
            $summary = self::executeNormalized($user, $module, $payload, $classId);
            if (! empty($summary['errors'])) {
                throw ValidationException::withMessages([
                    'file' => implode(' ', array_slice($summary['errors'], 0, 5)),
                ]);
            }

            return $summary;
        });

        DatasetAuditLog::recordSafe([
            'user_id' => $user->id,
            'action' => 'import',
            'module' => $module,
            'format' => (string) ($previewState['format'] ?? 'xlsx'),
            'filename' => (string) ($previewState['filename'] ?? ''),
            'created_count' => $result['created'],
            'updated_count' => $result['updated'],
            'total_rows' => $result['total_rows'],
            'scope_label' => $classId ? 'Kelas ID #'.$classId : 'Sekolah',
            'ip_address' => $ipAddress,
        ]);

        return $result;
    }

    private static function extractNormalizedPayload(string $module, array $sheets): array
    {
        if ($module === 'master_bundle') {
            $expected = DatasetRegistry::masterBundleSheets();
            $out = [];
            $total = 0;
            foreach ($expected as $sheetName => $spec) {
                $matched = $sheets[$sheetName] ?? null;
                if (! $matched) {
                    foreach ($sheets as $k => $v) {
                        if (mb_strtolower(trim($k), 'UTF-8') === mb_strtolower($sheetName, 'UTF-8')) {
                            $matched = $v;
                            break;
                        }
                    }
                }
                $rows = $matched['rows'] ?? [];
                if (count($rows) > self::MAX_ROWS_PER_SHEET) {
                    throw ValidationException::withMessages([
                        'file' => "Lembar '{$sheetName}' melebihi batas maksimum ".self::MAX_ROWS_PER_SHEET.' baris.',
                    ]);
                }
                $total += count($rows);
                $out[$sheetName] = [
                    'headers' => $matched['headers'] ?? [],
                    'rows' => $rows,
                ];
            }
            if ($total === 0) {
                throw ValidationException::withMessages([
                    'file' => 'Berkas buku kerja kosong atau tidak memuat lembar kerja resmi (01_Tahun_Ajaran s.d. 07_Data_Siswa).',
                ]);
            }

            return $out;
        }

        $firstSheet = reset($sheets) ?: ['headers' => [], 'rows' => []];
        $headers = $firstSheet['headers'] ?? [];
        $rows = $firstSheet['rows'] ?? [];
        $meta = DatasetRegistry::modules()[$module] ?? null;
        if (! $meta) {
            throw ValidationException::withMessages(['module' => 'Modul dataset tidak dikenali.']);
        }

        $missing = array_diff($meta['required_headers'], $headers);
        if ($missing) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib belum ditemukan pada berkas: '.implode(', ', $missing).'. Unduh templat resmi terlebih dahulu.',
            ]);
        }
        if (! $rows) {
            throw ValidationException::withMessages([
                'file' => 'Berkas tidak berisi baris data untuk diimpor.',
            ]);
        }
        if (count($rows) > self::MAX_ROWS_PER_SHEET) {
            throw ValidationException::withMessages([
                'file' => 'Maksimal '.self::MAX_ROWS_PER_SHEET.' baris per satu kali unggahan.',
            ]);
        }

        return [$meta['title'] => ['headers' => $headers, 'rows' => $rows]];
    }

    private static function executeNormalized(User $user, string $module, array $payload, ?int $classId): array
    {
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalRows = 0;
        $allErrors = [];
        $sheetStats = [];
        $sampleRows = [];

        if ($module === 'master_bundle') {
            $specs = DatasetRegistry::masterBundleSheets();
            foreach ($specs as $sheetName => $spec) {
                $rows = $payload[$sheetName]['rows'] ?? [];
                $totalRows += count($rows);
                if (! $rows) {
                    $sheetStats[] = ['sheet' => $sheetName, 'rows' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];

                    continue;
                }
                $res = self::dispatchSingle($user, $spec['module'], $rows, null, $sheetName);
                $totalCreated += $res['created'];
                $totalUpdated += $res['updated'];
                $allErrors = array_merge($allErrors, $res['errors']);
                $sheetStats[] = [
                    'sheet' => $sheetName,
                    'rows' => count($rows),
                    'created' => $res['created'],
                    'updated' => $res['updated'],
                    'errors' => count($res['errors']),
                ];
                foreach (array_slice($rows, 0, 4) as $r) {
                    $sampleRows[] = ['sheet' => $sheetName, 'line' => $r['_row'] ?? '-', 'summary' => self::summarizeRow($r)];
                }
                if (! empty($res['errors'])) {
                    break;
                }
            }

            return [
                'created' => $totalCreated,
                'updated' => $totalUpdated,
                'total_rows' => $totalRows,
                'errors' => $allErrors,
                'sheet_stats' => $sheetStats,
                'sample_rows' => array_slice($sampleRows, 0, 25),
            ];
        }

        $sheetName = (string) array_key_first($payload);
        $rows = $payload[$sheetName]['rows'] ?? [];
        $totalRows = count($rows);
        $res = self::dispatchSingle($user, $module, $rows, $classId, $sheetName);

        foreach (array_slice($rows, 0, 20) as $r) {
            $sampleRows[] = ['sheet' => $sheetName, 'line' => $r['_row'] ?? '-', 'summary' => self::summarizeRow($r)];
        }

        return [
            'created' => $res['created'],
            'updated' => $res['updated'],
            'total_rows' => $totalRows,
            'errors' => $res['errors'],
            'sheet_stats' => [[
                'sheet' => $sheetName,
                'rows' => $totalRows,
                'created' => $res['created'],
                'updated' => $res['updated'],
                'errors' => count($res['errors']),
            ]],
            'sample_rows' => $sampleRows,
        ];
    }

    private static function dispatchSingle(User $user, string $module, array $rows, ?int $classId, string $label): array
    {
        return match ($module) {
            'academic_years' => MasterSchoolImporter::importAcademicYears($rows, $label),
            'programs_levels' => MasterSchoolImporter::importProgramsLevels($rows, $label),
            'blocks' => MasterSchoolImporter::importBlocks($rows, $label),
            'subjects' => MasterSchoolImporter::importSubjects($rows, $label),
            'announcements' => MasterSchoolImporter::importAnnouncements($rows, $user, $label),
            'teachers' => RosterScheduleImporter::importTeachers($rows, $label),
            'students' => RosterScheduleImporter::importStudents($rows, $label),
            'teacher_assignments' => RosterScheduleImporter::importTeacherAssignments($rows, $label),
            'schedules' => RosterScheduleImporter::importSchedules($rows, $label),
            'bundle_classes' => RosterScheduleImporter::importBundleClasses($rows, $label),
            'lessons' => ClassroomActivityImporter::importLessons($rows, $user, $classId, $label),
            'assignments' => ClassroomActivityImporter::importAssignments($rows, $user, $classId, $label),
            'grades' => ClassroomActivityImporter::importGrades($rows, $user, $classId, $label),
            'attendance' => ClassroomActivityImporter::importAttendance($rows, $user, $classId, $label),
            'quiz_questions' => ClassroomActivityImporter::importQuizQuestions($rows, $user, $classId, $label),
            default => ['created' => 0, 'updated' => 0, 'errors' => ["Modul '{$module}' tidak mendukung impor."]],
        };
    }

    private static function summarizeRow(array $row): string
    {
        $parts = [];
        foreach ($row as $k => $v) {
            if ($k === '_row' || $v === '') {
                continue;
            }
            $val = mb_strlen((string) $v) > 38 ? mb_substr((string) $v, 0, 35).'...' : (string) $v;
            $parts[] = "{$k}: {$val}";
            if (count($parts) === 4) {
                break;
            }
        }

        return implode(' · ', $parts);
    }
}

