<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model {
    protected $table    = 'schedules';
    protected $fillable = [
        'trash_bin_id', 'truck_id', 'scheduled_at',
        'priority_level', 'execution_status', 'estimated_hours_full'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function truck() {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function trashBin() {
        return $this->belongsTo(TrashBin::class, 'trash_bin_id');
    }

    public function updateStatus(string $newStatus): bool {
        $allowed = ['Pending', 'In-Progress', 'Completed'];
        if (!in_array($newStatus, $allowed)) return false;

        $this->execution_status = $newStatus;

        if ($newStatus === 'Completed') {
            $this->trashBin?->update([
                'last_pickup' => now(),
                'current_volume_percentage' => 0,
                'methane_gas_level' => 0,
                'temperature' => null,
            ]);
        }

        return $this->save();
    }
}