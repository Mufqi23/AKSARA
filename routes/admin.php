<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\YearLevelController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/settings', [AdminController::class, 'settings']);
    Route::put('/settings', [AdminController::class, 'saveSettings']);
    Route::post('/users/{user}/invite', [AdminController::class, 'invite'])->middleware('throttle:10,1');

    Route::get('/manage/users', [UserController::class, 'index']);
    Route::get('/manage/users/create', [UserController::class, 'create']);
    Route::get('/manage/users/{id}/edit', [UserController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/users', [UserController::class, 'store']);
    Route::put('/manage/users/{id}', [UserController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/users/{id}', [UserController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/academic-years', [AcademicYearController::class, 'index']);
    Route::get('/manage/academic-years/create', [AcademicYearController::class, 'create']);
    Route::get('/manage/academic-years/{id}/edit', [AcademicYearController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/academic-years', [AcademicYearController::class, 'store']);
    Route::put('/manage/academic-years/{id}', [AcademicYearController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/academic-years/{id}', [AcademicYearController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/programs', [ProgramController::class, 'index']);
    Route::get('/manage/programs/create', [ProgramController::class, 'create']);
    Route::get('/manage/programs/{id}/edit', [ProgramController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/programs', [ProgramController::class, 'store']);
    Route::put('/manage/programs/{id}', [ProgramController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/programs/{id}', [ProgramController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/year-levels', [YearLevelController::class, 'index']);
    Route::get('/manage/year-levels/create', [YearLevelController::class, 'create']);
    Route::get('/manage/year-levels/{id}/edit', [YearLevelController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/year-levels', [YearLevelController::class, 'store']);
    Route::put('/manage/year-levels/{id}', [YearLevelController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/year-levels/{id}', [YearLevelController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/blocks', [BlockController::class, 'index']);
    Route::get('/manage/blocks/create', [BlockController::class, 'create']);
    Route::get('/manage/blocks/{id}/edit', [BlockController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/blocks', [BlockController::class, 'store']);
    Route::put('/manage/blocks/{id}', [BlockController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/blocks/{id}', [BlockController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/subjects', [SubjectController::class, 'index']);
    Route::get('/manage/subjects/create', [SubjectController::class, 'create']);
    Route::get('/manage/subjects/{id}/edit', [SubjectController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/subjects', [SubjectController::class, 'store']);
    Route::put('/manage/subjects/{id}', [SubjectController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/subjects/{id}', [SubjectController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/students', [StudentController::class, 'index']);
    Route::get('/manage/students/create', [StudentController::class, 'create']);
    Route::get('/manage/students/{id}/edit', [StudentController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/students', [StudentController::class, 'store']);
    Route::put('/manage/students/{id}', [StudentController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/students/{id}', [StudentController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/teachers', [TeacherController::class, 'index']);
    Route::get('/manage/teachers/create', [TeacherController::class, 'create']);
    Route::get('/manage/teachers/{id}/edit', [TeacherController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/teachers', [TeacherController::class, 'store']);
    Route::put('/manage/teachers/{id}', [TeacherController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/teachers/{id}', [TeacherController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/teacher-assignments', [TeacherAssignmentController::class, 'index']);
    Route::get('/manage/teacher-assignments/create', [TeacherAssignmentController::class, 'create']);
    Route::get('/manage/teacher-assignments/{id}/edit', [TeacherAssignmentController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/teacher-assignments', [TeacherAssignmentController::class, 'store']);
    Route::put('/manage/teacher-assignments/{id}', [TeacherAssignmentController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/teacher-assignments/{id}', [TeacherAssignmentController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/manage/enrollments/create', [EnrollmentController::class, 'create']);
    Route::get('/manage/enrollments/{id}/edit', [EnrollmentController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/enrollments', [EnrollmentController::class, 'store']);
    Route::put('/manage/enrollments/{id}', [EnrollmentController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/enrollments/{id}', [EnrollmentController::class, 'destroy'])->whereNumber('id');

    Route::get('/manage/schedules', [ScheduleController::class, 'index']);
    Route::get('/manage/schedules/create', [ScheduleController::class, 'create']);
    Route::get('/manage/schedules/{id}/edit', [ScheduleController::class, 'edit'])->whereNumber('id');
    Route::post('/manage/schedules', [ScheduleController::class, 'store']);
    Route::put('/manage/schedules/{id}', [ScheduleController::class, 'update'])->whereNumber('id');
    Route::delete('/manage/schedules/{id}', [ScheduleController::class, 'destroy'])->whereNumber('id');
});
