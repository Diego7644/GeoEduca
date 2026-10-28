<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school',
        'campus',
        'address',
        'year',
        'entry_limit',
        'exit_time',
        'geofence_m',
        'promotion_min',
        'tolerance_min',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'geofence_m' => 'integer',
            'promotion_min' => 'integer',
            'tolerance_min' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }
}
