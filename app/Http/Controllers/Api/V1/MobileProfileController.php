<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\MobileProfileDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function teacher(Request $request, MobileProfileDataService $profiles): JsonResponse
    {
        return response()->json([
            'data' => $profiles->teacher($request->user()),
        ]);
    }

    public function student(
        Request $request,
        Student $student,
        MobileProfileDataService $profiles,
    ): JsonResponse {
        return response()->json([
            'data' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'updated_at' => $student->updated_at?->toISOString(),
                'profile' => $profiles->student($request->user(), $student),
            ],
        ]);
    }
}
