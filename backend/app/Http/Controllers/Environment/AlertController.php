<?php
namespace App\Http\Controllers\Environment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\EnvAlert;

class AlertController extends Controller {
    private EnvAlert $model;

    public function __construct() {
        $this->model = new EnvAlert();
    }

    public function index(Request $request): JsonResponse {
        $filters = [
            'zone_id'  => $request->query('zone_id'),
            'severity' => $request->query('severity'),
        ];

        $data = $this->model->getActive($filters);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $data,
            'message'   => 'Active alerts retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'env-service',
        ]);
    }
}