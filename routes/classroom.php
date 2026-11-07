<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

// Classroom & Dedicated Learning Content Routes
Route::get('/classes/{classroom}', [ClassController::class, 'show']);

Route::get('/classes/{classroom}/content/lessons/create', [LessonController::class, 'create']);
Route::get('/classes/{classroom}/content/lessons/{id}/edit', [LessonController::class, 'edit'])->whereNumber('id');
Route::post('/classes/{classroom}/content/lessons', [LessonController::class, 'store']);
Route::put('/classes/{classroom}/content/lessons/{id}', [LessonController::class, 'update'])->whereNumber('id');
Route::delete('/classes/{classroom}/content/lessons/{id}', [LessonController::class, 'destroy'])->whereNumber('id');

Route::get('/classes/{classroom}/content/materials/create', [MaterialController::class, 'create']);
Route::get('/classes/{classroom}/content/materials/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id');
Route::post('/classes/{classroom}/content/materials', [MaterialController::class, 'store']);
Route::put('/classes/{classroom}/content/materials/{id}', [MaterialController::class, 'update'])->whereNumber('id');
Route::delete('/classes/{classroom}/content/materials/{id}', [MaterialController::class, 'destroy'])->whereNumber('id');

Route::get('/classes/{classroom}/content/assignments/create', [AssignmentController::class, 'create']);
Route::get('/classes/{classroom}/content/assignments/{id}/edit', [AssignmentController::class, 'edit'])->whereNumber('id');
Route::post('/classes/{classroom}/content/assignments', [AssignmentController::class, 'store']);
Route::put('/classes/{classroom}/content/assignments/{id}', [AssignmentController::class, 'update'])->whereNumber('id');
Route::delete('/classes/{classroom}/content/assignments/{id}', [AssignmentController::class, 'destroy'])->whereNumber('id');

Route::get('/classes/{classroom}/content/quizzes/create', [QuizController::class, 'create']);
Route::get('/classes/{classroom}/content/quizzes/{id}/edit', [QuizController::class, 'edit'])->whereNumber('id');
Route::post('/classes/{classroom}/content/quizzes', [QuizController::class, 'store']);
Route::put('/classes/{classroom}/content/quizzes/{id}', [QuizController::class, 'update'])->whereNumber('id');
Route::delete('/classes/{classroom}/content/quizzes/{id}', [QuizController::class, 'destroy'])->whereNumber('id');

Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit']);
Route::put('/submissions/{submission}/grade', [AssignmentController::class, 'grade']);
Route::get('/files/materials/{id}', [MaterialController::class, 'download'])->whereNumber('id');
Route::get('/files/assignments/{id}', [AssignmentController::class, 'downloadAssignment'])->whereNumber('id');
Route::get('/files/submissions/{id}', [AssignmentController::class, 'downloadSubmission'])->whereNumber('id');

Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
Route::post('/quizzes/{quiz}/questions', [QuizController::class, 'question']);
Route::put('/quizzes/{quiz}/questions/{id}', [QuizController::class, 'question'])->whereNumber('id');
Route::delete('/quizzes/{quiz}/questions/{id}', [QuizController::class, 'deleteQuestion'])->whereNumber('id');
Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start']);
Route::get('/attempts/{attempt}', [QuizController::class, 'attempt']);
Route::post('/attempts/{attempt}', [QuizController::class, 'submit']);

Route::post('/classes/{classroom}/attendance', [AttendanceController::class, 'create']);
Route::get('/attendance/sessions/{session}', [AttendanceController::class, 'show']);
Route::post('/attendance/sessions/{session}/records', [AttendanceController::class, 'record']);
Route::put('/attendance/sessions/{session}/records/{studentId}', [AttendanceController::class, 'record'])->whereNumber('studentId');
Route::post('/attendance/{record}/excuse', [AttendanceController::class, 'excuse']);
Route::put('/excuses/{excuse}', [AttendanceController::class, 'review']);

