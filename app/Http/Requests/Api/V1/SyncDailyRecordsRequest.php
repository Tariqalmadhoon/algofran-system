<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncDailyRecordsRequest extends FormRequest
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
            'operations.*.daily_record' => ['required', 'array'],
        ];
    }
}
