<?php
namespace App\Http\Controllers\CitizenReport;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\CitizenReport;
use App\Services\RabbitMQPublisher;
use App\Validators\ReportValidator;

class ReportController extends Controller {
    private ?RabbitMQPublisher $publisher = null;

    public function __construct() {
        try {
            $this->publisher = new RabbitMQPublisher();
        } catch (\Exception $e) {
            error_log('[RabbitMQ] Connection failed: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse {
        $errors = ReportValidator::validate($request->all());
        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-report-service',
            ], 422);
        }

        $report = CitizenReport::create($request->all());

        try {
            if ($this->publisher) {
                $this->publisher->publish('report.submitted', [
                    'report_id' => $report->id,
                    'zone_id'   => $report->zone_id,
                    'title'     => $report->title,
                    'latitude'  => $report->latitude,
                    'longitude' => $report->longitude,
                    'timestamp' => $report->created_at,
                ]);
            }
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $report,
            'message'   => 'Report submitted',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-report-service',
        ], 201);
    }

    public function getByZone(int $zoneId): JsonResponse {
        $reports = CitizenReport::where('zone_id', $zoneId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $reports,
            'message'   => 'Reports retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-report-service',
        ]);
    }

    public function health(): JsonResponse {
        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'citizen-report-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'citizen-report-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}