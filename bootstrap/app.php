<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render terminates TLS and proxies to the container, so trust the
        // forwarding headers to generate correct https URLs (e.g. Swagger UI).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! $request->is('api/*') || ! $response instanceof JsonResponse || $response->isSuccessful()) {
                return $response;
            }

            $data = $response->getData(true);
            $message = (string) ($data['message'] ?? Response::$statusTexts[$response->getStatusCode()] ?? 'Request failed.');

            $payload = [
                'success' => false,
                'message' => $message,
            ];

            if (isset($data['errors'])) {
                $payload['errors'] = $data['errors'];
            }

            if (isset($data['code'])) {
                $payload['code'] = $data['code'];
            }

            return response()->json($payload, $response->getStatusCode());
        });
    })->create();
