<?php

require_once __DIR__ . '/../app/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Biarkan request API menghasilkan response JSON standar
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            // Halaman 404 (Halaman / resource tidak ditemukan)
            if ($e instanceof NotFoundHttpException) {
                return response()->view('errors.404', [], 404);
            }

            // Error HTTP standar lainnya (403, 419, 503, dll)
            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
                if (view()->exists("errors.{$status}")) {
                    return response()->view("errors.{$status}", ['exception' => $e], $status);
                }
            }

            // Bila bukan mode debug (production), tangkap seluruh error dan tampilkan halaman 500 yang ramah
            if (! config('app.debug')) {
                return response()->view('errors.500', ['exception' => $e], 500);
            }
        });
    })->create();
