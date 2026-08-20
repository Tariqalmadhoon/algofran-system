<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'student_id' => $this->student_id, 'course' => $this->whenLoaded('course', fn () => $this->course ? ['id' => $this->course->id, 'name' => $this->course->name] : null), 'name' => $this->name, 'issuer' => $this->issuer, 'certificate_number' => $this->certificate_number, 'issued_at' => $this->issued_at?->toDateString(), 'expires_at' => $this->expires_at?->toDateString(), 'grade' => $this->grade, 'has_file' => (bool) $this->private_file_id];
    }
}
