<?php

namespace App\Models;

use App\Enums\EvaluationRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyRecord extends Model
{
    protected $fillable = [
        'student_id', 'teacher_profile_id', 'halaqa_id', 'attendance_id', 'record_date',
        'general_evaluation', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['record_date' => 'date', 'general_evaluation' => EvaluationRating::class];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function recitationItems(): HasMany
    {
        return $this->hasMany(RecitationItem::class);
    }
}
