<?php

namespace App\Models;

use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationItem extends Model
{
    protected $fillable = [
        'daily_record_id', 'type', 'start_ayah_id', 'end_ayah_id', 'evaluation', 'notes',
        'memorization_errors', 'tajweed_errors', 'hesitation_count', 'teacher_prompt_count',
    ];

    protected function casts(): array
    {
        return ['type' => RecitationType::class, 'evaluation' => EvaluationRating::class];
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function startAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'start_ayah_id');
    }

    public function endAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'end_ayah_id');
    }
}
