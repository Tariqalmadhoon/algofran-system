<?php

namespace App\Http\Controllers;

use App\Services\ApplicationReadinessService;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(ApplicationReadinessService $readiness): JsonResponse
    {
        $ready = $readiness->isReady();

        return response()
            ->json(['status' => $ready ? 'ready' : 'not_ready'], $ready ? 200 : 503)
            ->header('Cache-Control', 'no-store, private');
    }
}
