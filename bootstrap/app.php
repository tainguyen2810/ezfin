<?php

use App\Http\Middleware\NoIndex;
use App\Http\Middleware\PublicRedirects;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(PublicRedirects::class);

        // Ứng dụng khách hàng (module Personal); trang quản trị Filament có trang đăng nhập riêng.
        $middleware->redirectGuestsTo(fn () => route('personal.login'));
        $middleware->redirectUsersTo(fn () => route('personal.dashboard'));

        $middleware->alias([
            'noindex' => NoIndex::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
