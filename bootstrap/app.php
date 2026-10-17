<?php

use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\UnifiedJsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [UnifiedJsonResponse::class]);
        $middleware->alias(['not-banned' => EnsureUserIsNotBanned::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $json = fn (string $message, int $status, array $extra = []) => response()->json(
            ['data' => null, 'meta' => null, 'message' => $message] + $extra,
            $status,
        );

        $exceptions->render(function (ValidationException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json(__('store.validation_failed'), $e->status, ['errors' => $e->errors()]);
            }
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json(__('store.unauthenticated'), 401);
            }
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json(__('store.forbidden'), 403);
            }
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json(__('store.not_found'), 404);
            }
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json(__('store.too_many_requests'), 429)->withHeaders($e->getHeaders());
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($json) {
            if ($request->is('api/*') && $e->getStatusCode() >= 500 && ! config('app.debug')) {
                return $json(__('store.server_error'), $e->getStatusCode());
            }
        });
    })->create();
