<?php

namespace App\Http\Controllers;

use App\Actions\Mobile\StageAndroidReleaseUploadAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReleaseChunkUploadController extends Controller
{
    public function __invoke(Request $request, StageAndroidReleaseUploadAction $uploads): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1', 'max:128'],
            'total_size' => ['required', 'integer', 'min:1', 'max:'.StageAndroidReleaseUploadAction::MAX_BYTES],
            'chunk' => ['required', 'file', 'max:'.(int) ceil(StageAndroidReleaseUploadAction::CHUNK_BYTES / 1024)],
        ]);

        $result = $uploads->stage($request->user(), $data, $request->file('chunk'));

        return response()->json($result);
    }
}
