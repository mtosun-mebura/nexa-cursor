<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        // Ongeauthenticeerde frontend-gebruikers naar meld-pagina (sessie verlopen) i.p.v. direct naar login, met intended voor redirect na inloggen
        $middleware->redirectGuestsTo(fn (Request $request) => route('meld.sessie-verlopen') . '?intended=' . rawurlencode($request->url()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure JSON response for favorite routes so frontend can show the error
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('favorites/*') && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Er is een fout opgetreden: ' . $e->getMessage(),
                ], 500);
            }
        });
    })->create();
