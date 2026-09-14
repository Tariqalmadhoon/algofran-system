<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'father_name' => $this->father_name,
            'grandfather_name' => $this->grandfather_name,
            'family_name' => $this->family_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'contact_phone' => $this->contact_phone,
            'registration_date' => $this->registration_date?->toDateString(),
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'halaqa' => $this->currentHalaqa ? [
                'id' => $this->currentHalaqa->id,
                'name' => $this->currentHalaqa->name,
            ] : null,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
