<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bin extends Model {
    protected $table    = 'bins';
    protected $fillable = ['zone_id', 'location', 'capacity_kg', 'status'];

    public function zone() {
        return $this->belongsTo(Zone::class);
    }

    public function telemetry() {
        return $this->hasMany(BinTelemetry::class);
    }

    public function latestTelemetry() {
        return $this->hasOne(BinTelemetry::class)->latestOfMany('recorded_at');
    }
}