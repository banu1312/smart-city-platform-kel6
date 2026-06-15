<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenReport extends Model {
    protected $table    = 'citizen_reports';
    protected $fillable = [
        'zone_id', 'title', 'description',
        'photo_url', 'latitude', 'longitude', 'status'
    ];

    public function zone() {
        return $this->belongsTo(Zone::class);
    }
}