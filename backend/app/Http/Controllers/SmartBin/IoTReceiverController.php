<?php
namespace App\Http\Controllers\SmartBin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreTelemetryRequest;
use App\Models\TrashBin;
use App\Models\SensorLog;
use App\Models\Truck;
use App\Models\Schedule;
use App\Services\RabbitMQPublisher;
use App\Services\MLClient;

class IoTReceiverController extends Controller {

    public function storeTelemetry(StoreTelemetryRequest $request): JsonResponse {
        DB::beginTransaction();

        try {
            $bin = TrashBin::where('bin_code', $request->bin_id)->firstOrFail();

            // Update tinggi dari calibrated_height
            if ($request->calibrated_height) {
                $bin->tinggi = $request->calibrated_height;
            }

            // Update koordinat
            if ($request->latitude !== null) {
                $bin->latitude = $request->latitude;
            }
            if ($request->longitude !== null) {
                $bin->longitude = $request->longitude;
            }

            // fill_level dari IoT langsung jadi current_volume_percentage
            $volumePct = round(max(0, min(100, $request->fill_level)), 2);

            // Hitung distance_cm dari fill_level + tinggi (untuk sensor_logs)
            $binHeightCm = $bin->tinggi > 0 ? $bin->tinggi : 100;
            $distanceCm  = round($binHeightCm * (1 - $volumePct / 100), 2);

            // Delta volume dari log sebelumnya
            $prevLog = SensorLog::where('trash_bin_id', $bin->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            $deltaVolume = null;
            if ($prevLog) {
                $deltaVolume = round($volumePct - ($bin->current_volume_percentage ?? 0), 2);
            }

            $log = SensorLog::create([
                'trash_bin_id'  => $bin->id,
                'distance_cm'   => $distanceCm,
                'methane_ppm'   => $request->gas_level,
                'temperature_c' => $request->temperature,
                'delta_volume'  => $deltaVolume,
                'raw_payload'   => $request->all(),
            ]);

            // Update trash_bins kolom sesuai mapping
            $bin->current_volume_percentage = $volumePct;
            $bin->methane_gas_level         = $request->gas_level ?? $bin->methane_gas_level;
            $bin->temperature               = $request->temperature;
            $bin->save();

            DB::commit();

            Cache::forget('bins.all');
            Cache::forget("bins.{$bin->id}");

            // RabbitMQ
            $rabbitPayload = [
                'trash_bin_id'              => $bin->id,
                'bin_code'                  => $bin->bin_code,
                'current_volume_percentage' => $volumePct,
                'methane_gas_level'         => $request->gas_level,
                'temperature'               => $request->temperature,
                'delta_volume'              => $deltaVolume,
                'distance_cm'               => $distanceCm,
                'tinggi'                    => $bin->tinggi,
                'latitude'                  => $bin->latitude,
                'longitude'                 => $bin->longitude,
                'timestamp'                 => now()->toIso8601String(),
            ];

            try {
                $publisher = new RabbitMQPublisher();
                $publisher->publish('bin.updated', $rabbitPayload);

                if (($deltaVolume !== null && $deltaVolume < -5) ||
                    ($request->temperature !== null && $request->temperature > 36)) {
                    $publisher->publish('vandalism.alert', $rabbitPayload);
                }
            } catch (\Exception $e) {
                report($e);
            }

            // ML Integration
            $mlResult = $this->runMLPredictions($bin, $volumePct, $request->gas_level, $distanceCm, $deltaVolume, $request->temperature);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'data'      => [
                    'sensor_log'                => $log,
                    'current_volume_percentage' => $volumePct,
                    'delta_volume'              => $deltaVolume,
                    'bin_status'                => $bin->status,
                    'tinggi'                    => $bin->tinggi,
                    'ml_predictions'            => $mlResult,
                ],
                'message'   => 'Telemetry stored successfully',
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'error',
                'code'      => 500,
                'message'   => 'Failed to store telemetry: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
                'service'   => 'smart-bin-service',
            ], 500);
        }
    }

    private function runMLPredictions(TrashBin $bin, float $volumePct, ?float $gasLevel, float $distanceCm, ?float $deltaVolume, ?float $temperature): array {
        $result = ['fill_rate' => null, 'priority' => null, 'anomaly' => null, 'dispatch' => null];

        try {
            $ml = new MLClient();

            $hoursUntilFull = $ml->predictFillRate([
                'jam'             => (int) now()->format('H'),
                'suhu_cuaca'      => $temperature ?? 30.0,
                'volume_sekarang' => $volumePct,
                'latitude'        => $bin->latitude ?? -6.2,
                'longitude'       => $bin->longitude ?? 106.8,
                'is_weekend'      => now()->isWeekend() ? 1 : 0,
            ]);
            $result['fill_rate'] = $hoursUntilFull;

            $priority = $ml->predictPriority([
                'volume_sekarang' => $volumePct,
                'kadar_metana'    => $gasLevel ?? 0,
                'laporan_warga'   => 0,
            ]);
            $result['priority'] = $priority;

            $isAnomaly = $ml->detectAnomaly([
                'jarak_ultrasonik' => $distanceCm,
                'delta_volume_sec' => $deltaVolume ?? 0,
                'suhu_cuaca'       => $temperature ?? 30.0,
            ]);
            $result['anomaly'] = $isAnomaly;

            if ($hoursUntilFull !== null && $priority !== null && in_array($priority, ['Urgent', 'Critical'])) {
                $result['dispatch'] = $this->tryAutoDispatch($bin->id, $priority, $hoursUntilFull);
            }

        } catch (\Exception $e) {
            report($e);
        }

        return $result;
    }

    private function tryAutoDispatch(int $trashBinId, string $priority, float $hoursUntilFull): ?array {
        $alreadyPending = Schedule::where('trash_bin_id', $trashBinId)
            ->whereIn('execution_status', ['Pending', 'In-Progress'])
            ->exists();

        if ($alreadyPending) {
            return ['skipped' => 'Schedule already exists for this bin'];
        }

        $truck = Truck::where('current_status', 'Available')->first();

        if (! $truck) {
            return ['skipped' => 'No available trucks'];
        }

        DB::beginTransaction();
        try {
            $schedule = Schedule::create([
                'trash_bin_id'        => $trashBinId,
                'truck_id'            => $truck->id,
                'scheduled_at'        => now()->addHours($hoursUntilFull),
                'priority_level'      => $priority,
                'execution_status'    => 'Pending',
                'estimated_hours_full' => $hoursUntilFull,
            ]);

            $truck->current_status = 'On-Route';
            $truck->save();

            DB::commit();

            Log::info("[AutoDispatch] Bin {$trashBinId} → Truck {$truck->license_plate}, priority={$priority}, ETA={$hoursUntilFull}h");

            return [
                'schedule_id' => $schedule->id,
                'truck'       => $truck->license_plate,
                'driver'      => $truck->driver_name,
                'priority'    => $priority,
                'eta_hours'   => $hoursUntilFull,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return ['error' => 'Dispatch failed'];
        }
    }
}
