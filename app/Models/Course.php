<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = ['center_id', 'branch_id', 'instructor_id', 'name', 'description', 'starts_at', 'ends_at', 'hours', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'hours' => 'float', 'status' => CourseStatus::class];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
