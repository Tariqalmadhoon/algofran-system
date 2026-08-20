<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_number' => $this->student->student_number, 'full_name' => $this->student->full_name]), 'type' => $this->type, 'severity' => $this->severity->value, 'severity_label' => $this->severity->label(), 'reason' => $this->reason, 'status' => $this->status->value, 'status_label' => $this->status->label(), 'evidence' => $this->evidence, 'generated_at' => $this->generated_at?->toIso8601String()];
    }
}
