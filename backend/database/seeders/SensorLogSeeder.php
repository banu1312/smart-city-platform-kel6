<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorLogSeeder extends Seeder {
    public function run(): void {
        $records = [];

        for ($binId = 1; $binId <= 10; $binId++) {
            for ($i = 0; $i < 20; $i++) {
                $distanceCm  = round(rand(5, 90) + rand(0,9)/10, 1);
                $methanePpm  = round(rand(0, 30) + rand(0,9)/10, 1);
                $tempC       = round(rand(25, 38) + rand(0,9)/10, 1);
                $deltaVolume = round(rand(-2, 5) + rand(0,9)/10, 2);
                $recordedAt  = now()->subMinutes($i * 30)->format('Y-m-d H:i:s');

                $records[] = [
                    'trash_bin_id' => $binId,
                    'distance_cm'  => $distanceCm,
                    'methane_ppm'  => $methanePpm,
                    'temperature_c'=> $tempC,
                    'delta_volume' => $deltaVolume,
                    'raw_payload'  => json_encode([
                        'bin_id'        => $binId,
                        'distance_cm'   => $distanceCm,
                        'methane_ppm'   => $methanePpm,
                        'temperature_c' => $tempC,
                        'source'        => 'wokwi-esp32',
                        'timestamp'     => $recordedAt,
                    ]),
                    'recorded_at'  => $recordedAt,
                ];
            }
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('sensor_logs')->insert($chunk);
        }
    }
}