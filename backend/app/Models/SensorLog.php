<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model {
    protected $table    = 'sensor_logs';
    public $timestamps  = false;
    protected $fillable = [
        'trash_bin_id', 'distance_cm', 'methane_ppm',
        'temperature_c', 'delta_volume', 'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function trashBin() {
        return $this->belongsTo(TrashBin::class, 'trash_bin_id');
    }
}