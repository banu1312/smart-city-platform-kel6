<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrashBinSeeder extends Seeder {
    public function run(): void {
        DB::table('trash_bins')->insertOrIgnore([
            ['bin_code'=>'BIN-Z1-01', 'capacity_liters'=>100, 'tinggi'=>100.0, 'tipe_lokasi'=>'Perumahan', 'current_volume_percentage'=>45.0, 'methane_gas_level'=>5.2,  'temperature'=>28.5, 'status'=>'Active',      'latitude'=>-6.1944, 'longitude'=>106.8318],
            ['bin_code'=>'BIN-Z2-01', 'capacity_liters'=>80,  'tinggi'=>80.0,  'tipe_lokasi'=>'Pasar',     'current_volume_percentage'=>67.0, 'methane_gas_level'=>8.1,  'temperature'=>29.0, 'status'=>'Active',      'latitude'=>-6.1950, 'longitude'=>106.8320],
            ['bin_code'=>'BIN-Z3-01', 'capacity_liters'=>120, 'tinggi'=>120.0, 'tipe_lokasi'=>'Taman',     'current_volume_percentage'=>82.0, 'methane_gas_level'=>15.3, 'temperature'=>31.2, 'status'=>'Active',      'latitude'=>-6.2383, 'longitude'=>106.8000],
            ['bin_code'=>'BIN-Z4-01', 'capacity_liters'=>100, 'tinggi'=>100.0, 'tipe_lokasi'=>'Perumahan', 'current_volume_percentage'=>95.0, 'methane_gas_level'=>22.5, 'temperature'=>33.5, 'status'=>'Active',      'latitude'=>-6.2390, 'longitude'=>106.8010],
            ['bin_code'=>'BIN-Z5-01', 'capacity_liters'=>150, 'tinggi'=>150.0, 'tipe_lokasi'=>'Pasar',     'current_volume_percentage'=>23.0, 'methane_gas_level'=>3.1,  'temperature'=>27.8, 'status'=>'Active',      'latitude'=>-6.1200, 'longitude'=>106.8100],
            ['bin_code'=>'BIN-Z6-01', 'capacity_liters'=>100, 'tinggi'=>100.0, 'tipe_lokasi'=>'Taman',     'current_volume_percentage'=>55.0, 'methane_gas_level'=>7.8,  'temperature'=>28.3, 'status'=>'Active',      'latitude'=>-6.1210, 'longitude'=>106.8110],
            ['bin_code'=>'BIN-Z7-01', 'capacity_liters'=>120, 'tinggi'=>120.0, 'tipe_lokasi'=>'Perumahan', 'current_volume_percentage'=>38.0, 'methane_gas_level'=>4.5,  'temperature'=>27.5, 'status'=>'Active',      'latitude'=>-6.1500, 'longitude'=>106.7400],
            ['bin_code'=>'BIN-Z8-01', 'capacity_liters'=>200, 'tinggi'=>150.0, 'tipe_lokasi'=>'Pasar',     'current_volume_percentage'=>72.0, 'methane_gas_level'=>11.2, 'temperature'=>30.0, 'status'=>'Active',      'latitude'=>-6.1510, 'longitude'=>106.7410],
            ['bin_code'=>'BIN-Z9-01', 'capacity_liters'=>100, 'tinggi'=>100.0, 'tipe_lokasi'=>'Taman',     'current_volume_percentage'=>88.0, 'methane_gas_level'=>18.7, 'temperature'=>32.0, 'status'=>'Active',      'latitude'=>-6.2000, 'longitude'=>106.9500],
            ['bin_code'=>'BIN-Z10-01','capacity_liters'=>80,  'tinggi'=>80.0,  'tipe_lokasi'=>'Perumahan', 'current_volume_percentage'=>15.0, 'methane_gas_level'=>2.3,  'temperature'=>26.8, 'status'=>'Vandalized',  'latitude'=>-6.2010, 'longitude'=>106.9510],
        ]);
    }
}
