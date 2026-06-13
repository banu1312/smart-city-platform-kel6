<?php
namespace App\Http\Controllers\Waste;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\WasteIncident;
use App\Services\RabbitMQPublisher;

class IncidentController extends Controller {
    private WasteIncident $model;
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->model     = new WasteIncident();
        $this->publisher = new RabbitMQPublisher();
    }

    public function index(Request $request): JsonResponse {
        $filters = [
            'zone_id'  => $request->query('zone_id'),
            'severity' => $request->query('severity'),
        ];

        $data = $this->model->getAll($filters);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $data,
            'message'   => 'Incidents retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'waste-service',
        ]);
    }

    public function store(Request $request): JsonResponse {
        $data = $request->all();

        if (empty($data['zone_id']) || empty($data['type']) || empty($data['severity'])) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'message'   => 'zone_id, type, severity are required',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'waste-service',
            ], 422);
        }

        $record = $this->model->create($data);

        try {
            $this->publisher->publish('anomaly.alert', [
                'zone_id'   => $record['zone_id'],
                'type'      => $record['type'],
                'severity'  => $record['severity'],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $record,
            'message'   => 'Incident created',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'waste-service',
        ], 201);
    }
}