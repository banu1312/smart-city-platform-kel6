<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model {
    protected $table    = 'zones';
    protected $fillable = ['name', 'city_district', 'coordinates', 'area_km2'];
}