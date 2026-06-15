<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BinTelemetry extends Model {
    protected $table      = 'bin_telemetry';
    protected $fillable   = [
        'bin_id', 'zone_id', 'fill_level', 'gas_level',
        'temperature', 'distance_cm', 'is_anomaly', 'fill_rate_est'
    ];
    public $timestamps    = false;

    protected $casts = [
        'is_anomaly' => 'boolean',
        'recorded_at'=> 'datetime',
    ];

    public function bin() {
        return $this->belongsTo(Bin::class);
    }
}