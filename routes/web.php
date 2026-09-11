<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;

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

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/_test', fn () => 'admin guard OK: ' . auth('admin')->user()->name);
});
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


