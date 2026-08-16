<?php

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentNotificationController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReportController::class, 'index'])->name('home');
Route::get('/reports/public-status', [ReportController::class, 'publicReportStatus'])->name('reports.public-status');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/student', StudentDashboardController::class)->name('student.dashboard');
    Route::get('/student/profile', [StudentProfileController::class, 'edit'])->name('student.profile');
    Route::patch('/student/profile', [StudentProfileController::class, 'update'])->name('student.profile.update');
    Route::get('/student/notifications/unread', [StudentNotificationController::class, 'unread'])->name('student.notifications.unread');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{itemReport}', [ReportController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{itemReport}/claim', [ReportController::class, 'claim'])->name('reports.claim');
    Route::delete('/reports/{itemReport}', [ReportController::class, 'destroy'])->name('reports.destroy');

    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('dashboard');
        Route::patch('/reports/{itemReport}/approve', [AdminReportController::class, 'approve'])->name('reports.approve');
        Route::patch('/reports/{itemReport}/reject', [AdminReportController::class, 'reject'])->name('reports.reject');
        Route::patch('/reports/{itemReport}/block', [AdminReportController::class, 'block'])->name('reports.block');
        Route::patch('/reports/{itemReport}/confirm-claim', [AdminReportController::class, 'confirmClaim'])->name('reports.confirm-claim');
        Route::patch('/reports/{itemReport}/close', [AdminReportController::class, 'close'])->name('reports.close');
        Route::patch('/reports/{itemReport}/archive', [AdminReportController::class, 'archive'])->name('reports.archive');
    });
});
