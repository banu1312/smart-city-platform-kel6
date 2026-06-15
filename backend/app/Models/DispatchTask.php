<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchTask extends Model {
    protected $table    = 'dispatch_tasks';
    protected $fillable = [
        'truck_id', 'bin_id', 'zone_id',
        'priority', 'status', 'triggered_by', 'notes', 'completed_at'
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function truck() {
        return $this->belongsTo(Truck::class);
    }

    public function bin() {
        return $this->belongsTo(Bin::class);
    }
}