<?php
namespace App\Http\Controllers\Fleet;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Truck;
use App\Models\Schedule;

class FleetManagerController extends Controller {

    // GET /api/fleet/trucks
    public function index(): JsonResponse {
        $trucks = Truck::with([
            'schedules' => fn($q) => $q->where('execution_status', '!=', 'Completed')
                                        ->orderBy('scheduled_at')
        ])->get();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $trucks,
            'message'   => 'Truck fleet retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ]);
    }

    // POST /api/fleet/trucks
    // nambah armada truk baru
    public function store(Request $request): JsonResponse {
        $required = ['license_plate', 'driver_name'];
        $errors   = [];

        foreach ($required as $field) {
            if (empty($request->input($field))) {
                $errors[] = "$field is required";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        // Cek duplikat license plate
        if (Truck::where('license_plate', $request->license_plate)->exists()) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => ['license_plate already registered'],
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        $truck = Truck::create([
            'license_plate'  => $request->license_plate,
            'driver_name'    => $request->driver_name,
            'max_capacity_ton'=> $request->max_capacity_ton ?? 2.0,
            'current_status' => 'Available',
        ]);

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $truck,
            'message'   => 'Truck registered successfully',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ], 201);
    }   

    // POST /api/fleet/driver-checkin
    // supir ubah status dari off-duty jadi available
    public function driverCheckIn(Request $request): JsonResponse {
        $truck = Truck::where('license_plate', $request->license_plate)->first();

        if (!$truck) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Truck not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 404);
        }

        $truck->current_status = 'Available';
        $truck->save();

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $truck,
            'message'   => "Driver {$truck->driver_name} checked in",
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ]);
    }

    // PUT /api/fleet/schedules/{id}/status
    // supir update status jadwal via aplikasi mobile
    public function updateScheduleStatus(Request $request, int $id): JsonResponse {
        $allowed  = ['Pending', 'In-Progress', 'Completed'];
        $newStatus = $request->input('execution_status');

        if (!in_array($newStatus, $allowed)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'message'   => 'execution_status must be one of: ' . implode(', ', $allowed),
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Schedule not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 404);
        }

        // pakai method updateStatus() dari model 
        $success = $schedule->updateStatus($newStatus);

        if (!$success) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'message'   => 'Failed to update status',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 422);
        }

        // kalau selesai, kembalikan status truk jadi available
        if ($newStatus === 'Completed') {
            $schedule->truck?->update(['current_status' => 'Available']);
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $schedule->fresh(['truck', 'trashBin']),
            'message'   => 'Schedule status updated',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ]);
    }

    // GET /api/fleet/health
    public function health(): JsonResponse {
        try {
            DB::select('SELECT 1');
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