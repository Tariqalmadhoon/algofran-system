<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalaqaTeacherAssignment extends Model
{
    protected $fillable = ['halaqa_id', 'teacher_profile_id', 'role', 'starts_at', 'ends_at', 'assigned_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
