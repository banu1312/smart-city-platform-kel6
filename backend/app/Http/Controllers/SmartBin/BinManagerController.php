<?php
namespace App\Http\Controllers\SmartBin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\TrashBin;

class BinManagerController extends Controller {

    // GET /api/bins
    public function index(): JsonResponse {
        $bins = Cache::remember('bins.all', 30, function () {
            return TrashBin::with('latestLog')->get();
        });

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $bins,
            'message'   => 'Trash bins retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ]);
    }

    // GET /api/bins/{id}
    public function show(int $id): JsonResponse {
        $bin = Cache::remember("bins.$id", 30, function () use ($id) {
            return TrashBin::with([
                'latestLog',
                'sensorLogs' => fn($q) => $q->orderBy('recorded_at','desc')->limit(5)
            ])->find($id);
        });

        if (!$bin) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Trash bin not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 404);
        }

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $bin,
            'message'   => 'Trash bin detail retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ]);
    }

    // PUT /api/bins/{id}/maintenance
    public function updateMaintenanceStatus(int $id): JsonResponse {
        $bin = TrashBin::find($id);

        if (!$bin) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Trash bin not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 404);
        }

        $bin->status = 'Maintenance';
        $bin->save();

        Cache::forget('bins.all');
        Cache::forget("bins.$id");

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $bin,
            'message'   => 'Bin status updated to Maintenance',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ]);
    }

    // GET /api/smart-bin/health
    public function health(): JsonResponse {
        try {
            DB::select('SELECT 1');
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