<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'tenant.host' => \App\Http\Middleware\ResolveTenantFromHost::class,
            'tenant.domain.user' => \App\Http\Middleware\EnforceTenantDomainMatchesUser::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\AdminRoutesUseWebGuard::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ApplyDevSimulatedTenantHost::class,
            \App\Http\Middleware\OverlayGeneralSettingConfig::class,
            \App\Http\Middleware\WebsiteAdminPreviewSession::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TenantMiddleware::class,
            \App\Http\Middleware\EnforceTenantDomainMatchesUser::class,
        ]);

        // Ongeauthenticeerde frontend-gebruikers naar meld-pagina (sessie verlopen) i.p.v. direct naar login, met intended voor redirect na inloggen
        // Relatief pad i.p.v. route(): voorkomt absolute https://-URL’s op :8000 zonder TLS (ERR_CONNECTION_CLOSED).
        $middleware->redirectGuestsTo(fn (Request $request) => '/meld/sessie-verlopen?'.http_build_query(['intended' => $request->url()]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Altijd onze volledige 403-pagina (CodePen-stijl), niet een gecachte oude layout met sidebar.
        // HttpException (abort(403)) implementeert HttpExceptionInterface; Gate/policy gebruikt AuthorizationException (anders).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $is403 = false;
            $message = '';

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
                $is403 = true;
                $message = $e->getMessage();
            } elseif ($e instanceof AuthorizationException) {
                $status = $e->hasStatus() ? (int) $e->status() : 403;
                if ($status === 403) {
                    $is403 = true;
                    $message = $e->getMessage();
                }
            }

            if (! $is403 || ! view()->exists('errors.403')) {
                return null;
            }

            return response()
                ->view('errors.403', [
                    'exceptionMessage' => $message !== '' ? $message : null,
                ], 403)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        });

        // Ensure JSON response for favorite routes so frontend can show the error
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('favorites/*') && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Er is een fout opgetreden: '.$e->getMessage(),
                ], 500);
            }
        });
    })->create();
