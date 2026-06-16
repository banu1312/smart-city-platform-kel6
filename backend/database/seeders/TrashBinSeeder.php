<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrashBinSeeder extends Seeder {
    public function run(): void {
        DB::table('trash_bins')->insertOrIgnore([
            ['bin_code'=>'BIN-MNT-001', 'capacity_liters'=>100, 'current_volume_percentage'=>45.0, 'methane_gas_level'=>5.2,  'status'=>'Active',      'location_coordinate'=>'-6.1944,106.8318'],
            ['bin_code'=>'BIN-MNT-002', 'capacity_liters'=>80,  'current_volume_percentage'=>67.0, 'methane_gas_level'=>8.1,  'status'=>'Active',      'location_coordinate'=>'-6.1950,106.8320'],
            ['bin_code'=>'BIN-KBY-001', 'capacity_liters'=>120, 'current_volume_percentage'=>82.0, 'methane_gas_level'=>15.3, 'status'=>'Active',      'location_coordinate'=>'-6.2383,106.8000'],
            ['bin_code'=>'BIN-KBY-002', 'capacity_liters'=>100, 'current_volume_percentage'=>95.0, 'methane_gas_level'=>22.5, 'status'=>'Maintenance', 'location_coordinate'=>'-6.2390,106.8010'],
            ['bin_code'=>'BIN-PNJ-001', 'capacity_liters'=>150, 'current_volume_percentage'=>23.0, 'methane_gas_level'=>3.1,  'status'=>'Active',      'location_coordinate'=>'-6.1200,106.8100'],
            ['bin_code'=>'BIN-PNJ-002', 'capacity_liters'=>100, 'current_volume_percentage'=>55.0, 'methane_gas_level'=>7.8,  'status'=>'Active',      'location_coordinate'=>'-6.1210,106.8110'],
            ['bin_code'=>'BIN-CNG-001', 'capacity_liters'=>120, 'current_volume_percentage'=>38.0, 'methane_gas_level'=>4.5,  'status'=>'Active',      'location_coordinate'=>'-6.1500,106.7400'],
            ['bin_code'=>'BIN-CNG-002', 'capacity_liters'=>200, 'current_volume_percentage'=>72.0, 'methane_gas_level'=>11.2, 'status'=>'Active',      'location_coordinate'=>'-6.1510,106.7410'],
            ['bin_code'=>'BIN-CKG-001', 'capacity_liters'=>100, 'current_volume_percentage'=>88.0, 'methane_gas_level'=>18.7, 'status'=>'Active',      'location_coordinate'=>'-6.2000,106.9500'],
            ['bin_code'=>'BIN-CKG-002', 'capacity_liters'=>80,  'current_volume_percentage'=>15.0, 'methane_gas_level'=>2.3,  'status'=>'Vandalized',  'location_coordinate'=>'-6.2010,106.9510'],
        ]);
    }
}