<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * FR-041: permission-denied, not-found and expired-session responses render as
         * styled in-app Inertia pages instead of Laravel's unstyled defaults. Delivered
         * as an Inertia page rather than Blade views under resources/views/errors so the
         * app never mixes rendering strategies (constitution Principle II).
         *
         * The status code is set EXPLICITLY. Returning 200 with an error page would break
         * assertForbidden()/assertNotFound() across feature 002's suite (FR-045) and would
         * misreport a denial as a success. tests/Feature/ErrorPageTest.php asserts the
         * page component and the status together so this cannot regress.
         *
         * Unlike the common recipe, the `testing` environment is NOT excluded — the page
         * must be reachable by the automated suite. Only 500s defer to Laravel's handler,
         * and they defer on app.debug rather than on the environment name, so a local
         * stack trace is still available while debugging.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 405, 419, 500], true)) {
                return $response;
            }

            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            if ($request->expectsJson()) {
                return $response;
            }

            return inertia('Errors/Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
