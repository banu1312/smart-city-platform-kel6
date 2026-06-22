<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrashBin extends Model {
    protected $table    = 'trash_bins';
    protected $fillable = [
        'bin_code', 'capacity_liters', 'tinggi', 'current_volume_percentage',
        'methane_gas_level', 'temperature', 'tipe_lokasi', 'status',
        'latitude', 'longitude', 'last_pickup'
    ];

    protected $casts = [
        'last_pickup'               => 'datetime',
        'current_volume_percentage' => 'float',
        'methane_gas_level'         => 'float',
        'temperature'               => 'float',
        'latitude'                  => 'double',
        'longitude'                 => 'double',
        'tinggi'                    => 'float',
    ];

    public function sensorLogs() {
        return $this->hasMany(SensorLog::class, 'trash_bin_id');
    }

    public function latestLog() {
        return $this->hasOne(SensorLog::class, 'trash_bin_id')
                    ->latestOfMany('recorded_at');
    }

    public function schedules() {
        return $this->hasMany(Schedule::class, 'trash_bin_id');
    }

    public function getFillRateEstimation(): array {
        $logs = $this->sensorLogs()
                     ->orderBy('recorded_at', 'desc')
                     ->limit(10)
                     ->get();

        if ($logs->count() < 2) {
            return ['hours_until_full' => null, 'rate_per_hour' => null];
        }

        // hitung rata-rata delta volume per jam
        $totalDelta   = 0;
        $totalHours   = 0;
        $previousLog  = null;

        foreach ($logs as $log) {
            if ($previousLog) {
                $hoursDiff   = $previousLog->recorded_at->diffInMinutes($log->recorded_at) / 60;
                $volumeDiff  = abs($log->delta_volume ?? 0);
                $totalDelta += $volumeDiff;
                $totalHours += $hoursDiff;
            }
            $previousLog = $log;
        }

        $ratePerHour   = $totalHours > 0 ? $totalDelta / $totalHours : 0;
        $remaining     = 100 - $this->current_volume_percentage;
        $hoursUntilFull = $ratePerHour > 0 ? $remaining / $ratePerHour : null;

        return [
            'hours_until_full' => $hoursUntilFull ? round($hoursUntilFull, 2) : null,
            'rate_per_hour'    => round($ratePerHour, 4),
        ];
    }
}