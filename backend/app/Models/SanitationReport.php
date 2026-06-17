<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanitationReport extends Model {
    protected $table    = 'sanitation_reports';
    protected $fillable = [
        'reporter_name', 'reporter_phone', 'issue_description',
        'photo_url', 'geo_coordinate', 'verification_status'
    ];

    public function verifyReport(): bool {
        if ($this->verification_status !== 'Pending') return false;
        $this->verification_status = 'Reviewed';
        return $this->save();
    }
}