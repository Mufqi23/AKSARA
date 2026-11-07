<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Block;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\PortalNotice;
use App\Services\Access;
use App\Services\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index(Request $r)
    {
        $isAdmin = $r->user()->role === 'admin';

        return view('announcements.index', [
            'announcements' => Access::announcements($r->user())->with('classroom.block.program', 'classroom.subject', 'block', 'program')->latest()->paginate(15),
            'classes' => Access::classes($r->user())->get(),
            'programs' => $isAdmin ? Catalog::choices('programs') : [],
            'blocks' => $isAdmin ? Catalog::choices('blocks') : [],
        ]);
    }

    public function save(Request $r, ?int $id = null)
    {
        abort_unless(in_array($r->user()->role, ['admin', 'teacher']), 403);
        $data = $r->validate([
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:10000',
            'teacher_assignment_id' => ($r->user()->role === 'teacher' ? 'required|' : 'nullable|').'exists:teacher_assignments,id',
            'program_id' => 'nullable|exists:programs,id',
            'block_id' => 'nullable|exists:blocks,id',
        ]);
        $row = $id ? Announcement::findOrFail($id) : new Announcement;
        if ($id) {
            abort_unless($r->user()->role === 'admin' || $row->user_id === $r->user()->id, 403);
            if ($r->user()->role === 'teacher') {
                abort_if(! $row->classroom, 403);
                Access::classroom($r->user(), $row->classroom, true);
            }
        }
        if ($r->user()->role === 'teacher') {
            $class = TeacherAssignment::findOrFail($data['teacher_assignment_id'] ?? 0);
            Access::classroom($r->user(), $class, true);
            $data['program_id'] = null;
            $data['block_id'] = null;
        }
        $count = collect($data)->only(['teacher_assignment_id', 'program_id', 'block_id'])->filter()->count();
        if ($count > 1) {
            return back()->withInput()->withErrors(['audience' => 'Pilih satu sasaran penerima saja, atau kosongkan ketiganya untuk pengumuman seluruh sekolah (khusus Admin).']);
        }
        $row->fill($data + ['user_id' => $r->user()->id])->save();
        if (! $id) {
            $this->notifyRecipients($row);
        }

        return back()->with('success', 'Pengumuman berhasil disimpan.');
    }

    public function delete(Request $r, Announcement $announcement)
    {
        abort_unless($r->user()->role === 'admin' || ($r->user()->role === 'teacher' && $announcement->user_id === $r->user()->id), 403);
        if ($r->user()->role === 'teacher') {
            abort_if(! $announcement->classroom, 403);
            Access::classroom($r->user(), $announcement->classroom, true);
        }
        $announcement->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function notifyRecipients(Announcement $row): void
    {
        $query = User::where('status', 'active')->whereIn('role', ['admin', 'teacher', 'student']);

        if ($row->teacher_assignment_id) {
            $classId = (int) $row->teacher_assignment_id;
            $query->where(function ($q) use ($classId) {
                $q->where('role', 'admin')
                    ->orWhereHas('teacher.classes', fn ($t) => $t->whereKey($classId))
                    ->orWhereHas('student.enrollments', fn ($e) => $e->where('teacher_assignment_id', $classId));
            });
        } elseif ($row->block_id) {
            $blockId = (int) $row->block_id;
            $query->where(function ($q) use ($blockId) {
                $q->where('role', 'admin')
                    ->orWhereHas('teacher.classes', fn ($t) => $t->where('block_id', $blockId))
                    ->orWhereHas('student', fn ($s) => $s->where('block_id', $blockId)
                        ->orWhereHas('enrollments.classroom', fn ($c) => $c->where('block_id', $blockId)));
            });
        } elseif ($row->program_id) {
            $blockIds = Block::where('program_id', $row->program_id)->pluck('id');
            $query->where(function ($q) use ($blockIds) {
                $q->where('role', 'admin')
                    ->orWhereHas('teacher.classes', fn ($t) => $t->whereIn('block_id', $blockIds))
                    ->orWhereHas('student', fn ($s) => $s->whereIn('block_id', $blockIds)
                        ->orWhereHas('enrollments.classroom', fn ($c) => $c->whereIn('block_id', $blockIds)));
            });
        }

        $notice = new PortalNotice('Pengumuman baru', $row->title, '/announcements');
        $query->chunkById(100, function ($users) use ($notice) {
            Notification::send($users, $notice);
        });
    }
}
