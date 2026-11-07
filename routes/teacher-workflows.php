<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::put('/attendance/sessions/{session}/bulk-records', [AttendanceController::class, 'bulkRecord']);
Route::put('/assignments/{assignment}/grades', [AssignmentController::class, 'bulkGrade']);
