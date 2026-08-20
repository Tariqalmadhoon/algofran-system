<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Halaqa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['center_id', 'branch_id', 'primary_teacher_id', 'name', 'code', 'program', 'capacity', 'room', 'active', 'start_date', 'notes'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'start_date' => 'date'];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function primaryTeacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'primary_teacher_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(HalaqaSchedule::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(HalaqaTeacherAssignment::class);
    }

    public function currentStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'current_halaqa_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HalaqaEnrollment::class);
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StudentAlert::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
