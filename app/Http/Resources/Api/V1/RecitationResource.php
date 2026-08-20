<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'daily_record_id' => $this->daily_record_id, 'type' => $this->type->value, 'type_label' => $this->type->label(), 'start_ayah_id' => $this->start_ayah_id, 'end_ayah_id' => $this->end_ayah_id, 'evaluation' => $this->evaluation->value, 'evaluation_label' => $this->evaluation->label(), 'memorization_errors' => $this->memorization_errors, 'tajweed_errors' => $this->tajweed_errors, 'hesitation_count' => $this->hesitation_count, 'teacher_prompt_count' => $this->teacher_prompt_count, 'notes' => $this->notes];
    }
}
