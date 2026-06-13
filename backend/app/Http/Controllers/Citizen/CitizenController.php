<?php
namespace App\Http\Controllers\Citizen;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\Citizen;
use App\Validators\CitizenValidator;

class CitizenController extends Controller {
    private Citizen $model;

    public function __construct() {
        $this->model = new Citizen();
    }

    public function store(Request $request): JsonResponse {
        $data   = $request->all();
        $errors = CitizenValidator::validate($data);

        if (!empty($errors)) {
            return response()->json([
                'status'    => 'error',
                'code'      => 422,
                'errors'    => $errors,
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 422);
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $record = $this->model->create($data);
        unset($record['password']);

        return response()->json([
            'status'    => 'success',
            'code'      => 201,
            'data'      => $record,
            'message'   => 'Citizen registered successfully',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ], 201);
    }

    public function show(int $id): JsonResponse {
        $record = $this->model->findById($id);

        if (!$record) {
            return response()->json([
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Citizen not found',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'citizen-service',
            ], 404);
        }

        unset($record['password']);

        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'data'      => $record,
            'message'   => 'Citizen retrieved',
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-service',
        ], 200);
    }

    public function health(): JsonResponse {
        try {
            \App\Services\Database::getInstance()->query('SELECT 1');
            return response()->json([
                'status'    => 'ok',
                'service'   => 'citizen-service',
                'db'        => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'service'   => 'citizen-service',
                'db'        => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}