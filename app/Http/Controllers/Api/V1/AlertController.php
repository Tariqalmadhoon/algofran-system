<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AlertResource;
use App\Models\StudentAlert;
use App\Services\MobileDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function index(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AlertStatus::class)],
            'severity' => ['nullable', Rule::enum(AlertSeverity::class)],
            'student_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = StudentAlert::query()->with('student:id,student_number,full_name')->whereIn('student_id', $mobile->studentIds($request->user()))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('student_id', $id));

        return AlertResource::collection($query->latest('generated_at')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }
}
