<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HalaqaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'program' => $this->program, 'capacity' => $this->capacity, 'room' => $this->room, 'active' => $this->active, 'branch' => $this->whenLoaded('branch', fn () => ['id' => $this->branch->id, 'name' => $this->branch->name]), 'teacher' => $this->whenLoaded('primaryTeacher', fn () => $this->primaryTeacher?->user ? ['id' => $this->primaryTeacher->id, 'name' => $this->primaryTeacher->user->name] : null), 'students_count' => $this->whenCounted('currentStudents'), 'schedules' => $this->whenLoaded('schedules', fn () => $this->schedules->map(fn ($schedule) => ['weekday' => $schedule->weekday, 'starts_at' => $schedule->starts_at, 'ends_at' => $schedule->ends_at, 'room' => $schedule->room]))];
    }
}
