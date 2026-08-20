<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentTimelineEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class StudentTimelineService
{
    public function record(
        Student $student,
        string $eventType,
        string $title,
        ?Model $source = null,
        ?string $description = null,
        array $metadata = [],
        CarbonInterface|string|null $occurredAt = null,
    ): StudentTimelineEvent {
        return $student->timelineEvents()->create([
            'event_type' => $eventType,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
