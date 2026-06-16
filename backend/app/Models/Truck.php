<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model {
    protected $table    = 'trucks';
    protected $fillable = [
        'license_plate', 'max_capacity_ton',
        'current_status', 'driver_name'
    ];

    public function schedules() {
        return $this->hasMany(Schedule::class, 'truck_id');
    }
}