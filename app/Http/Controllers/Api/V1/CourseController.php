<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Services\MobileDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(CourseStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = $mobile->courseQuery($request->user())->with(['branch:id,name', 'instructor.user:id,name'])->withCount('enrollments')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"));

        return CourseResource::collection($query->latest('starts_at')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }
}
