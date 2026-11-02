<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e->getPrevious() instanceof ModelNotFoundException ? 'Booking not found.' : 'Not found.'], 404)
            : null);

        // A write that never got the lock did not happen, so report it as retryable rather than a generic 500.
        $exceptions->render(fn (QueryException $e, Request $request) => $request->is('api/*') && str_contains($e->getMessage(), 'database is locked')
            ? response()->json(['message' => 'The booking system is busy. Please try again.'], 503)
            : null);
    })->create();
