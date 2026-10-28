<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use HasFactory;

    public const KIND_AUXILIAR = 'auxiliar';
    public const KIND_DOCENTE = 'docente';

    protected $fillable = [
        'user_id',
        'name',
        'kind',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeroomStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'homeroom_staff_id');
    }
}
