<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DailyRecordResource;
use App\Http\Resources\Api\V1\StudentProgressResource;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Student;
use App\Services\MobileDataService;
use App\Services\StudentVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'halaqa_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = $mobile->studentQuery($request->user())->with(['currentHalaqa:id,name', 'latestProgress'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($scope) => $scope->where('full_name', 'like', "%{$search}%")->orWhere('student_number', 'like', "%{$search}%")))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $id) => $query->where('current_halaqa_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        return StudentResource::collection($query->orderBy('full_name')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }

    public function show(Request $request, Student $student, StudentVisibilityService $visibility): StudentResource
    {
        abort_unless($visibility->canView($request->user(), $student), 403);

        return new StudentResource($student->load(['currentHalaqa:id,name', 'latestProgress']));
    }

    public function progress(Request $request, Student $student, StudentVisibilityService $visibility): AnonymousResourceCollection
    {
        $filters = $request->validate(['per_page' => ['nullable', 'integer', 'between:1,100']]);
        abort_unless($visibility->canView($request->user(), $student), 403);

        return StudentProgressResource::collection($student->progressSnapshots()->latest('as_of_date')->paginate((int) ($filters['per_page'] ?? 30)));
    }

    public function dailyRecords(Request $request, Student $student, StudentVisibilityService $visibility): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        abort_unless($visibility->canView($request->user(), $student), 403);
        $query = $student->dailyRecords()->with(['student:id,student_number,full_name', 'halaqa:id,name', 'teacher.user:id,name', 'attendance', 'recitationItems'])
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('record_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('record_date', '<=', $date));

        return DailyRecordResource::collection($query->latest('record_date')->paginate((int) ($filters['per_page'] ?? 20)));
    }
}
