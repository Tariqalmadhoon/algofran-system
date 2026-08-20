<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'as_of_date' => $this->as_of_date?->toDateString(), 'memorized_ayahs' => $this->memorized_ayahs, 'memorized_percentage' => $this->memorized_percentage, 'completed_surahs' => $this->completed_surahs, 'completed_juz' => $this->completed_juz, 'memorization_sessions' => $this->memorization_sessions, 'revision_sessions' => $this->revision_sessions, 'last_revision_at' => $this->last_revision_at?->toDateString(), 'evaluation_average' => $this->evaluation_average, 'performance_trend' => $this->performance_trend, 'attendance_rate' => $this->attendance_rate, 'score' => $this->score, 'score_breakdown' => $this->score_breakdown];
    }
}
