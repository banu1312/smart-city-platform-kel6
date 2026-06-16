<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TruckSeeder extends Seeder {
    public function run(): void {
        DB::table('trucks')->insertOrIgnore([
            ['license_plate'=>'B 1234 ABC', 'max_capacity_ton'=>2.0, 'current_status'=>'Available', 'driver_name'=>'Budi Santoso'],
            ['license_plate'=>'B 5678 DEF', 'max_capacity_ton'=>2.0, 'current_status'=>'On-Route',  'driver_name'=>'Ahmad Fauzi'],
            ['license_plate'=>'B 9012 GHI', 'max_capacity_ton'=>1.5, 'current_status'=>'Available', 'driver_name'=>'Riko Pratama'],
            ['license_plate'=>'B 3456 JKL', 'max_capacity_ton'=>2.0, 'current_status'=>'Available', 'driver_name'=>'Hendro Wijaya'],
            ['license_plate'=>'B 7890 MNO', 'max_capacity_ton'=>1.5, 'current_status'=>'Off-Duty',  'driver_name'=>'Doni Kurniawan'],
            ['license_plate'=>'B 2345 PQR', 'max_capacity_ton'=>2.0, 'current_status'=>'Available', 'driver_name'=>'Wahyu Nugroho'],
            ['license_plate'=>'B 6789 STU', 'max_capacity_ton'=>1.5, 'current_status'=>'Available', 'driver_name'=>'Fajar Hidayat'],
            ['license_plate'=>'B 0123 VWX', 'max_capacity_ton'=>2.0, 'current_status'=>'On-Route',  'driver_name'=>'Bayu Saputra'],
            ['license_plate'=>'B 4567 YZA', 'max_capacity_ton'=>1.5, 'current_status'=>'Available', 'driver_name'=>'Irwan Maulana'],
            ['license_plate'=>'B 8901 BCD', 'max_capacity_ton'=>2.0, 'current_status'=>'Off-Duty',  'driver_name'=>'Kevin Pratama'],
        ]);
    }
}