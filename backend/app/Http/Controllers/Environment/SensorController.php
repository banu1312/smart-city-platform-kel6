<?php
namespace App\Http\Controllers\Environment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\EnvSensorReading;
use App\Models\EnvAlert;
use App\Services\RabbitMQPublisher;
use App\Validators\SensorValidator;

class SensorController extends Controller {
    private EnvSensorReading $model;
    private EnvAlert $alertModel;
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->model      = new EnvSensorReading();
        $this->alertModel = new EnvAlert();
        $this->publisher  = new RabbitMQPublisher();
    }

    public function store(Request $request): JsonResponse {
        $data   = $request->all();
        $errors = SensorValidator::validate($data);

        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'env-service',
            ], 422);
        }

        $record = $this->model->create($data);

        try {
            $this->publisher->publish('air.new', [
                'id'          => $record['id'],
                'zone_id'     => $record['zone_id'],
                'pm25'        => $record['pm25'],
                'pm10'        => $record['pm10'],
                'no2'         => $record['no2'],
                'co'          => $record['co'],
                'o3'          => $record['o3'],
                'temperature' => $record['temperature'],
                'humidity'    => $record['humidity'],
                'timestamp'   => $record['recorded_at'],
            ]);

            if ((float)($record['pm25'] ?? 0) > 55.0) {
                $alertData = [
                    'zone_id'    => $record['zone_id'],
                    'alert_type' => 'PM2.5_HIGH',
                    'severity'   => (float)$record['pm25'] > 75 ? 'critical' : 'high',
                    'value'      => $record['pm25'],
                    'threshold'  => 55.0,
                ];
                $this->alertModel->create($alertData);
                $this->publisher->publish('anomaly.alert', [
                    'zone_id'   => $record['zone_id'],
                    'type'      => 'PM2.5_HIGH',
                    'severity'  => $alertData['severity'],
                    'value'     => $record['pm25'],
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
            'message'   => 'Sensor reading saved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'env-service',
        ], 201);
    }

    public function current(): JsonResponse {
        $data = $this->model->getLatestPerZone();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $data,
            'message'   => 'Current environment data retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'env-service',
        ]);
    }

    public function health(): JsonResponse {
        try {
            \App\Services\Database::getInstance()->query('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'env-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'env-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}