<?php

namespace App\Services\Dataset\Importers;

use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Program;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\Access;
use Carbon\Carbon;

trait ImportHelpers
{
    protected static function parseDateValue(string $value): ?string
    {
        $v = trim($value);
        if ($v === '') {
            return null;
        }
        if (is_numeric($v) && (float) $v > 25000 && (float) $v < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $v))->format('Y-m-d');
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat('!'.$fmt, $v);
                if ($dt && $dt->format($fmt) === $v) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function parseDateTimeValue(string $value): ?string
    {
        $v = trim($value);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v.' 23:59:00';
        }
        try {
            return Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function parseTimeValue(string $value): ?string
    {
        $v = trim(str_replace('.', ':', $value));
        if ($v === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $minutes = (int) round(((float) $value) * 1440);

            return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $v, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d', $h, $min);
            }
        }

        return null;
    }

    protected static function parseSemester(string $value, int $default = 1): ?int
    {
        $v = mb_strtolower(trim($value), 'UTF-8');
        if ($v === '') {
            return $default;
        }

        return match ($v) {
            '1', 'ganjil', 'semester 1', '1st' => 1,
            '2', 'genap', 'semester 2', '2nd' => 2,
            '3', 'remedial', 'pendek', 'antara' => 3,
            default => null,
        };
    }

    protected static function parseDayOfWeek(string $value): ?int
    {
        $v = mb_strtolower(trim($value), 'UTF-8');

        return match ($v) {
            '1', 'senin', 'monday', 'mon' => 1,
            '2', 'selasa', 'tuesday', 'tue' => 2,
            '3', 'rabu', 'wednesday', 'wed' => 3,
            '4', 'kamis', 'thursday', 'thu' => 4,
            '5', 'jumat', "jum'at", 'friday', 'fri' => 5,
            '6', 'sabtu', 'saturday', 'sat' => 6,
            '7', 'minggu', 'ahad', 'sunday', 'sun' => 7,
            default => null,
        };
    }

    protected static function parsePublishStatus(string $value, string $default = 'published'): ?string
    {
        $v = mb_strtolower(trim($value), 'UTF-8');
        if ($v === '') {
            return $default;
        }

        return match ($v) {
            'published', 'terbit', 'aktif', 'active' => 'published',
            'draft', 'draf', 'konsep' => 'draft',
            default => null,
        };
    }

    protected static function parseAttendanceStatus(string $value): ?string
    {
        $v = mb_strtolower(trim($value), 'UTF-8');

        return match ($v) {
            'present', 'hadir', 'h' => 'present',
            'late', 'terlambat', 'telat', 't' => 'late',
            'excused', 'izin', 'sakit', 'izin/sakit', 'i', 's' => 'excused',
            'absent', 'alpa', 'alpha', 'absen', 'tanpa keterangan', 'a' => 'absent',
            default => null,
        };
    }

    protected static function resolveAcademicYear(string $name): ?AcademicYear
    {
        $n = trim($name);
        if ($n === '') {
            return AcademicYear::orderByDesc('starts_on')->first();
        }

        return AcademicYear::whereRaw('LOWER(name) = ?', [mb_strtolower($n, 'UTF-8')])->first();
    }

    protected static function resolveProgram(string $codeOrName): ?Program
    {
        $v = mb_strtolower(trim($codeOrName), 'UTF-8');
        if ($v === '') {
            return null;
        }

        return Program::whereRaw('LOWER(code) = ? OR LOWER(name) = ?', [$v, $v])->first();
    }

    protected static function resolveYearLevel(string $levelOrName): ?YearLevel
    {
        $v = trim($levelOrName);
        if ($v === '') {
            return null;
        }
        if (filter_var($v, FILTER_VALIDATE_INT) !== false) {
            $found = YearLevel::where('level', (int) $v)->first();
            if ($found) {
                return $found;
            }
        }

        return YearLevel::whereRaw('LOWER(name) = ?', [mb_strtolower($v, 'UTF-8')])->first();
    }

