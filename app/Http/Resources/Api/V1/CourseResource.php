<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description, 'starts_at' => $this->starts_at?->toDateString(), 'ends_at' => $this->ends_at?->toDateString(), 'hours' => $this->hours, 'status' => $this->status->value, 'status_label' => $this->status->label(), 'branch' => $this->whenLoaded('branch', fn () => $this->branch ? ['id' => $this->branch->id, 'name' => $this->branch->name] : null), 'instructor' => $this->whenLoaded('instructor', fn () => $this->instructor?->user ? ['id' => $this->instructor->id, 'name' => $this->instructor->user->name] : null), 'enrollments_count' => $this->whenCounted('enrollments')];
    }
}
