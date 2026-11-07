<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Services\Ai\EarlyWarningCalculator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EarlyWarningSystemTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.com')->firstOrFail();
    }

    public function test_early_warning_calculator_identifies_high_medium_and_low_risk_and_excludes_excused_absences(): void
    {
        // High risk due to low attendance (< 75%) and overdue missing assignments
        $high = EarlyWarningCalculator::scoreMetrics(60.0, 3, 2, 0, 0, 50.0);
        $this->assertSame('high', $high['tier']);
        $this->assertSame('Risiko Tinggi', $high['tier_label']);
        $this->assertGreaterThanOrEqual(60, $high['risk_score']);

        // Medium risk due to moderate attendance (80%) and 1 returned assignment
        $medium = EarlyWarningCalculator::scoreMetrics(80.0, 1, 0, 0, 1, 78.0);
        $this->assertSame('medium', $medium['tier']);
        $this->assertSame('Risiko Sedang', $medium['tier_label']);
        $this->assertGreaterThanOrEqual(30, $medium['risk_score']);
        $this->assertLessThan(60, $medium['risk_score']);

        // Low risk when attendance >= 85%, 0 missing assignments, and grade >= 70%
        $low = EarlyWarningCalculator::scoreMetrics(95.0, 0, 0, 0, 0, 88.0);
        $this->assertSame('low', $low['tier']);
        $this->assertSame('Terkendali', $low['tier_label']);
        $this->assertLessThan(30, $low['risk_score']);

        // Verify excused absences do not penalize student attendance rate in cohort analysis
        $session = AttendanceSession::create([
            'teacher_assignment_id' => 1,
            'date' => today()->subDay(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'late_threshold' => 15,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => 1,
            'status' => 'excused',
        ]);

        $cohort = EarlyWarningCalculator::analyzeCohort($this->user('admin'), 1);
        $student1 = $cohort['all_students']->firstWhere('student_id', 1);
        $this->assertSame(100.0, $student1['attendance_rate']);
        $this->assertGreaterThanOrEqual(1, $student1['excused_count']);
    }

    public function test_admin_and_teacher_can_filter_early_warning_dashboard_and_detect_at_risk_student(): void
    {
        // Make Student 1 high-risk in Class 1 (2 overdue missing assignments + 2 unexcused absences + low grade)
        foreach ([1, 2] as $idx) {
            $session = AttendanceSession::create([
                'teacher_assignment_id' => 1,
                'date' => today()->subDays($idx),
                'start_time' => '15:00',
                'end_time' => '16:00',
                'late_threshold' => 15,
            ]);
            AttendanceRecord::create([
                'attendance_session_id' => $session->id,
                'student_id' => 1,
                'status' => 'absent',
            ]);
            Assignment::create([
                'teacher_assignment_id' => 1,
                'title' => 'Overdue Mandatory Task '.$idx,
                'instructions' => 'Must submit.',
                'due_at' => now()->subDays($idx),
                'total_points' => 100,
                'allow_text' => true,
                'status' => 'published',
            ]);
        }
        Grade::where('student_id', 1)->update(['score' => 8, 'total_points' => 20]);

        $student1 = Student::with('user')->findOrFail(1);

        $this->actingAs($this->user('admin'))
            ->get('/early-warning?class_id=1&tier=high')
            ->assertOk()
            ->assertSee('AI Deteksi Dini Siswa Berisiko')
            ->assertSee($student1->user->name)
            ->assertSee('Risiko Tinggi')
            ->assertViewHas('counts', fn ($counts) => $counts['high'] >= 1);

        $this->actingAs($this->user('teacher'))
            ->get('/early-warning?class_id=1')
            ->assertOk()
            ->assertSee($student1->user->name)
            ->assertSee('Saran AI:');

        // Teacher cannot access unassigned class 2
        $this->actingAs($this->user('teacher'))
            ->get('/early-warning?class_id=2')
            ->assertForbidden();
    }

    public function test_student_views_personal_ai_study_advisor_without_exposing_other_students(): void
    {
        $student1 = $this->user('student');
        $otherStudent = Student::with('user')->findOrFail(2);

        $this->actingAs($student1)
            ->get('/early-warning')
            ->assertOk()
            ->assertSee('Evaluasi Dini & Panduan Belajar AI', false)
            ->assertSee($student1->name)
            ->assertSee('Rincian Evaluasi AI per Mata Pelajaran')
            ->assertDontSee($otherStudent->user->name);
    }

    public function test_gemini_provider_falls_back_gracefully_when_api_returns_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota exceeded'], 500),
        ]);
        config([
            'lms.ai_ews.provider' => 'gemini',
            'lms.ai_ews.gemini_api_key' => 'test-dummy-key',
        ]);

        $this->actingAs($this->user('admin'))
            ->get('/early-warning')
            ->assertOk()
            ->assertSee('Mesin Diagnostik Cerdas AKSARA');
    }
}

