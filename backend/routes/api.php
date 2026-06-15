<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmartBin\BinController;
use App\Http\Controllers\Fleet\FleetController;
use App\Http\Controllers\CitizenReport\ReportController;

// ── HEALTH CHECKS ──────────────────────────────────────
Route::get('/smart-bin/health',      [BinController::class,   'health']);
Route::get('/fleet/health',          [FleetController::class, 'health']);
Route::get('/citizen-report/health', [ReportController::class,'health']);

// ── SMART BIN SERVICE ──────────────────────────────────
Route::get('/bins',                [BinController::class, 'index']);
Route::post('/bins',               [BinController::class, 'store']);
Route::get('/bins/{id}/history',   [BinController::class, 'history']);
Route::post('/bins/telemetry',     [BinController::class, 'telemetry']);

// ── FLEET SERVICE ──────────────────────────────────────
Route::get('/fleet/trucks',              [FleetController::class, 'trucks']);
Route::post('/fleet/dispatch',           [FleetController::class, 'dispatch']);
Route::put('/fleet/tasks/{id}/status',   [FleetController::class, 'updateTaskStatus']);

// ── CITIZEN REPORT SERVICE ─────────────────────────────
Route::post('/reports',                [ReportController::class, 'store']);
Route::get('/reports/zone/{zone_id}',  [ReportController::class, 'getByZone']);