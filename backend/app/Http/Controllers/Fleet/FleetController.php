<?php
namespace App\Http\Controllers\Fleet;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\Truck;
use App\Models\DispatchTask;
use App\Services\RabbitMQPublisher;
use App\Validators\FleetValidator;

class FleetController extends Controller {
    private ?RabbitMQPublisher $publisher = null;

    public function __construct() {
        try {
            $this->publisher = new RabbitMQPublisher();
        } catch (\Exception $e) {
            error_log('[RabbitMQ] Connection failed: ' . $e->getMessage());
        }
    }

    public function trucks(): JsonResponse {
        $trucks = Truck::with('zone')->get();
        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $trucks,
            'message'   => 'Trucks retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ]);
    }

    public function dispatch(Request $request): JsonResponse {
        $errors = FleetValidator::validateDispatch($request->all());
        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        $task = DispatchTask::create($request->all());

        try {
            if ($this->publisher) {
                $this->publisher->publish('fleet.dispatched', [
                    'task_id'   => $task->id,
                    'truck_id'  => $task->truck_id,
                    'bin_id'    => $task->bin_id,
                    'zone_id'   => $task->zone_id,
                    'priority'  => $task->priority,
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        } catch (\Exception $e) {
            error_log('[RabbitMQ Error] ' . $e->getMessage());
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $task,
            'message'   => 'Dispatch task created',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ], 201);
    }

    public function updateTaskStatus(Request $request, int $id): JsonResponse {
        $errors = FleetValidator::validateStatus($request->all());
        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        $task = DispatchTask::find($id);
        if (!$task) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Task not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 404);
        }

        $task->status = $request->status;
        if ($request->status === 'completed') {
            $task->completed_at = now();
        }
        $task->save();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $task,
            'message'   => 'Task status updated',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ]);
    }

    public function health(): JsonResponse {
        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'fleet-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'fleet-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}