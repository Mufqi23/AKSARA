<?php

namespace App\Services\Setup;

use App\Jobs\SendSetupInvitation;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\ScheduleConflicts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait PersistsAcademicSetup
{
    public static function duplicate(array $scope, bool $copyStudents, bool $copySchedules): array
    {
        $scope = Validator::make($scope, ['academic_year_id' => 'required|integer|exists:academic_years,id', 'program_id' => 'required|integer|exists:programs,id', 'year_level_id' => 'required|integer|exists:year_levels,id', 'semester' => 'required|integer|in:1,2,3'])->validate();
        $blocks = Block::where($scope)->with(['classes.subject.program', 'classes.subject.yearLevel', 'classes.teacher.user'])->orderBy('id')->limit(31)->get();
        if ($blocks->isEmpty() || $blocks->count() > 30) {
            throw ValidationException::withMessages(['source' => 'Choose a previous setup with 1–30 blocks.']);
        }
        $subjects = $blocks->flatMap->classes->pluck('subject')->unique('id')->values();
        if ($subjects->isEmpty() || $subjects->count() > 50) {
            throw ValidationException::withMessages(['source' => 'The previous setup needs subjects and supports at most 50 per copy.']);
        }
        $subjectIndexes = $subjects->pluck('id')->flip();
        $assignments = [];
        foreach ($blocks as $blockIndex => $block) {
            foreach ($block->classes as $class) {
                $key = $class->subject_id.':'.$class->teacher_id;
                $assignments[$key] ??= ['subject' => $subjectIndexes[$class->subject_id], 'teacher_id' => $class->teacher_id, 'blocks' => []];
                $assignments[$key]['blocks'][] = $blockIndex;
            }
        }
        $students = [];
        if ($copyStudents) {
            $people = Student::whereIn('block_id', $blocks->pluck('id'))->whereHas('user', fn ($q) => $q->where('role', 'student'))->limit(501)->get(['id', 'block_id']);
            if ($people->count() > 500) {
                throw ValidationException::withMessages(['source' => 'Copy at most 500 students per setup. You can enroll further students through the existing management pages.']);
            }
            $blockIndexes = $blocks->pluck('id')->flip();
            $students = $people->map(fn ($student) => ['id' => $student->id, 'block' => $blockIndexes[$student->block_id]])->all();
        }
        $level = YearLevel::findOrFail($scope['year_level_id']);

        return [
            'program_id' => (int) $scope['program_id'], 'year_level_id' => (int) $scope['year_level_id'],
            'structures' => [['program_id' => (int) $scope['program_id'], 'year_levels' => [['year_level_id' => (int) $scope['year_level_id'],
                'name' => $level->name, 'level' => $level->level,
                'blocks' => $blocks->map(fn ($block) => ['name' => $block->name])->all()]]]],
            'blocks' => $blocks->map(fn ($block) => ['name' => $block->name, 'program_id' => $block->program_id, 'year_level_id' => $block->year_level_id,
                'new_name' => null, 'new_level' => null, 'group' => 0, 'year_level_group' => 0])->all(),
            'subjects' => $subjects->map(fn ($subject) => ['id' => $subject->id, 'code' => $subject->code, 'name' => $subject->name, 'units' => $subject->units, 'label' => $subject->catalog_label])->all(),
            'assignments' => array_values($assignments), 'students' => $students,
            'source' => $scope, 'source_block_ids' => $blocks->pluck('id')->all(), 'copy_schedules' => $copySchedules,
        ];
    }

    public static function create(array $draft): array
    {
        return DB::transaction(function () use ($draft) {
            $data = self::validateDraft($draft);
            $year = ($data['academic_year_id'] ?? null)
                ? AcademicYear::whereKey($data['academic_year_id'])->lockForUpdate()->firstOrFail()
                : AcademicYear::create(['name' => trim($data['academic_year_name']), 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']]);
            self::resolveBlockLevels($data);
            $blockQuery = Block::where('academic_year_id', $year->id)->where('semester', $data['semester'])->where(function ($query) use ($data) {
                foreach ($data['blocks'] as $row) {
                    $query->orWhere(fn ($scope) => $scope->where('program_id', $row['program_id'])->where('year_level_id', $row['year_level_id'])->whereRaw('LOWER(name) = ?', [Str::lower($row['name'])]));
                }
            });
            if ((clone $blockQuery)->exists()) {
                throw ValidationException::withMessages(['blocks' => 'One or more target blocks already exist for this academic year, semester, program and year level. Change the names or correct them on the management pages.']);
            }
            $now = now();
            $blockScope = ['academic_year_id' => $year->id, 'semester' => $data['semester']];
            Block::insert(array_map(fn ($row) => $blockScope + ['program_id' => $row['program_id'], 'year_level_id' => $row['year_level_id'], 'name' => $row['name'], 'created_at' => $now, 'updated_at' => $now], $data['blocks']));
            $blockMap = $blockQuery->get()->keyBy(fn ($block) => self::blockKey($block->program_id, $block->year_level_id, $block->name));
            $blocks = array_map(fn ($row) => $blockMap->get(self::blockKey($row['program_id'], $row['year_level_id'], $row['name'])), $data['blocks']);
            $newSubjects = array_filter($data['subjects'], fn ($row) => ! $row['id']);
            if ($newSubjects) {
                Subject::insert(array_map(fn ($row) => ['code' => $row['code'], 'name' => $row['name'], 'units' => $row['units'], 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], array_values($newSubjects)));
            }
            $catalog = Subject::whereIn('id', array_filter(array_column($data['subjects'], 'id')))->orWhere(function ($query) use ($newSubjects) {
                $query->whereNull('program_id')->whereIn('code', array_column($newSubjects, 'code'));
            })->get();
            $subjects = array_map(fn ($row) => $row['id'] ? $catalog->firstWhere('id', $row['id']) : $catalog->first(fn ($subject) => ! $subject->program_id && $subject->code === $row['code']), $data['subjects']);
            $classRows = [];
            foreach ($data['assignments'] as $row) {
                foreach ($row['blocks'] as $blockIndex) {
                    $classRows[] = ['teacher_id' => $row['teacher_id'], 'block_id' => $blocks[$blockIndex]->id, 'subject_id' => $subjects[$row['subject']]->id, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            TeacherAssignment::insert($classRows);
            $classes = [];
            $blockIndexes = array_flip(array_map(fn ($block) => $block->id, $blocks));
            foreach (TeacherAssignment::whereIn('block_id', array_keys($blockIndexes))->get() as $class) {
                $index = $blockIndexes[$class->block_id];
                $class->setRelation('block', $blocks[$index]);
                $classes[$index.':'.$class->subject_id] = $class;
            }
            $scheduleCount = self::copySchedules($data, $draft, $blocks, $classes);
            [$studentCount, $newUsers, $enrollmentCount] = self::enroll($data, $blocks, $classes);

            return ['academic_year_id' => $year->id, 'blocks' => count($blocks), 'subjects' => count($subjects), 'classes' => count($classes), 'students' => $studentCount, 'new_users' => count($newUsers), 'invitations_queued' => count($newUsers), 'enrollments' => $enrollmentCount, 'schedules' => $scheduleCount];
        }, 3);
    }

    private static function blockKey(int $programId, int $levelId, string $name): string
    {
        return $programId.':'.$levelId.':'.Str::lower($name);
    }

    private static function resolveBlockLevels(array &$data): void
    {
        $new = [];
        foreach ($data['blocks'] as $block) {
            if (! $block['year_level_id']) {
                $new[(int) $block['new_level']] = $block['new_name'];
            }
        }
        if (! $new) {
            return;
        }
        $existing = YearLevel::whereIn('level', array_keys($new))->lockForUpdate()->get()->keyBy('level');
        $missing = array_diff_key($new, $existing->all());
        if ($missing) {
            if (YearLevel::whereIn(DB::raw('LOWER(name)'), array_map(fn ($name) => Str::lower($name), array_values($missing)))->exists()) {
                throw ValidationException::withMessages(['structures' => 'A new year level name now belongs to another level. Review the available year levels and try again.']);
            }
            $now = now();
            YearLevel::insert(array_map(fn ($ordinal, $name) => ['level' => $ordinal, 'name' => $name, 'created_at' => $now, 'updated_at' => $now], array_keys($missing), array_values($missing)));
            $existing = YearLevel::whereIn('level', array_keys($new))->get()->keyBy('level');
        }
        foreach ($data['blocks'] as &$block) {
            if (! $block['year_level_id']) {
                $block['year_level_id'] = $existing[$block['new_level']]->id;
            }
        }
        unset($block);
    }

    private static function copySchedules(array $data, array $draft, array $blocks, array $classes): int
    {
        if (! $data['copy_schedules'] || ! $data['source']) {
            return 0;
        }
        $sourceRows = Block::where($data['source'])->orderBy('id')->limit(30)->get();
        $sourceBlocks = $sourceRows->pluck('id')->all();
        $sourceIds = $draft['source_block_ids'] ?? $sourceBlocks;
        if (! is_array($sourceIds) || count($sourceIds) !== count($blocks) || array_map('intval', $sourceIds) !== $sourceBlocks) {
            throw ValidationException::withMessages(['schedules' => 'Copied blocks changed. Load the previous setup again or turn off Copy Schedules before saving.']);
        }
        foreach ($blocks as $index => $block) {
            if ((int) $block->program_id !== (int) $sourceRows[$index]->program_id || (int) $block->year_level_id !== (int) $sourceRows[$index]->year_level_id) {
                throw ValidationException::withMessages(['schedules' => 'The copied program or year level changed. Turn off Copy Schedules or reload the previous setup before saving.']);
            }
        }
        $mapping = array_flip($sourceIds);
        $schedules = ClassSchedule::whereHas('classroom', fn ($q) => $q->whereIn('block_id', $sourceBlocks))->with('classroom')->orderBy('id')->limit(301)->get();
        if ($schedules->count() > 300) {
            throw ValidationException::withMessages(['schedules' => 'Copy at most 300 schedule entries per setup.']);
        }
        $count = 0;
        foreach ($schedules as $schedule) {
            $index = $mapping[$schedule->classroom->block_id] ?? null;
            $class = $index === null ? null : ($classes[$index.':'.$schedule->classroom->subject_id] ?? null);
            if (! $class) {
                continue;
            }
            $row = $schedule->only(['day', 'start_time', 'end_time', 'room']);
            if (ScheduleConflicts::exists($class, $row)) {
                throw ValidationException::withMessages(['schedules' => 'A copied schedule conflicts with a teacher, block or room in the target semester. Turn off Copy Schedules and set the times on the existing schedule page. Nothing was created.']);
            }
            ClassSchedule::create($row + ['teacher_assignment_id' => $class->id]);
            $count++;
        }

        return $count;
    }

    private static function enroll(array $data, array $blocks, array $classes): array
    {
        $now = now();
        $selection = [];
        $studentIds = array_column($data['students'], 'id');
        $studentIds = array_merge($studentIds, array_filter(array_column($data['csv_rows'], 'existing_student_id')));
        $existingStudents = Student::whereIn('id', $studentIds)->lockForUpdate()->get()->keyBy('id');
        foreach ($data['students'] as $row) {
            $person = $existingStudents[$row['id']];
            $selection[] = ['student_id' => $person->id, 'user_id' => $person->user_id, 'student_number' => $person->student_number, 'block' => $row['block']];
        }
        $newRows = array_filter($data['csv_rows'], fn ($row) => ! $row['existing_user_id']);
        if ($newRows) {
            $unusableHash = Hash::make(Str::random(64));
            User::insert(array_map(fn ($row) => ['name' => $row['name'], 'email' => $row['email'], 'password' => $unusableHash, 'role' => 'student', 'status' => 'inactive', 'created_at' => $now, 'updated_at' => $now], array_values($newRows)));
        }
        $users = User::whereIn(DB::raw('LOWER(email)'), array_map(fn ($row) => Str::lower($row['email']), $data['csv_rows']))->get()->keyBy(fn ($user) => Str::lower($user->email));
        $newUserIds = [];
        foreach ($data['csv_rows'] as $row) {
            $user = $users[Str::lower($row['email'])];
            if (! $row['existing_user_id']) {
                $newUserIds[] = $user->id;
            }
            $selection[] = ['student_id' => $row['existing_student_id'], 'user_id' => $user->id, 'student_number' => $row['student_number'], 'block' => $row['block_index']];
        }
        $newProfiles = [];
        $moves = [];
        foreach ($selection as $row) {
            $values = ['user_id' => $row['user_id'], 'student_number' => $row['student_number'], 'block_id' => $blocks[$row['block']]->id, 'created_at' => $now, 'updated_at' => $now];
            if ($row['student_id']) {
                $values['id'] = $row['student_id'];
                $values['created_at'] = $existingStudents[$row['student_id']]->created_at;
                $moves[] = $values;
            } else {
                $newProfiles[] = $values;
            }
        }
        if ($newProfiles) {
            Student::insert($newProfiles);
        }
        if ($moves) {
            Student::upsert($moves, ['id'], ['block_id', 'updated_at']);
        }
        $studentMap = Student::whereIn('user_id', array_column($selection, 'user_id'))->get(['id', 'user_id'])->keyBy('user_id');
        $enrollments = [];
        foreach ($selection as $row) {
            foreach ($classes as $class) {
                if ($class->block_id === $blocks[$row['block']]->id) {
                    $enrollments[] = ['student_id' => $studentMap[$row['user_id']]->id, 'teacher_assignment_id' => $class->id, 'created_at' => $now, 'updated_at' => $now];
                }
            }
        }
        foreach (array_chunk($enrollments, 300) as $chunk) {
            Enrollment::insert($chunk);
        }
        if ($newUserIds) {
            Queue::connection('database')->bulk(array_map(fn ($id) => new SendSetupInvitation($id), $newUserIds));
        }

        return [count($selection), $newUserIds, count($enrollments)];
    }
}

