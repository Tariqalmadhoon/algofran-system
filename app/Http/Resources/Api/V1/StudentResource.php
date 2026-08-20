<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'student_number' => $this->student_number, 'full_name' => $this->full_name, 'birth_date' => $this->birth_date?->toDateString(), 'registration_date' => $this->registration_date?->toDateString(), 'status' => $this->status->value, 'status_label' => $this->status->label(), 'halaqa' => $this->whenLoaded('currentHalaqa', fn () => $this->currentHalaqa ? ['id' => $this->currentHalaqa->id, 'name' => $this->currentHalaqa->name] : null), 'latest_progress' => StudentProgressResource::make($this->whenLoaded('latestProgress'))];
    }
}
