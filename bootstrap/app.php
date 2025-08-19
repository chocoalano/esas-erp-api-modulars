<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return route('unauthorized');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Tangani Exception API
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Tangani 404 Not Found
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'message' => 'Resource not found',
                    ], 404);
                }
                // Tangani 403 Forbidden
                if (
                    ($e instanceof \Illuminate\Auth\Access\AuthorizationException) ||
                    ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 403)
                ) {
                    return response()->json([
                        'message' => 'User does not have the right permissions.'
                    ], 403);
                }

                // Tangani Validasi Error
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Tangani semua error tak terduga (500)
                if (!$e instanceof HttpExceptionInterface) {
                    return response()->json([
                        'message' => 'Internal server error',
                        'error' => $e->getMessage(), // Optional: hapus jika tidak ingin menampilkan pesan debug
                    ], 500);
                }
            }

            // Untuk selain API, tetap gunakan handler default Laravel
            return null;
        });
    })
    ->create();
