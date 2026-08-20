<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'record_date' => $this->record_date?->toDateString(), 'student_id' => $this->student_id, 'halaqa_id' => $this->halaqa_id, 'status' => $this->status->value, 'status_label' => $this->status->label(), 'notes' => $this->notes];
    }
}
