<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LearningHubController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index']);

Route::get('/setup/{token}', SetupController::class);

require __DIR__.'/auth.php';

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    require __DIR__.'/teacher-workflows.php';
    require __DIR__.'/academic-setup.php';

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::redirect('/admin/dashboard', '/dashboard')->middleware('role:admin');
    Route::redirect('/teacher/dashboard', '/dashboard')->middleware('role:teacher');
    Route::redirect('/student/dashboard', '/dashboard')->middleware('role:student');

    Route::get('/classes', [DashboardController::class, 'classes']);
    Route::get('/learning', [LearningHubController::class, 'index'])->name('learning.index');
    Route::get('/schedule', [DashboardController::class, 'schedule']);
    Route::get('/search', [DashboardController::class, 'search']);

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    Route::post('/profile/two-factor', [AuthController::class, 'toggleTwoFactor']);
    Route::get('/profile/photo', [AuthController::class, 'photo']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

    require __DIR__.'/classroom.php';

    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'save']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'save'])->whereNumber('id');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'delete']);

    Route::get('/reports/attendance', [ReportController::class, 'attendance']);
    Route::get('/reports/grades', [ReportController::class, 'grades']);
    Route::get('/reports/students', [ReportController::class, 'students']);
    Route::get('/reports/enrollments', [ReportController::class, 'enrollments']);
    Route::get('/early-warning', [EarlyWarningController::class, 'index'])->name('early-warning.index');
    Route::get('/classes/{classroom}/gradebook', [ReportController::class, 'gradebook']);

    require __DIR__.'/dataset.php';
    require __DIR__.'/admin.php';
});
