<?php
namespace App\Http\Controllers\CitizenReport;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreReportRequest;
use App\Models\SanitationReport;
use App\Services\RabbitMQPublisher;

class ReportSubmissionController extends Controller {
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->publisher = new RabbitMQPublisher();
    }

    // POST /api/reports
    public function store(StoreReportRequest $request): JsonResponse {
        // simpan foto ke storage
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path     = Storage::disk('public')->put('sanitation-reports', $request->file('photo'));
            $photoUrl = Storage::url($path);
        }

        $report = SanitationReport::create([
            'reporter_name'    => $request->reporter_name,
            'reporter_phone'   => $request->reporter_phone,
            'issue_description'=> $request->issue_description,
            'photo_url'        => $photoUrl,
            'geo_coordinate'   => $request->geo_coordinate,
            'verification_status'=> 'Pending',
        ]);

        try {
            $this->publisher->publish('report.submitted', [
                'report_id'      => $report->id,
                'reporter_name'  => $report->reporter_name,
                'geo_coordinate' => $report->geo_coordinate,
                'timestamp'      => $report->created_at,
            ]);
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $report,
            'message'   => 'Sanitation report submitted',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-report-service',
        ], 201);
    }

    // GET /api/citizen-report/health
    public function health(): JsonResponse {
        try {
            DB::select('SELECT 1');
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