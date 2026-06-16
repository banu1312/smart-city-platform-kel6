<?php
namespace App\Http\Controllers\SmartBin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreTelemetryRequest;
use App\Models\TrashBin;
use App\Models\SensorLog;
use App\Services\RabbitMQPublisher;

class IoTReceiverController extends Controller {
    private RabbitMQPublisher $publisher;

    public function __construct() {
        $this->publisher = new RabbitMQPublisher();
    }

    // POST /api/iot/telemetry (ini yang bakal ditembak node-RED setiap 30 detik)
    public function storeTelemetry(StoreTelemetryRequest $request): JsonResponse {
        DB::beginTransaction();

        try {
            $bin = TrashBin::findOrFail($request->trash_bin_id);

            // ngambil log sebelumnya untuk hitung delta_volume
            $prevLog = SensorLog::where('trash_bin_id', $bin->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            // kalkulasi current_volume_percentage dari distance_cm
            // rumus: tinggi tong (misal 100cm) - jarak sensor = tinggi sampah
            // jarak 20cm = sampah setinggi 80cm = 80% penuh
            $binHeightCm        = $bin->capacity_liters > 0
                                    ? $bin->capacity_liters / 1  // asumsi 1 liter per cm
                                    : 100;
            $volumePct          = round((($binHeightCm - $request->distance_cm) / $binHeightCm) * 100, 2);
            $volumePct          = max(0, min(100, $volumePct));

            // hitung delta_volume (perubahan volume dari log sebelumnya)
            $deltaVolume = null;
            if ($prevLog) {
                $prevVolumePct   = round((($binHeightCm - $prevLog->distance_cm) / $binHeightCm) * 100, 2);
                $deltaVolume     = round($volumePct - $prevVolumePct, 2);
            }

            // simpan ke sensor_logs
            $log = SensorLog::create([
                'trash_bin_id' => $bin->id,
                'distance_cm'  => $request->distance_cm,
                'methane_ppm'  => $request->methane_ppm,
                'temperature_c'=> $request->temperature_c,
                'delta_volume' => $deltaVolume,
                'raw_payload'  => $request->raw_payload ?? $request->all(),
            ]);

            // update trash_bins dengan nilai terbaru
            $bin->current_volume_percentage = $volumePct;
            $bin->methane_gas_level         = $request->methane_ppm ?? $bin->methane_gas_level;
            $bin->save();

            DB::commit();

            // Bersihkan cache
            Cache::forget('bins.all');
            Cache::forget("bins.{$bin->id}");

            // publish bin.updated ke RabbitMQ — Python ML consume ini
            $payload = [
                'trash_bin_id'              => $bin->id,
                'bin_code'                  => $bin->bin_code,
                'current_volume_percentage' => $volumePct,
                'methane_gas_level'         => $request->methane_ppm,
                'temperature_c'             => $request->temperature_c,
                'delta_volume'              => $deltaVolume,
                'distance_cm'               => $request->distance_cm,
                'timestamp'                 => now()->toIso8601String(),
            ];

            try {
                // selalu publish bin.updated untuk ML consume
                $this->publisher->publish('bin.updated', $payload);

                // rule 1: vandalisme atau kebakaran (anomali)
                // delta_volume < -5 = sampah tiba-tiba berkurang (mungkin dibakar/dicuri)
                // temperature_c > 36 = panas tidak wajar
                if (($deltaVolume !== null && $deltaVolume < -5) ||
                    ($request->temperature_c !== null && $request->temperature_c > 36)) {
                    $this->publisher->publish('vandalism.alert', $payload);
                }
            } catch (\Exception $e) {
                error_log('[RabbitMQ Error] ' . $e->getMessage());
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'data'      => [
                    'sensor_log'                => $log,
                    'current_volume_percentage' => $volumePct,
                    'delta_volume'              => $deltaVolume,
                    'bin_status'                => $bin->status,
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
}