    protected static function resolveBlock(array $row, array &$rowErrors): ?Block
    {
        $name = trim((string) ($row['nama_rombel'] ?? ''));
        if ($name === '') {
            $rowErrors[] = 'Kolom nama_rombel wajib diisi.';

            return null;
        }

        $query = Block::with(['academicYear', 'program', 'yearLevel'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')]);

        if (($yr = trim((string) ($row['tahun_ajaran'] ?? ''))) !== '') {
            $year = self::resolveAcademicYear($yr);
            if (! $year) {
                $rowErrors[] = "Tahun ajaran '{$yr}' belum terdaftar.";

                return null;
            }
            $query->where('academic_year_id', $year->id);
        }

        if (($semRaw = trim((string) ($row['semester'] ?? ''))) !== '') {
            $sem = self::parseSemester($semRaw, 0);
            if (! $sem) {
                $rowErrors[] = "Nilai semester '{$semRaw}' tidak valid (gunakan 1/Ganjil atau 2/Genap).";

                return null;
            }
            $query->where('semester', $sem);
        }

        if (($progRaw = trim((string) ($row['kode_jurusan'] ?? ''))) !== '') {
            $prog = self::resolveProgram($progRaw);
            if (! $prog) {
                $rowErrors[] = "Jurusan '{$progRaw}' belum terdaftar.";

                return null;
            }
            $query->where('program_id', $prog->id);
        }

        if (($lvlRaw = trim((string) ($row['nomor_tingkat'] ?? ''))) !== '') {
            $lvl = self::resolveYearLevel($lvlRaw);
            if (! $lvl) {
                $rowErrors[] = "Tingkat kelas '{$lvlRaw}' belum terdaftar.";

                return null;
            }
            $query->where('year_level_id', $lvl->id);
        }

        $matches = $query->get();
        if ($matches->isEmpty()) {
            $rowErrors[] = "Rombel '{$name}' tidak ditemukan pada struktur sekolah.";

            return null;
        }
        if ($matches->count() > 1) {
            $rowErrors[] = "Nama rombel '{$name}' terdapat pada lebih dari satu jurusan/tahun ajaran; lengkapi kolom tahun_ajaran, semester, dan kode_jurusan.";

            return null;
        }

        return $matches->first();
    }

    protected static function resolveClassroomForUser(User $user, array $row, ?int $forcedClassId, array &$rowErrors): ?TeacherAssignment
    {
        $rombel = trim((string) ($row['nama_rombel'] ?? ''));
        $kodeMapel = trim((string) ($row['kode_mapel'] ?? ''));

        if ($rombel === '' && $kodeMapel === '' && $forcedClassId) {
            $cls = Access::classes($user)->whereKey($forcedClassId)->first();
            if (! $cls) {
                $rowErrors[] = 'Kelas tujuan tidak ditemukan atau di luar hak akses mengajar Anda.';
            }

            return $cls;
        }

        if ($rombel === '' || $kodeMapel === '') {
            $rowErrors[] = 'Kolom nama_rombel dan kode_mapel wajib diisi.';

            return null;
        }

        $query = Access::classes($user)
            ->whereHas('block', function ($q) use ($rombel, $row) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($rombel, 'UTF-8')]);
                if (($yr = trim((string) ($row['tahun_ajaran'] ?? ''))) !== '') {
                    $q->whereHas('academicYear', fn ($yq) => $yq->whereRaw('LOWER(name) = ?', [mb_strtolower($yr, 'UTF-8')]));
                }
                if (($sem = self::parseSemester((string) ($row['semester'] ?? ''), 0)) > 0) {
                    $q->where('semester', $sem);
                }
            })
            ->whereHas('subject', fn ($q) => $q->whereRaw('LOWER(code) = ?', [mb_strtolower($kodeMapel, 'UTF-8')]));

        if ($forcedClassId) {
            $query->whereKey($forcedClassId);
        }

        $matches = $query->get();
        if ($matches->isEmpty()) {
            $rowErrors[] = "Kelas '{$kodeMapel}' pada rombel '{$rombel}' tidak ditemukan atau bukan kelas yang Anda ajar.";

            return null;
        }
        if ($matches->count() > 1) {
            $rowErrors[] = "Kelas '{$kodeMapel}' pada rombel '{$rombel}' cocok dengan lebih dari satu periode; pilih filter kelas spesifik terlebih dahulu.";

            return null;
        }

        return $matches->first();
    }

    protected static function resolveTeacherByNip(string $nipOrEmail): ?Teacher
    {
        $v = mb_strtolower(trim($nipOrEmail), 'UTF-8');
        if ($v === '') {
            return null;
        }

        return Teacher::with('user')
            ->whereRaw('LOWER(employee_number) = ?', [$v])
            ->orWhereHas('user', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$v]))
            ->first();
    }

    protected static function resolveStudentByNis(string $nis): ?Student
    {
        $v = mb_strtolower(trim($nis), 'UTF-8');
        if ($v === '') {
            return null;
        }

        return Student::with('user')->whereRaw('LOWER(student_number) = ?', [$v])->first();
    }

    protected static function resolveSubjectForBlock(string $code, ?Block $block): ?Subject
    {
        $c = mb_strtolower(trim($code), 'UTF-8');
        if ($c === '') {
            return null;
        }
        if ($block) {
            $scoped = Subject::whereRaw('LOWER(code) = ?', [$c])
                ->where('program_id', $block->program_id)
                ->where('year_level_id', $block->year_level_id)
                ->where('semester', $block->semester)
                ->first();
            if ($scoped) {
                return $scoped;
            }
        }

        return Subject::whereRaw('LOWER(code) = ?', [$c])->whereNull('program_id')->first()
            ?? Subject::whereRaw('LOWER(code) = ?', [$c])->first();
    }
}

