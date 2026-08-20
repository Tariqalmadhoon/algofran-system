<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HalaqaResource;
use App\Services\MobileDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HalaqaController extends Controller
{
    public function index(Request $request, MobileDataService $mobile): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'active' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = $mobile->halaqaQuery($request->user())->with(['branch:id,name', 'primaryTeacher.user:id,name', 'schedules'])->withCount('currentStudents')
            ->when(filter_var($filters['active'] ?? true, FILTER_VALIDATE_BOOL), fn ($query) => $query->where('active', true))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($scope) => $scope->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")));

        return HalaqaResource::collection($query->orderBy('name')->paginate($mobile->perPage((int) ($filters['per_page'] ?? 15))));
    }
}
