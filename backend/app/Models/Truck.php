<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model {
    protected $table    = 'trucks';
    protected $fillable = ['plate_number', 'driver_name', 'capacity_kg', 'status', 'zone_id'];

    public function zone() {
        return $this->belongsTo(Zone::class);
    }

    public function tasks() {
        return $this->hasMany(DispatchTask::class);
    }
}