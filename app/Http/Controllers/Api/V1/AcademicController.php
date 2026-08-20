<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Enums\RecitationType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Http\Resources\Api\V1\CertificateResource;
use App\Http\Resources\Api\V1\DailyRecordResource;
use App\Http\Resources\Api\V1\RecitationResource;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\DailyRecord;
use App\Models\RecitationItem;
use App\Services\MobileDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AcademicController extends Controller
{
    public function dailyRecords(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $this->validatedFilters($request);
        $ids = $mobile->studentIds($request->user());
        $query = DailyRecord::query()->with(['student:id,student_number,full_name', 'halaqa:id,name', 'teacher.user:id,name', 'attendance', 'recitationItems'])->whereIn('student_id', $ids);
        $this->filters($query, $filters, 'record_date');

        return DailyRecordResource::collection($query->latest('record_date')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }

    public function recitations(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(RecitationType::class)],
            'student_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $ids = $mobile->studentIds($request->user());
        $query = RecitationItem::query()->whereHas('dailyRecord', fn ($records) => $records->whereIn('student_id', $ids))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->whereHas('dailyRecord', fn ($records) => $records->where('student_id', $id)));

        return RecitationResource::collection($query->latest()->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }

    public function attendance(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $this->validatedFilters($request, ['status' => ['nullable', Rule::enum(AttendanceStatus::class)]]);
        $query = Attendance::query()->whereIn('student_id', $mobile->studentIds($request->user()))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $this->filters($query, $filters, 'record_date');

        return AttendanceResource::collection($query->latest('record_date')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }

    public function certificates(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate(['student_id' => ['nullable', 'integer'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $query = Certificate::query()->with('course:id,name')->whereIn('student_id', $mobile->studentIds($request->user()))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('student_id', $id));

        return CertificateResource::collection($query->latest('issued_at')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }

    private function validatedFilters(Request $request, array $extra = []): array
    {
        return $request->validate($extra + [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'student_id' => ['nullable', 'integer'],
            'halaqa_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }

    private function filters($query, array $filters, string $column): void
    {
        $query->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate($column, '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate($column, '<=', $date))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('student_id', $id))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $id) => $query->where('halaqa_id', $id));
    }
}
