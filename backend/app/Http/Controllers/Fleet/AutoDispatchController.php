<?php
namespace App\Http\Controllers\Fleet;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AutoDispatchRequest;
use App\Models\Truck;
use App\Models\Schedule;

class AutoDispatchController extends Controller {

    // POST /api/fleet/auto-dispatch
    public function receivePrediction(AutoDispatchRequest $request): JsonResponse {
        DB::beginTransaction();

        try {
            // mencari truk available 
            $truck = Truck::where('current_status', 'Available')->first();

            if (!$truck) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'error',
                    'code'      => 503,
                    'message'   => 'No available trucks at the moment',
                    'timestamp' => now()->toIso8601String(),
                    'service'   => 'fleet-service',
                ], 503);
            }

            // buat jadwal penjemputan 
            $schedule = Schedule::create([
                'trash_bin_id'       => $request->trash_bin_id,
                'truck_id'           => $truck->id,
                'scheduled_at'       => now()->addHours($request->hours_until_full),
                'priority_level'     => $request->pickup_priority,
                'execution_status'   => 'Pending',
                'estimated_hours_full'=> $request->hours_until_full,
            ]);

            // update status truk jadi On-Route
            $truck->current_status = 'On-Route';
            $truck->save();

            DB::commit();

            // mock push notification ke aplikasi supir
            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'data'      => [
                    'schedule'          => $schedule->load('truck'),
                    'notification'      => [
                        'driver'    => $truck->driver_name,
                        'message'   => "Tugas Penjemputan Baru Tersedia",
                        'bin_id'    => $request->trash_bin_id,
                        'priority'  => $request->pickup_priority,
                        'eta_hours' => $request->hours_until_full,
                    ],
                ],
                'message'   => 'AI prediction received, schedule created',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'error',
                'code'      => 500,
                'message'   => 'Failed: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
                'service'   => 'fleet-service',
            ], 500);
        }
    }
}