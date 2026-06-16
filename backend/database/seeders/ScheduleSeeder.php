<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder {
    public function run(): void {
        DB::table('schedules')->insertOrIgnore([
            ['trash_bin_id'=>3, 'truck_id'=>1, 'scheduled_at'=>now()->addHour(),    'priority_level'=>'Urgent',   'execution_status'=>'Pending',     'estimated_hours_full'=>1.5],
            ['trash_bin_id'=>4, 'truck_id'=>2, 'scheduled_at'=>now()->addMinutes(30),'priority_level'=>'Critical', 'execution_status'=>'In-Progress', 'estimated_hours_full'=>0.5],
            ['trash_bin_id'=>9, 'truck_id'=>3, 'scheduled_at'=>now()->addHours(2),  'priority_level'=>'Urgent',   'execution_status'=>'Pending',     'estimated_hours_full'=>2.0],
            ['trash_bin_id'=>8, 'truck_id'=>4, 'scheduled_at'=>now()->addHours(4),  'priority_level'=>'Medium',   'execution_status'=>'Pending',     'estimated_hours_full'=>4.0],
            ['trash_bin_id'=>2, 'truck_id'=>1, 'scheduled_at'=>now()->subHours(2),  'priority_level'=>'Low',      'execution_status'=>'Completed',   'estimated_hours_full'=>8.0],
            ['trash_bin_id'=>5, 'truck_id'=>6, 'scheduled_at'=>now()->addHours(5),  'priority_level'=>'Medium',   'execution_status'=>'Pending',     'estimated_hours_full'=>5.0],
            ['trash_bin_id'=>6, 'truck_id'=>7, 'scheduled_at'=>now()->addHours(6),  'priority_level'=>'Low',      'execution_status'=>'Pending',     'estimated_hours_full'=>6.0],
            ['trash_bin_id'=>7, 'truck_id'=>8, 'scheduled_at'=>now()->addHours(2),  'priority_level'=>'Urgent',   'execution_status'=>'In-Progress', 'estimated_hours_full'=>2.5],
            ['trash_bin_id'=>1, 'truck_id'=>9, 'scheduled_at'=>now()->addHours(10), 'priority_level'=>'Low',      'execution_status'=>'Pending',     'estimated_hours_full'=>10.0],
            ['trash_bin_id'=>10,'truck_id'=>3, 'scheduled_at'=>now()->addHours(3),  'priority_level'=>'Medium',   'execution_status'=>'Pending',     'estimated_hours_full'=>3.0],
        ]);
    }
}