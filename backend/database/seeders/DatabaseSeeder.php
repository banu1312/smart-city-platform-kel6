<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            TrashBinSeeder::class,
            SensorLogSeeder::class,
            TruckSeeder::class,
            ScheduleSeeder::class,
            SanitationReportSeeder::class,
        ]);
    }
}