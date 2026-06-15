<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BinSeeder extends Seeder {
    public function run(): void {
        DB::table('bins')->insertOrIgnore([
            ['zone_id'=>1, 'location'=>'Depan Pasar Menteng',       'capacity_kg'=>100.0, 'status'=>'active'],
            ['zone_id'=>1, 'location'=>'Taman Suropati',            'capacity_kg'=>80.0,  'status'=>'active'],
            ['zone_id'=>2, 'location'=>'Blok M Square',             'capacity_kg'=>120.0, 'status'=>'active'],
            ['zone_id'=>2, 'location'=>'Kebayoran Lama Terminal',   'capacity_kg'=>100.0, 'status'=>'full'],
            ['zone_id'=>3, 'location'=>'Pelabuhan Sunda Kelapa',    'capacity_kg'=>150.0, 'status'=>'active'],
            ['zone_id'=>3, 'location'=>'Pasar Ikan Penjaringan',    'capacity_kg'=>100.0, 'status'=>'active'],
            ['zone_id'=>4, 'location'=>'Cengkareng Business City',  'capacity_kg'=>120.0, 'status'=>'active'],
            ['zone_id'=>4, 'location'=>'Bandara Soekarno-Hatta',    'capacity_kg'=>200.0, 'status'=>'active'],
            ['zone_id'=>5, 'location'=>'Pulogadung Trade Center',   'capacity_kg'=>100.0, 'status'=>'active'],
            ['zone_id'=>5, 'location'=>'TMII Cakung',               'capacity_kg'=>80.0,  'status'=>'active'],
        ]);
    }
}