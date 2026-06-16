<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BinTelemetrySeeder extends Seeder {
    public function run(): void {
        $records = [];
        $bins = [
            1=>1, 2=>1, 3=>2, 4=>2, 5=>3,
            6=>3, 7=>4, 8=>4, 9=>5, 10=>5,
        ];

        $dates = [
            '2026-06-01', '2026-06-02', '2026-06-03',
            '2026-06-04', '2026-06-05',
        ];
        $times = [
            '06:00:00', '08:00:00', '10:00:00', '12:00:00'
        ];

        foreach ($dates as $date) {
            foreach ($times as $time) {
                foreach ($bins as $binId => $zoneId) {
                    $fill    = round(rand(10, 98) + rand(0, 9) / 10, 1);
                    $gas     = round(rand(75, 250) + rand(0, 9) / 10, 1);
                    $temp    = round(rand(26, 38) + rand(0, 9) / 10, 1);
                    $dist    = round(100 - $fill, 1);
                    $anomaly = ($fill > 90 || $gas > 200) ? 1 : 0;

                    $records[] = [
                        'bin_id'      => $binId,
                        'zone_id'     => $zoneId,
                        'fill_level'  => $fill,
                        'gas_level'   => $gas,
                        'temperature' => $temp,
                        'distance_cm' => $dist,
                        'is_anomaly'  => $anomaly,
                        'recorded_at' => "$date $time",
                    ];

                    // Stop tepat di 200
                    if (count($records) >= 200) break 3;
                }
            }
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('bin_telemetry')->insert($chunk);
        }
    }
}