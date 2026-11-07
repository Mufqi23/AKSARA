<?php

namespace App\Services\Dataset\Importers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Block;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Support\Facades\DB;

class MasterSchoolImporter
{
    use ImportHelpers;

    public static function importAcademicYears(array $rows, string $sheetLabel = 'Tahun Ajaran'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $name = trim((string) ($row['nama_tahun_ajaran'] ?? ''));
            $start = self::parseDateValue((string) ($row['tanggal_mulai'] ?? ''));
            $end = self::parseDateValue((string) ($row['tanggal_selesai'] ?? ''));

            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = "{$sheetLabel} baris {$line}: Nama tahun ajaran wajib diisi (maks. 120 karakter).";

                continue;
            }
            if (! $start || ! $end || $end < $start) {
                $errors[] = "{$sheetLabel} baris {$line}: Tanggal mulai dan tanggal selesai harus valid (YYYY-MM-DD) dan tanggal selesai tidak boleh sebelum tanggal mulai.";

                continue;
            }

            $existing = self::resolveAcademicYear($name);
            if ($existing) {
                $existing->update(['starts_on' => $start, 'ends_on' => $end]);
                $updated++;
            } else {
                AcademicYear::create(['name' => $name, 'starts_on' => $start, 'ends_on' => $end]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importProgramsLevels(array $rows, string $sheetLabel = 'Jurusan & Tingkat'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $code = trim((string) ($row['kode_jurusan'] ?? ''));
            $progName = trim((string) ($row['nama_jurusan'] ?? ''));
            $lvlNumRaw = trim((string) ($row['nomor_tingkat'] ?? ''));
            $lvlName = trim((string) ($row['nama_tingkat'] ?? ''));

            if ($code === '' && $progName === '' && $lvlNumRaw === '' && $lvlName === '') {
                continue;
            }

            if ($code !== '' || $progName !== '') {
                if ($code === '' || mb_strlen($code) > 30 || $progName === '' || mb_strlen($progName) > 120) {
                    $errors[] = "{$sheetLabel} baris {$line}: Kode jurusan (maks. 30 karakter) dan nama jurusan (maks. 120 karakter) harus diisi berpasangan.";

                    continue;
                }
                $existingProg = Program::whereRaw('LOWER(code) = ?', [mb_strtolower($code, 'UTF-8')])->first();
                if ($existingProg) {
                    $existingProg->update(['name' => $progName]);
                    $updated++;
                } else {
                    Program::create(['code' => strtoupper($code), 'name' => $progName]);
                    $created++;
                }
            }

            if ($lvlNumRaw !== '' || $lvlName !== '') {
                if (filter_var($lvlNumRaw, FILTER_VALIDATE_INT) === false || (int) $lvlNumRaw < 1 || (int) $lvlNumRaw > 20 || $lvlName === '' || mb_strlen($lvlName) > 60) {
                    $errors[] = "{$sheetLabel} baris {$line}: Nomor tingkat harus angka bulat (1–20) dan nama tingkat wajib diisi (maks. 60 karakter).";

                    continue;
                }
                $lvlInt = (int) $lvlNumRaw;
                $existingLvl = YearLevel::where('level', $lvlInt)->first();
                $nameCollision = YearLevel::whereRaw('LOWER(name) = ?', [mb_strtolower($lvlName, 'UTF-8')])
                    ->when($existingLvl, fn ($q) => $q->where('id', '!=', $existingLvl->id))
                    ->exists();
                if ($nameCollision) {
                    $errors[] = "{$sheetLabel} baris {$line}: Nama tingkat '{$lvlName}' sudah dipakai oleh nomor tingkat lain.";

                    continue;
                }
                if ($existingLvl) {
                    $existingLvl->update(['name' => $lvlName]);
                    $updated++;
                } else {
                    YearLevel::create(['level' => $lvlInt, 'name' => $lvlName]);
                    $created++;
                }
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importBlocks(array $rows, string $sheetLabel = 'Rombel'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $yrName = trim((string) ($row['tahun_ajaran'] ?? ''));
            $sem = self::parseSemester((string) ($row['semester'] ?? ''), 0);
            $progRaw = trim((string) ($row['kode_jurusan'] ?? ''));
            $lvlRaw = trim((string) ($row['nomor_tingkat'] ?? ''));
            $rombel = trim((string) ($row['nama_rombel'] ?? ''));

            $year = self::resolveAcademicYear($yrName);
            $program = self::resolveProgram($progRaw);
            $level = self::resolveYearLevel($lvlRaw);

            if (! $year || ! $sem || ! $program || ! $level || $rombel === '' || mb_strlen($rombel) > 60) {
                $errors[] = "{$sheetLabel} baris {$line}: Pastikan tahun_ajaran, semester (1/2), kode_jurusan, nomor_tingkat, dan nama_rombel (maks. 60 karakter) valid serta sudah terdaftar.";

                continue;
            }

            $existing = Block::where([
                'academic_year_id' => $year->id,
                'program_id' => $program->id,
                'year_level_id' => $level->id,
                'semester' => $sem,
            ])->whereRaw('LOWER(name) = ?', [mb_strtolower($rombel, 'UTF-8')])->first();

            if ($existing) {
                $existing->update(['name' => $rombel]);
                $updated++;
            } else {
                Block::create([
                    'academic_year_id' => $year->id,
                    'program_id' => $program->id,
                    'year_level_id' => $level->id,
                    'semester' => $sem,
                    'name' => $rombel,
                ]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importSubjects(array $rows, string $sheetLabel = 'Mata Pelajaran'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $pendingPrereqs = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $code = trim((string) ($row['kode_mapel'] ?? ''));
            $name = trim((string) ($row['nama_mapel'] ?? ''));
            $unitsRaw = trim((string) ($row['jp_total'] ?? ''));
            $lecRaw = trim((string) ($row['jp_teori'] ?? ''));
            $labRaw = trim((string) ($row['jp_praktik'] ?? ''));
            $progRaw = trim((string) ($row['kode_jurusan'] ?? ''));
            $lvlRaw = trim((string) ($row['nomor_tingkat'] ?? ''));
            $semRaw = trim((string) ($row['semester'] ?? ''));
            $desc = trim((string) ($row['deskripsi'] ?? '')) ?: null;
            $statusRaw = mb_strtolower(trim((string) ($row['status'] ?? 'active')), 'UTF-8');
            $status = in_array($statusRaw, ['inactive', 'nonaktif'], true) ? 'inactive' : 'active';

            if ($code === '' || mb_strlen($code) > 30 || $name === '' || mb_strlen($name) > 160 || ! is_numeric($unitsRaw) || (float) $unitsRaw <= 0 || (float) $unitsRaw > 40) {
                $errors[] = "{$sheetLabel} baris {$line}: Kode mapel, nama mapel, dan jp_total (angka 1–40) wajib diisi.";

                continue;
            }

            $units = round((float) $unitsRaw, 2);
            $lec = $lecRaw !== '' && is_numeric($lecRaw) ? round((float) $lecRaw, 2) : null;
            $lab = $labRaw !== '' && is_numeric($labRaw) ? round((float) $labRaw, 2) : null;
            if (($lec !== null || $lab !== null) && abs((($lec ?? 0) + ($lab ?? 0)) - $units) > 0.01) {
                $errors[] = "{$sheetLabel} baris {$line}: Jumlah jp_teori + jp_praktik harus sama dengan jp_total ({$units} JP).";

                continue;
            }

            $progId = null;
            $lvlId = null;
            $sem = null;
            if ($progRaw !== '' || $lvlRaw !== '' || $semRaw !== '') {
                $prog = self::resolveProgram($progRaw);
                $lvl = self::resolveYearLevel($lvlRaw);
                $sem = self::parseSemester($semRaw, 0) ?: null;
                if (! $prog || ! $lvl || ! $sem) {
                    $errors[] = "{$sheetLabel} baris {$line}: Jika mata pelajaran dikhususkan per jurusan, kolom kode_jurusan, nomor_tingkat, dan semester wajib valid.";

                    continue;
                }
                $progId = $prog->id;
                $lvlId = $lvl->id;
            }

            $query = Subject::whereRaw('LOWER(code) = ?', [mb_strtolower($code, 'UTF-8')]);
            if ($progId === null) {
                $query->whereNull('program_id');
            } else {
                $query->where('program_id', $progId)->where('year_level_id', $lvlId)->where('semester', $sem);
            }
            $subject = $query->first();

            $payload = [
                'code' => $code,
                'name' => $name,
                'units' => $units,
                'lecture_units' => $lec,
                'laboratory_units' => $lab,
                'program_id' => $progId,
                'year_level_id' => $lvlId,
                'semester' => $sem,
                'description' => $desc,
                'status' => $status,
            ];

            if ($subject) {
                $subject->update($payload);
                $updated++;
            } else {
                $subject = Subject::create($payload);
                $created++;
            }

            $prereqRaw = trim((string) ($row['prasyarat'] ?? ''));
            if ($prereqRaw !== '') {
                $pendingPrereqs[] = ['line' => $line, 'subject' => $subject, 'codes' => preg_split('/[,;]+/', $prereqRaw) ?: []];
            }
        }

        foreach ($pendingPrereqs as $item) {
            $prereqIds = [];
            foreach ($item['codes'] as $pCode) {
                $pCode = trim($pCode);
                if ($pCode === '' || mb_strtolower($pCode, 'UTF-8') === 'none' || $pCode === '-') {
                    continue;
                }
                $target = Subject::whereRaw('LOWER(code) = ?', [mb_strtolower($pCode, 'UTF-8')])
                    ->where('id', '!=', $item['subject']->id)
                    ->orderByRaw('CASE WHEN program_id = ? THEN 0 ELSE 1 END', [$item['subject']->program_id ?? 0])
                    ->first();
                if (! $target) {
                    $errors[] = "{$sheetLabel} baris {$item['line']}: Kode prasyarat '{$pCode}' tidak ditemukan.";

                    continue;
                }
                $prereqIds[] = $target->id;
            }
            foreach (array_unique($prereqIds) as $pid) {
                DB::table('subject_prerequisites')->updateOrInsert([
                    'subject_id' => $item['subject']->id,
                    'prerequisite_id' => $pid,
                ]);
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importAnnouncements(array $rows, User $user, string $sheetLabel = 'Pengumuman'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $title = trim((string) ($row['judul'] ?? ''));
            $body = trim((string) ($row['isi_pengumuman'] ?? ''));
            $progRaw = trim((string) ($row['kode_jurusan_opsional'] ?? ''));
            $blkRaw = trim((string) ($row['nama_rombel_opsional'] ?? ''));

            if ($title === '' || mb_strlen($title) > 180 || $body === '') {
                $errors[] = "{$sheetLabel} baris {$line}: Judul (maks. 180 karakter) dan isi pengumuman wajib diisi.";

                continue;
            }

            $progId = null;
            if ($progRaw !== '') {
                $prog = self::resolveProgram($progRaw);
                if (! $prog) {
                    $errors[] = "{$sheetLabel} baris {$line}: Jurusan '{$progRaw}' tidak ditemukan.";

                    continue;
                }
                $progId = $prog->id;
            }

            $blkId = null;
            if ($blkRaw !== '') {
                $blkErr = [];
                $blk = self::resolveBlock(['nama_rombel' => $blkRaw, 'kode_jurusan' => $progRaw], $blkErr);
                if (! $blk) {
                    $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $blkErr);

                    continue;
                }
                $blkId = $blk->id;
                $progId = $blk->program_id;
            }

            $existing = Announcement::whereRaw('LOWER(title) = ?', [mb_strtolower($title, 'UTF-8')])
                ->where('program_id', $progId)
                ->where('block_id', $blkId)
                ->first();

            if ($existing) {
                $existing->update(['body' => $body]);
                $updated++;
            } else {
                Announcement::create([
                    'user_id' => $user->id,
                    'program_id' => $progId,
                    'block_id' => $blkId,
                    'title' => $title,
                    'body' => $body,
                ]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }
}

