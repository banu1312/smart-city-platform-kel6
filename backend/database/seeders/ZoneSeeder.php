<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder {
    public function run(): void {
        DB::table('zones')->insertOrIgnore([
            ['name'=>'Zone Menteng',     'city_district'=>'Jakarta Pusat',   'coordinates'=>'-6.1944,106.8318', 'area_km2'=>6.5],
            ['name'=>'Zone Kebayoran',   'city_district'=>'Jakarta Selatan', 'coordinates'=>'-6.2383,106.8000', 'area_km2'=>8.2],
            ['name'=>'Zone Penjaringan', 'city_district'=>'Jakarta Utara',   'coordinates'=>'-6.1200,106.8100', 'area_km2'=>10.1],
            ['name'=>'Zone Cengkareng',  'city_district'=>'Jakarta Barat',   'coordinates'=>'-6.1500,106.7400', 'area_km2'=>9.3],
            ['name'=>'Zone Cakung',      'city_district'=>'Jakarta Timur',   'coordinates'=>'-6.2000,106.9500', 'area_km2'=>11.0],
        ]);
    }
}