<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ClassifierController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// This registers /login, /logout, /register — all Breeze routes
require __DIR__.'/auth.php';

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/',                      [GeneratorController::class, 'index'])->name('dashboard');
    Route::get('/generators/{id}',       [GeneratorController::class, 'show'])->name('generator.show');
    Route::get('/tickets',               [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets/{id}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
    Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
    Route::get('/history', [TicketController::class, 'history'])->name('tickets.history');
    Route::get('/classifier', [ClassifierController::class, 'index'])->name('classifier.index');
    Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
});
