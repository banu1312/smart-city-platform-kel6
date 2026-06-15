<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TruckSeeder extends Seeder {
    public function run(): void {
        DB::table('trucks')->insertOrIgnore([
            ['plate_number'=>'B 1234 ABC', 'driver_name'=>'Budi Santoso',   'capacity_kg'=>2000.0, 'status'=>'available',   'zone_id'=>1],
            ['plate_number'=>'B 5678 DEF', 'driver_name'=>'Ahmad Fauzi',    'capacity_kg'=>2000.0, 'status'=>'on_duty',     'zone_id'=>2],
            ['plate_number'=>'B 9012 GHI', 'driver_name'=>'Riko Pratama',   'capacity_kg'=>1500.0, 'status'=>'available',   'zone_id'=>3],
            ['plate_number'=>'B 3456 JKL', 'driver_name'=>'Hendro Wijaya',  'capacity_kg'=>2000.0, 'status'=>'available',   'zone_id'=>4],
            ['plate_number'=>'B 7890 MNO', 'driver_name'=>'Doni Kurniawan', 'capacity_kg'=>1500.0, 'status'=>'maintenance', 'zone_id'=>5],
            ['plate_number'=>'B 2345 PQR', 'driver_name'=>'Wahyu Nugroho',  'capacity_kg'=>2000.0, 'status'=>'available',   'zone_id'=>1],
            ['plate_number'=>'B 6789 STU', 'driver_name'=>'Fajar Hidayat',  'capacity_kg'=>1500.0, 'status'=>'available',   'zone_id'=>2],
            ['plate_number'=>'B 0123 VWX', 'driver_name'=>'Bayu Saputra',   'capacity_kg'=>2000.0, 'status'=>'on_duty',     'zone_id'=>3],
            ['plate_number'=>'B 4567 YZA', 'driver_name'=>'Irwan Maulana',  'capacity_kg'=>1500.0, 'status'=>'available',   'zone_id'=>4],
            ['plate_number'=>'B 8901 BCD', 'driver_name'=>'Kevin Pratama',  'capacity_kg'=>2000.0, 'status'=>'available',   'zone_id'=>5],
        ]);
    }
}