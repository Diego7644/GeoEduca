<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_ON_TIME = 'on_time';
    public const STATUS_LATE = 'late';
    public const STATUS_ABSENT = 'absent';

    protected $fillable = [
        'student_id',
        'recorded_by_user_id',
        'attended_on',
        'status',
        'source',
        'method',
        'gate',
        'entry_time',
        'exit_time',
        'late_minutes',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'attended_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
