<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DispatchTaskSeeder extends Seeder {
    public function run(): void {
        DB::table('dispatch_tasks')->insertOrIgnore([
            ['truck_id'=>1, 'bin_id'=>4,  'zone_id'=>2, 'priority'=>'urgent',    'status'=>'completed',   'triggered_by'=>'ai',       'notes'=>'Tong 90% penuh'],
            ['truck_id'=>2, 'bin_id'=>9,  'zone_id'=>5, 'priority'=>'emergency', 'status'=>'on_the_way',  'triggered_by'=>'ai',       'notes'=>'Anomali gas terdeteksi'],
            ['truck_id'=>1, 'bin_id'=>3,  'zone_id'=>2, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal rutin pagi'],
            ['truck_id'=>3, 'bin_id'=>6,  'zone_id'=>3, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal rutin pagi'],
            ['truck_id'=>4, 'bin_id'=>8,  'zone_id'=>4, 'priority'=>'urgent',    'status'=>'completed',   'triggered_by'=>'manual',   'notes'=>'Request manual operator'],
            ['truck_id'=>2, 'bin_id'=>2,  'zone_id'=>1, 'priority'=>'normal',    'status'=>'completed',   'triggered_by'=>'schedule', 'notes'=>'Jadwal rutin sore'],
            ['truck_id'=>1, 'bin_id'=>5,  'zone_id'=>3, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal besok'],
            ['truck_id'=>3, 'bin_id'=>10, 'zone_id'=>5, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal besok'],
            ['truck_id'=>4, 'bin_id'=>7,  'zone_id'=>4, 'priority'=>'urgent',    'status'=>'on_the_way',  'triggered_by'=>'ai',       'notes'=>'Fill level 91%'],
            ['truck_id'=>1, 'bin_id'=>1,  'zone_id'=>1, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal rutin'],
            ['truck_id'=>5, 'bin_id'=>2,  'zone_id'=>1, 'priority'=>'normal',    'status'=>'completed',   'triggered_by'=>'schedule', 'notes'=>'Selesai'],
            ['truck_id'=>6, 'bin_id'=>3,  'zone_id'=>2, 'priority'=>'urgent',    'status'=>'completed',   'triggered_by'=>'ai',       'notes'=>'AI trigger'],
            ['truck_id'=>7, 'bin_id'=>4,  'zone_id'=>2, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Jadwal sore'],
            ['truck_id'=>8, 'bin_id'=>5,  'zone_id'=>3, 'priority'=>'emergency', 'status'=>'completed',   'triggered_by'=>'ai',       'notes'=>'Kebakaran kecil'],
            ['truck_id'=>9, 'bin_id'=>6,  'zone_id'=>3, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Rutin'],
            ['truck_id'=>10,'bin_id'=>7,  'zone_id'=>4, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Rutin'],
            ['truck_id'=>1, 'bin_id'=>8,  'zone_id'=>4, 'priority'=>'urgent',    'status'=>'on_the_way',  'triggered_by'=>'ai',       'notes'=>'Fill 88%'],
            ['truck_id'=>2, 'bin_id'=>9,  'zone_id'=>5, 'priority'=>'normal',    'status'=>'assigned',    'triggered_by'=>'schedule', 'notes'=>'Rutin pagi'],
            ['truck_id'=>3, 'bin_id'=>10, 'zone_id'=>5, 'priority'=>'urgent',    'status'=>'assigned',    'triggered_by'=>'ai',       'notes'=>'Fill 85%'],
            ['truck_id'=>4, 'bin_id'=>1,  'zone_id'=>1, 'priority'=>'normal',    'status'=>'completed',   'triggered_by'=>'manual',   'notes'=>'Manual dispatch'],
        ]);
    }
}