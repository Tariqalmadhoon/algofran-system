<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAlert extends Model
{
    protected $fillable = [
        'student_id', 'teacher_profile_id', 'halaqa_id', 'fingerprint', 'type', 'severity', 'reason',
        'status', 'evidence', 'occurrence_count', 'generated_at', 'acknowledged_at', 'acknowledged_by',
        'resolved_at', 'resolved_by', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'evidence' => 'array',
            'generated_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
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
}
