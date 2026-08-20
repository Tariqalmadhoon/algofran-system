<?php

use App\Http\Middleware\EnsureStrongIdentity;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', [
        'middleware' => ['web', 'auth', EnsureUserIsActive::class, EnsureStrongIdentity::class],
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApi = fn (Request $request): bool => $request->is('api/*');

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isApi) {
            return $isApi($request) ? response()->json(['message' => 'يلزم تسجيل الدخول.', 'error' => ['code' => 'unauthenticated']], 401) : null;
        });
        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($isApi) {
            return $isApi($request) ? response()->json(['message' => 'لا تملك صلاحية الوصول.', 'error' => ['code' => 'forbidden']], 403) : null;
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isApi) {
            if ($isApi($request) && $exception->getStatusCode() === 403) {
                return response()->json(['message' => 'لا تملك صلاحية الوصول.', 'error' => ['code' => 'forbidden']], 403);
            }

            return null;
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) use ($isApi) {
            return $isApi($request) ? response()->json(['message' => 'المورد المطلوب غير موجود.', 'error' => ['code' => 'not_found']], 404) : null;
        });
        $exceptions->render(function (ValidationException $exception, Request $request) use ($isApi) {
            return $isApi($request) ? response()->json(['message' => 'البيانات المرسلة غير صالحة.', 'error' => ['code' => 'validation_failed', 'fields' => $exception->errors()]], 422) : null;
        });
        $exceptions->respond(function (Response $response): Response {
            if (request()->is('api/v1/*') || request()->is('api/v1')) {
                $response->headers->set('X-API-Version', (string) config('system.api.version', '1'));
            }

            return $response;
        });
    })->create();
