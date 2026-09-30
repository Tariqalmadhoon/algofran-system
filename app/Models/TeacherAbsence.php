<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAbsence extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'halaqa_id',
        'absence_date',
        'reason',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return ['absence_date' => 'date'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
