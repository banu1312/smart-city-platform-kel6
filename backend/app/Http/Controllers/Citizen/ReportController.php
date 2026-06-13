<?php
namespace App\Http\Controllers\Citizen;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\Report;
use App\Services\RabbitMQPublisher;
use App\Validators\ReportValidator;

class ReportController extends Controller {
    private Report $model;
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->model     = new Report();
        $this->publisher = new RabbitMQPublisher();
    }

    public function store(Request $request): JsonResponse {
        $data   = $request->all();
        $errors = ReportValidator::validate($data);

        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 422);
        }

        $record = $this->model->create($data);

        try {
            $this->publisher->publish('report.submitted', [
                'report_id'  => $record['id'],
                'citizen_id' => $record['citizen_id'],
                'zone_id'    => $record['zone_id'],
                'category'   => $record['category'],
                'timestamp'  => $record['created_at'],
            ]);
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $record,
            'message'   => 'Report submitted successfully',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ], 201);
    }

    public function index(Request $request): JsonResponse {
        $filters = [
            'status'  => $request->query('status'),
            'zone_id' => $request->query('zone_id'),
        ];

        $records = $this->model->getAll($filters);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $records,
            'message'   => 'Reports retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse {
        $allowed = ['pending', 'in_progress', 'resolved'];
        $status  = $request->input('status');

        if (empty($status) || !in_array($status, $allowed)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'message'   => 'Invalid status value',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 422);
        }

        $record = $this->model->updateStatus($id, $status);

        if (!$record) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Report not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 404);
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $record,
            'message'   => 'Report status updated',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ]);
    }
}