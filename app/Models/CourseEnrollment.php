<?php

namespace App\Models;

use App\Enums\CourseEnrollmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    protected $fillable = ['course_id', 'student_id', 'enrolled_at', 'status', 'result', 'grade', 'completed_at', 'notes', 'enrolled_by'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'completed_at' => 'date',
            'result' => 'float',
            'status' => CourseEnrollmentStatus::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
