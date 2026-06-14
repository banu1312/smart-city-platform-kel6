<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Citizen\CitizenController;
use App\Http\Controllers\Citizen\ReportController;
use App\Http\Controllers\Citizen\NotifController;
use App\Http\Controllers\Waste\WasteController;
use App\Http\Controllers\Waste\IncidentController;
use App\Http\Controllers\Environment\SensorController;
use App\Http\Controllers\Environment\AlertController;

// ── HEALTH CHECKS ──────────────────────────────────────
Route::get('/citizens/health', [CitizenController::class, 'health']);
Route::get('/waste/health',    [WasteController::class,  'health']);
Route::get('/environment/health', [SensorController::class, 'health']);

// ── CITIZEN SERVICE ────────────────────────────────────
Route::post('/citizens',              [CitizenController::class, 'store']);
Route::get('/citizens/{id}',          [CitizenController::class, 'show']);
Route::post('/reports',               [ReportController::class,  'store']);
Route::get('/reports',                [ReportController::class,  'index']);
Route::patch('/reports/{id}/status',  [ReportController::class,  'updateStatus']);
Route::get('/notifications',          [NotifController::class,   'index']);

// ── WASTE SERVICE ──────────────────────────────────────
Route::post('/waste/readings',   [WasteController::class,    'store']);
Route::get('/waste/current',     [WasteController::class,    'current']);
Route::get('/waste/history',     [WasteController::class,    'history']);
Route::get('/waste/incidents',   [IncidentController::class, 'index']);
Route::post('/waste/incidents',  [IncidentController::class, 'store']);

// ── ENVIRONMENT SERVICE ────────────────────────────────
Route::post('/environment/readings', [SensorController::class, 'store']);
Route::get('/environment/current',   [SensorController::class, 'current']);
Route::get('/environment/alerts',    [AlertController::class,  'index']);