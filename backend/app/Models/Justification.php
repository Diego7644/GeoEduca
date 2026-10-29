<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Justification extends Model
{
    use HasFactory;

    public const TYPES = ['medical', 'early_exit', 'tardy_excuse', 'absence'];

    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'student_id',
        'guardian_user_id',
        'type',
        'event_date',
        'event_time',
        'reason',
        'attachment_name',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }
}
