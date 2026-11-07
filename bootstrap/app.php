<?php

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\Role;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['active' => ActiveAccount::class, 'role' => Role::class]);
        $middleware->web(append: [SecurityHeaders::class]);

        if (filter_var($_SERVER['LMS_VERCEL_RUNTIME'] ?? $_ENV['LMS_VERCEL_RUNTIME'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->is('activate/*')) {
                return response()->view('auth.activation-link-invalid', [], 410);
            }
        });
    })->create();
