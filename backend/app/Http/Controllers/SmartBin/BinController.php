<?php
namespace App\Http\Controllers\SmartBin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\Bin;
use App\Models\BinTelemetry;
use App\Services\RabbitMQPublisher;
use App\Validators\BinValidator;

class BinController extends Controller {
    private ?RabbitMQPublisher $publisher = null;

    public function __construct() {
        try {
            $this->publisher = new RabbitMQPublisher();
        } catch (\Exception $e) {
            error_log('[RabbitMQ] Connection failed: ' . $e->getMessage());
        }
    }

    public function index(): JsonResponse {
        $bins = Bin::with(['zone', 'latestTelemetry'])->get();
        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $bins,
            'message'   => 'Bins retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ]);
    }

    public function store(Request $request): JsonResponse {
        $errors = BinValidator::validateCreate($request->all());
        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 422);
        }

        $bin = Bin::create($request->all());
        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $bin,
            'message'   => 'Bin registered',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ], 201);
    }

    public function history(int $id): JsonResponse {
        $bin = Bin::find($id);
        if (!$bin) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Bin not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 404);
        }

        $history = BinTelemetry::where('bin_id', $id)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $history,
            'message'   => 'Bin history retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ]);
    }

    public function telemetry(Request $request): JsonResponse {
        $errors = BinValidator::validateTelemetry($request->all());
        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 422);
        }

        $record = BinTelemetry::create($request->all());

        try {
            if ($this->publisher) {
                $this->publisher->publish('bin.telemetry', [
                    'telemetry_id' => $record->id,
                    'bin_id'       => $record->bin_id,
                    'zone_id'      => $record->zone_id,
                    'fill_level'   => $record->fill_level,
                    'gas_level'    => $record->gas_level,
                    'temperature'  => $record->temperature,
                    'distance_cm'  => $record->distance_cm,
                    'timestamp'    => $record->recorded_at,
                ]);

                if ((float)$record->fill_level > 80) {
                    $this->publisher->publish('bin.alert', [
                        'bin_id'     => $record->bin_id,
                        'zone_id'    => $record->zone_id,
                        'fill_level' => $record->fill_level,
                        'priority'   => (float)$record->fill_level > 95 ? 'emergency' : 'urgent',
                        'timestamp'  => now()->toIso8601String(),
                    ]);
                }

                if ($record->is_anomaly) {
                    $this->publisher->publish('bin.anomaly', [
                        'bin_id'    => $record->bin_id,
                        'zone_id'   => $record->zone_id,
                        'gas_level' => $record->gas_level,
                        'timestamp' => now()->toIso8601String(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $record,
            'message'   => 'Telemetry saved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ], 201);
    }

    public function health(): JsonResponse {
        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'smart-bin-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'smart-bin-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}