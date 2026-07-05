<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GeneratorApiController;

// ── Generators ───────────────────────────────────────────────────
Route::get('/generators',                      [GeneratorApiController::class, 'index']);
Route::get('/generators/{id}',                 [GeneratorApiController::class, 'show']);
Route::get('/generators/{id}/telemetry',       [GeneratorApiController::class, 'telemetry']);
Route::get('/generators/{id}/anomalies',       [GeneratorApiController::class, 'anomalies']);
Route::get('/generators/{id}/tickets',         [GeneratorApiController::class, 'tickets']);
Route::get('/generators/{id}/summary',         [GeneratorApiController::class, 'summary']);

// ── Tickets ──────────────────────────────────────────────────────
Route::get('/tickets',                         [GeneratorApiController::class, 'allTickets']);