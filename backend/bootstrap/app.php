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
        // Achter reverse proxy (Apache/Varnish/Nginx): juiste scheme/host voor URL’s, sessiecookies en CSRF.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'skillmatching' => \App\Http\Middleware\EnsureSkillmatchingModule::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\OverlayGeneralSettingConfig::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        // Ongeauthenticeerde frontend-gebruikers naar meld-pagina (sessie verlopen) i.p.v. direct naar login, met intended voor redirect na inloggen
        // Relatief pad i.p.v. route(): voorkomt absolute https://-URL’s op :8000 zonder TLS (ERR_CONNECTION_CLOSED).
        $middleware->redirectGuestsTo(fn (Request $request) => '/meld/sessie-verlopen?' . http_build_query(['intended' => $request->url()]));
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
