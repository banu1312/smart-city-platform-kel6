<?php
namespace App\Http\Controllers\CitizenReport;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\SanitationReport;
use App\Models\Schedule;
use App\Models\Truck;

class ReportDispatchController extends Controller {

    // POST /api/reports/{id}/verify
    // disini admin verifikasi laporan warga 
    public function verify(int $id): JsonResponse {
        $report = SanitationReport::find($id);

        if (!$report) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Report not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-report-service',
            ], 404);
        }

        $success = $report->verifyReport();

        if (!$success) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'message'   => 'Report cannot be verified (may already be processed)',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-report-service',
            ], 422);
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $report,
            'message'   => 'Report verified',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-report-service',
        ]);
    }

    // POST /api/reports/{id}/dispatch
    // admin assign truk ke laporan warga yang sudah diverifikasi
    public function dispatch(int $id): JsonResponse {
        DB::beginTransaction();

        try {
            $report = SanitationReport::find($id);

            if (!$report) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => 'Report not found',
                    'timestamp' => now()->toIso8601String(),
                    'service'   => 'citizen-report-service',
                ], 404);
            }

            if ($report->verification_status !== 'Reviewed') {
                DB::rollBack();
                return response()->json([
                    'status'    => 'error',
                    'code'      => 422,
                    'message'   => 'Report must be Reviewed first',
                    'timestamp' => now()->toIso8601String(),
                    'service'   => 'citizen-report-service',
                ], 422);
            }

            $truck = Truck::where('current_status', 'Available')->first();

            if (!$truck) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'error',
                    'code'      => 503,
                    'message'   => 'No available trucks',
                    'timestamp' => now()->toIso8601String(),
                    'service'   => 'citizen-report-service',
                ], 503);
            }

            // update status laporan
            $report->verification_status = 'Dispatched';
            $report->save();

            // cross-service: buat jadwal di tabel schedules
            // trash_bin_id = 0 karena ini dari laporan warga bukan sensor
            $schedule = Schedule::create([
                'trash_bin_id'   => 0,
                'truck_id'       => $truck->id,
                'scheduled_at'   => now(),
                'priority_level' => 'Urgent',
                'execution_status'=> 'Pending',
            ]);

            // update status truk
            $truck->current_status = 'On-Route';
            $truck->save();

            DB::commit();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'data'      => [
                    'report'   => $report,
                    'schedule' => $schedule,
                    'truck'    => $truck,
                ],
                'message'   => "Report dispatched to driver {$truck->driver_name}",
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-report-service',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'error',
                'code'      => 500,
                'message'   => 'Dispatch failed: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-report-service',
            ], 500);
        }
    }
}