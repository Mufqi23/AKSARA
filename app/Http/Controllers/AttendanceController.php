<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ExcuseRequest;
use App\Models\Setting;
use App\Models\TeacherAssignment;
use App\Services\Access;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function create(Request $r, TeacherAssignment $classroom)
    {
        Access::classroom($r->user(), $classroom, true);
        $data = $r->validate(['date' => 'required|date|before_or_equal:today', 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i|after:start_time']);
        if ($r->user()->role !== 'admin' && Carbon::parse($data['date'])->lt(today()->subDays(7))) {
            return back()->withInput()->withErrors(['date' => 'Guru hanya dapat membuat sesi kehadiran maksimal 7 hari ke belakang.']);
        }
        try {
            $session = AttendanceSession::create($data + ['teacher_assignment_id' => $classroom->id, 'late_threshold' => (int) (Setting::where('key', 'late_threshold')->value('value') ?? 15)]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withInput()->withErrors(['date' => 'Sesi kehadiran untuk kelas, tanggal, dan jam mulai ini sudah pernah dibuat.']);
            }
            throw $e;
        }

        return redirect('/attendance/sessions/'.$session->id)->with('success', 'Sesi kehadiran berhasil dibuat.');
    }

    public function show(Request $r, AttendanceSession $session)
    {
        Access::classroom($r->user(), $session->classroom, true);
        $session->load('classroom.block.program', 'classroom.subject', 'records');
        if (config('lms.show_advanced_features')) {
            $session->load('records.excuse', 'records.logs.user', 'records.student.user');
        }
        $enrollments = $session->classroom->enrollments()->with('student.user')->get();
        $recordsByStudent = $session->records->keyBy('student_id');

        return view('attendance.session', compact('session', 'enrollments', 'recordsByStudent'));
    }

    public function record(Request $r, AttendanceSession $session, ?int $studentId = null)
    {
        $data = $r->validate(['student_id' => ($studentId ? 'nullable' : 'required').'|integer|exists:students,id', 'status' => 'required|in:present,late,absent,excused', 'minutes_late' => 'nullable|integer|min:0|max:1440', 'remarks' => 'nullable|string|max:2000', 'reason' => 'nullable|string|max:2000']);
        AttendanceService::record($r->user(), $session, $studentId ?? (int) $data['student_id'], $data, $studentId !== null);

        return back()->with('success', 'Data kehadiran berhasil disimpan.');
    }

    public function bulkRecord(Request $r, AttendanceSession $session)
    {
        AttendanceService::editable($r->user(), $session);
        $completeRow = $r->filled('expected_count') ? 'present|' : '';
        $data = $r->validate([
            'records' => 'required|array|min:1|max:500',
            'expected_count' => 'nullable|integer|min:1|max:500',
            'records.*' => 'required|array:student_id,status,minutes_late,remarks,reason',
            'records.*.student_id' => 'required|integer|distinct',
            'records.*.status' => 'required|in:present,late,absent,excused',
            'records.*.minutes_late' => $completeRow.'nullable|integer|min:0|max:1440',
            'records.*.remarks' => $completeRow.'nullable|string|max:2000',
            'records.*.reason' => $completeRow.($r->user()->role === 'admin' ? 'required' : 'nullable').'|string|max:2000',
        ], [
            'records.*.minutes_late.present' => 'Sebagian data kehadiran tidak terkirim lengkap ke server. Tidak ada data yang diubah.',
            'records.*.remarks.present' => 'Sebagian data kehadiran tidak terkirim lengkap ke server. Tidak ada data yang diubah.',
            'records.*.reason.present' => 'Sebagian data kehadiran tidak terkirim lengkap ke server. Tidak ada data yang diubah.',
        ]);
        if ($r->filled('expected_count') && count($data['records']) !== (int) $data['expected_count']) {
            throw ValidationException::withMessages(['records' => 'Sebagian baris daftar hadir tidak terkirim lengkap. Muat ulang halaman lalu simpan kembali.']);
        }
        $count = AttendanceService::recordMany($r->user(), $session, $data['records']);

        return back()->with('success', $count ? 'Kehadiran berhasil disimpan untuk '.$count.' siswa.' : 'Tidak ada perubahan data kehadiran.');
    }

    public function excuse(Request $r, AttendanceRecord $record)
    {
        abort_unless($r->user()->role === 'student' && $record->student_id === $r->user()->student?->id, 403);
        Access::classroom($r->user(), $record->session->classroom);
        abort_unless($record->status === 'absent', 422);
        $data = $r->validate(['reason' => 'required|string|min:10|max:2000']);
        if ($record->excuse) {
            return back()->withErrors(['reason' => 'Pengajuan izin untuk catatan kehadiran ini sudah pernah dikirim.']);
        }
        ExcuseRequest::create($data + ['attendance_record_id' => $record->id, 'status' => 'pending']);

        return back()->with('success', 'Pengajuan izin berhasil dikirim untuk diperiksa guru.');
    }

    public function review(Request $r, ExcuseRequest $excuse)
    {
        $excuse->loadMissing('record.session.classroom');
        Access::classroom($r->user(), $excuse->record->session->classroom, true);
        $data = $r->validate(['status' => 'required|in:approved,rejected', 'review_note' => 'required|string|max:2000']);
        DB::transaction(function () use ($r, $excuse, $data) {
            $excuse = ExcuseRequest::whereKey($excuse->id)->lockForUpdate()->firstOrFail();
            abort_unless($excuse->status === 'pending', 422);
            AttendanceService::record($r->user(), $excuse->record->session, $excuse->record->student_id, ['status' => $data['status'] === 'approved' ? 'excused' : 'absent', 'minutes_late' => null, 'remarks' => $data['review_note'], 'reason' => 'Tinjauan izin ('.$data['status'].'): '.$data['review_note']], true);
            $excuse->update($data);
        });

        return back()->with('success', 'Pengajuan izin berhasil diproses.');
    }
}
