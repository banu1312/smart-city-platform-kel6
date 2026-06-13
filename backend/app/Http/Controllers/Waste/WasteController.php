<?php
namespace App\Http\Controllers\Waste;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\WasteBinReading;
use App\Services\RabbitMQPublisher;
use App\Validators\WasteValidator;

class WasteController extends Controller {
    private WasteBinReading $model;
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->model     = new WasteBinReading();
        $this->publisher = new RabbitMQPublisher();
    }

    public function store(Request $request): JsonResponse {
        $data   = $request->all();
        $errors = WasteValidator::validate($data);

        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'waste-service',
            ], 422);
        }

        $record = $this->model->create($data);

        try {
            $this->publisher->publish('waste.new', [
                'id'          => $record['id'],
                'zone_id'     => $record['zone_id'],
                'fill_level'  => $record['fill_level'],
                'gas_level'   => $record['gas_level'],
                'temperature' => $record['temperature'],
                'timestamp'   => $record['recorded_at'],
            ]);

            if ((float)$record['fill_level'] > 80) {
                $this->publisher->publish('waste.alert', [
                    'zone_id'   => $record['zone_id'],
                    'type'      => 'overflow_warning',
                    'severity'  => (float)$record['fill_level'] > 95 ? 'critical' : 'high',
                    'value'     => $record['fill_level'],
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $record,
            'message'   => 'Waste reading saved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'waste-service',
        ], 201);
    }

    public function current(): JsonResponse {
        $data = $this->model->getLatestPerZone();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $data,
            'message'   => 'Current waste levels retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'waste-service',
        ]);
    }

    public function history(Request $request): JsonResponse {
        $filters = [
            'zone_id'   => $request->query('zone_id'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
        ];

        $data = $this->model->getHistory($filters);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $data,
            'message'   => 'Waste history retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'waste-service',
        ]);
    }

    public function health(): JsonResponse {
        try {
            \App\Services\Database::getInstance()->query('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'waste-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'waste-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}