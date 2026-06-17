<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmartBin\BinManagerController;
use App\Http\Controllers\SmartBin\IoTReceiverController;
use App\Http\Controllers\Fleet\FleetManagerController;
use App\Http\Controllers\Fleet\AutoDispatchController;
use App\Http\Controllers\CitizenReport\ReportSubmissionController;
use App\Http\Controllers\CitizenReport\ReportDispatchController;

// HEALTH CHECKS                                         
Route::get('/smart-bin/health',      [BinManagerController::class,      'health']);
Route::get('/fleet/health',          [FleetManagerController::class,    'health']);
Route::get('/citizen-report/health', [ReportSubmissionController::class,'health']);

// SERVICE 1: SMART BIN                                  
// BinManagerController
Route::get('/bins',                  [BinManagerController::class, 'index']);
Route::get('/bins/{id}',             [BinManagerController::class, 'show']);
Route::put('/bins/{id}/maintenance', [BinManagerController::class, 'updateMaintenanceStatus']);

// IoTReceiverController (ditembak Node-RED via Gateway)
Route::post('/iot/telemetry',        [IoTReceiverController::class, 'storeTelemetry']);

// SERVICE 2: FLEET                                      
// FleetManagerController
Route::get('/fleet/trucks',                    [FleetManagerController::class, 'index']);
Route::post('/fleet/trucks',                   [FleetManagerController::class, 'store']);
Route::post('/fleet/driver-checkin',           [FleetManagerController::class, 'driverCheckIn']);
Route::put('/fleet/schedules/{id}/status',     [FleetManagerController::class, 'updateScheduleStatus']);

// AutoDispatchController (ditembak Python FastAPI ML)
Route::post('/fleet/auto-dispatch',            [AutoDispatchController::class, 'receivePrediction']);

// SERVICE 3: CITIZEN REPORT                              
// ReportSubmissionController (warga submit laporan)
Route::post('/reports',                        [ReportSubmissionController::class, 'store']);

// ReportDispatchController (admin verifikasi n dispatch)
Route::post('/reports/{id}/verify',            [ReportDispatchController::class, 'verify']);
Route::post('/reports/{id}/dispatch',          [ReportDispatchController::class, 'dispatch']);