<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncStudentOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->teacherProfile()->where('active', true)->exists() === true;
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'operations' => ['required', 'array', 'min:1', 'max:50'],
            'operations.*' => ['required', 'array'],
            'operations.*.operation_uuid' => ['required', 'uuid', 'distinct'],
            'operations.*.client_created_at' => ['nullable', 'date'],
            'operations.*.type' => ['required', Rule::in(['create', 'update', 'archive'])],
            'operations.*.student' => ['required', 'array'],
        ];
    }
}
