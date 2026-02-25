<?php

use App\Exceptions\SessionAlreadyCompletedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    */
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
    )
    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    */
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
    })
    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    */
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([
            SessionAlreadyCompletedException::class,
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn($request) => $request->expectsJson() || $request->is('api/*')
        );

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        });

        $exceptions->render(function (SessionAlreadyCompletedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        });

        $exceptions->render(function (HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'HTTP error.',
            ], $e->getStatusCode());
        });
    })
    ->create();
