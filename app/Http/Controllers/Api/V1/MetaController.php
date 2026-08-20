<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'api_version' => (string) config('system.api.version', '1'),
                'status' => (string) config('system.api.status', 'stable'),
                'minimum_supported_client' => (string) config('system.api.minimum_supported_client', '1.0.0'),
                'authentication' => 'Bearer',
                'locale' => 'ar',
                'direction' => 'rtl',
                'two_factor_challenge' => true,
            ],
        ]);
    }
}
