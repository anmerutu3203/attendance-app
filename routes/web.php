<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 管理者認証（admin ガード）
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
        ->middleware('guest:admin')
        ->name('admin.login');

    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('guest:admin');

    Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('admin.logout');
});

// 管理者機能（admin ガード）
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/_test', fn () => 'admin guard OK: ' . auth('admin')->user()->name);
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/attendance/staff/{user}', [StaffController::class, 'show'])->name('admin.staff.show');
});

// 一般ユーザー機能（web ガード）
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    
     Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('application.index');
    Route::get('/application/{attendanceCorrectionRequest}', [ApplicationController::class, 'show'])->name('application.show');
});

Route::middleware('auth:web,admin')->group(function () {
    Route::get('/attendance/{attendanceRecord}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendanceRecord}', [AttendanceController::class, 'update'])->name('attendance.update');
});