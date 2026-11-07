<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\DeadlineReminder;
use App\Notifications\PortalNotice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDeadlineRemindersCommand extends Command
{
    protected $signature = 'lms:deadlines';

    protected $description = 'Mengirim satu pengingat tenggat tugas per siswa untuk tugas yang jatuh tempo dalam 24 jam';

    public function handle(): int
    {
        $assignments = Assignment::with([
            'classroom.enrollments.student.user',
            'submissions:id,assignment_id,student_id',
        ])
            ->where('status', 'published')
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->get();

        foreach ($assignments as $assignment) {
            $submittedStudentIds = $assignment->submissions->pluck('student_id')->flip();
            foreach ($assignment->classroom->enrollments as $enrollment) {
                $user = $enrollment->student?->user;
                if (! $user || $user->status !== 'active' || $submittedStudentIds->has($enrollment->student_id)) {
                    continue;
                }

                DB::transaction(function () use ($assignment, $user) {
                    $reminder = DeadlineReminder::firstOrCreate(['assignment_id' => $assignment->id, 'user_id' => $user->id]);
                    if ($reminder->wasRecentlyCreated) {
                        $user->notify(new PortalNotice('Pengingat batas waktu 24 jam', $assignment->title, '/assignments/'.$assignment->id));
                    }
                });
            }
        }

        $this->info('Pengingat tenggat tugas telah diperiksa.');

        return self::SUCCESS;
    }
}

