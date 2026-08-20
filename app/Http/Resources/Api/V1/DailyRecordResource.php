<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'record_date' => $this->record_date?->toDateString(), 'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_number' => $this->student->student_number, 'full_name' => $this->student->full_name]), 'halaqa' => $this->whenLoaded('halaqa', fn () => ['id' => $this->halaqa->id, 'name' => $this->halaqa->name]), 'teacher' => $this->whenLoaded('teacher', fn () => ['id' => $this->teacher->id, 'name' => $this->teacher->user?->name]), 'general_evaluation' => $this->general_evaluation?->value, 'general_evaluation_label' => $this->general_evaluation?->label(), 'notes' => $this->notes, 'attendance' => AttendanceResource::make($this->whenLoaded('attendance')), 'recitations' => RecitationResource::collection($this->whenLoaded('recitationItems'))];
    }
}
