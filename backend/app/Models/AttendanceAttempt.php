<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAttempt extends Model
{
    public const KIND_QR = 'qr';
    public const KIND_GEO = 'geo';
    public const KIND_MANUAL = 'manual';

    protected $fillable = [
        'student_id',
        'actor_user_id',
        'kind',
        'result',
        'latitude',
        'longitude',
        'accuracy_m',
        'detail',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
