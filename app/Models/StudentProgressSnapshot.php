<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgressSnapshot extends Model
{
    protected $fillable = [
        'student_id', 'as_of_date', 'last_memorized_ayah_id', 'memorized_ayahs', 'memorized_percentage',
        'completed_surahs', 'completed_juz', 'memorization_sessions', 'revision_sessions', 'last_revision_at',
        'evaluation_average', 'performance_trend', 'attendance_rate', 'score', 'score_breakdown', 'metrics',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'last_revision_at' => 'date',
            'memorized_percentage' => 'float',
            'evaluation_average' => 'float',
            'performance_trend' => 'float',
            'attendance_rate' => 'float',
            'score' => 'float',
            'score_breakdown' => 'array',
            'metrics' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lastMemorizedAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'last_memorized_ayah_id');
    }
}
