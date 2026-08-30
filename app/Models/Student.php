<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'student_number', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'full_name',
        'identity_number', 'birth_date', 'contact_phone', 'sponsorship_type', 'sponsorship_organization',
        'registration_date', 'status', 'current_halaqa_id',
        'photo_private_file_id', 'identity_private_file_id', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'registration_date' => 'date',
            'status' => StudentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentHalaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class, 'current_halaqa_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class, 'photo_private_file_id');
    }

    public function identityDocument(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class, 'identity_private_file_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot(['relationship', 'is_primary', 'can_receive_notifications'])->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HalaqaEnrollment::class);
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(StudentMemorizationBaseline::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(StudentTimelineEvent::class);
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(StudentProgressSnapshot::class);
    }

    public function latestProgress(): HasOne
    {
        return $this->hasOne(StudentProgressSnapshot::class)->latestOfMany('as_of_date');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StudentAlert::class);
    }
}
