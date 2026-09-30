<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncTeacherAbsencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recitations.create') === true
            && $this->user()?->can('attendance.manage') === true;
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'operations' => ['required', 'array', 'min:1', 'max:50'],
            'operations.*' => ['required', 'array'],
            'operations.*.operation_uuid' => ['required', 'uuid', 'distinct'],
            'operations.*.client_created_at' => ['nullable', 'date'],
            'operations.*.teacher_absence' => ['required', 'array'],
            'operations.*.teacher_absence.halaqa_id' => ['required', 'integer', 'exists:halaqas,id'],
            'operations.*.teacher_absence.absence_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'operations.*.teacher_absence.reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
