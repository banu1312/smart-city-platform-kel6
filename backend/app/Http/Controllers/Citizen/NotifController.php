<?php
namespace App\Http\Controllers\Citizen;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\Notification;

class NotifController extends Controller {
    private Notification $model;

    public function __construct() {
        $this->model = new Notification();
    }

    public function index(Request $request): JsonResponse {
        $citizenId = $request->query('citizen_id');

        if (!$citizenId) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'citizen_id is required',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 400);
        }

        $records = $this->model->getByCitizen((int)$citizenId);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $records,
            'message'   => 'Notifications retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ]);
    }
}