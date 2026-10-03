<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use App\Support\Tenancy\TenantFrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxiBookingPaymentController extends Controller
{
    public function returnPage(
        Request $request,
        ModuleDatabaseService $moduleDb,
        TaxiRidePaymentService $payments
    ): RedirectResponse|View {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideId = (int) $request->query('ride', 0);

        $ride = $rideId > 0
            ? RideRequest::on($conn)->find($rideId)
            : null;

        $sessionKey = 'nexataxi.booking_payment.'.$rideId;
        $sessionStored = is_array($request->session()->get($sessionKey))
            ? $request->session()->get($sessionKey)
            : [];
        $payload = is_array($ride?->booking_payload) ? $ride->booking_payload : [];
        $payloadStored = is_array($payload['payment_return'] ?? null)
            ? $payload['payment_return']
            : [];
        $stored = array_merge($payloadStored, $sessionStored);
        $returnUrl = self::safeReturnUrl(
            is_string($stored['return_url'] ?? null) ? $stored['return_url'] : null,
            $request,
            $ride !== null ? (int) ($ride->company_id ?? 0) : null
        );

        $trackToken = '';
        if (is_string($stored['track_token'] ?? null) && $stored['track_token'] !== '') {
            $trackToken = (string) $stored['track_token'];
        } elseif ($ride && is_string($ride->customer_track_token ?? null)) {
            $trackToken = (string) $ride->customer_track_token;
        }

        $isCustomerApp = (($stored['channel'] ?? '') === 'customer_app')
            || str_contains($returnUrl, '/taxi/klant')
            || self::isAppDeepLink($returnUrl);

        $latestPayment = null;
        if ($ride) {
            $latestPayment = $ride->payments()->orderByDesc('id')->first();
            // Mollie redirect komt vaak vóór de definitieve status: kort snel doorsyncen.
            $attempts = $isCustomerApp ? 5 : 1;
            for ($i = 0; $i < $attempts; $i++) {
                if (! $latestPayment || $latestPayment->status !== RidePayment::STATUS_OPEN) {
                    break;
                }
                if ($i > 0) {
                    usleep(250_000);
                }
                $payments->syncRidePaymentFromMollie($conn, $latestPayment);
                $ride = $ride->fresh();
                $latestPayment = $latestPayment->fresh();
            }
        }

        $paid = $ride && (
            $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID
            || ($latestPayment && $latestPayment->status === RidePayment::STATUS_PAID)
        );
        $paymentStatus = $latestPayment ? (string) $latestPayment->status : '';
        $failed = in_array($paymentStatus, [
            RidePayment::STATUS_FAILED,
            RidePayment::STATUS_CANCELED,
            RidePayment::STATUS_EXPIRED,
        ], true);

        if ($paid) {
            $request->session()->forget($sessionKey);

            $redirect = redirect()->to(self::withBookingResultQuery($returnUrl, 'betaald', [
                'token' => $trackToken,
            ]));
            if (is_string($stored['message'] ?? null) && $stored['message'] !== '') {
                $redirect->with('nexataxi_booking_message', $stored['message']);
            }
            if (is_string($stored['portal_login_url'] ?? null) && $stored['portal_login_url'] !== '') {
                $redirect->with('nexataxi_booking_portal_login_url', $stored['portal_login_url']);
            }

            return $redirect;
        }

        if ($failed) {
            $request->session()->forget($sessionKey);

            $reden = match ($paymentStatus) {
                RidePayment::STATUS_FAILED => 'mislukt',
                RidePayment::STATUS_CANCELED => 'geannuleerd',
                RidePayment::STATUS_EXPIRED => 'verlopen',
                default => 'mislukt',
            };

            return redirect()->to(self::withBookingResultQuery($returnUrl, 'betaling-mislukt', [
                'reden' => $reden,
                'token' => $trackToken,
            ]));
        }

        // Klant-app: meteen terug naar rit; live-poll bevestigt betaling als die nog open staat.
        if ($isCustomerApp) {
            $request->session()->forget($sessionKey);

            return redirect()->to(self::withBookingResultQuery($returnUrl, 'betaling-bezig', [
                'token' => $trackToken,
            ]));
        }

        return view('taxi::booking.payment-return', [
            'paid' => false,
            'rideId' => $rideId,
            'refreshUrl' => $request->fullUrl(),
        ]);
    }

    /** Native Expo-app deep-link schemes (Mollie redirect → Laravel → app). */
    private const APP_RETURN_SCHEMES = ['nexataxi'];

    /** @var list<string> */
    private const APP_RETURN_HOSTS = ['customer'];

    public static function isAppDeepLink(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));

        return in_array($scheme, self::APP_RETURN_SCHEMES, true);
    }

    public static function safeReturnUrl(?string $candidate, Request $request, ?int $companyId = null): string
    {
        $fallback = TenantFrontendUrl::for(url('/'), ($companyId ?? 0) > 0 ? $companyId : null, $request);
        if (! str_contains($fallback, '#')) {
            $fallback .= '#boek-rit';
        }
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return $fallback;
        }

        if (str_starts_with($candidate, '/') && ! str_starts_with($candidate, '//')) {
            return url($candidate);
        }

        $parts = parse_url($candidate);
        if ($parts === false) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (in_array($scheme, self::APP_RETURN_SCHEMES, true)) {
            $host = strtolower((string) ($parts['host'] ?? ''));
            if ($host === '' || ! in_array($host, self::APP_RETURN_HOSTS, true)) {
                return $fallback;
            }
            $path = (string) ($parts['path'] ?? '');
            if ($path !== '' && ! str_starts_with($path, '/')) {
                $path = '/'.$path;
            }
            $query = '';
            if (! empty($parts['query'])) {
                $query = '?'.$parts['query'];
            }

            return $scheme.'://'.$host.$path.$query;
        }

        if (empty($parts['host'])) {
            return $fallback;
        }

        if (strcasecmp((string) $parts['host'], (string) $request->getHost()) !== 0) {
            return $fallback;
        }

        if (! in_array($scheme === '' ? 'https' : $scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        return $candidate;
    }

    /**
     * @param  array<string, string>  $extra
     */
    public static function withBookingResultQuery(string $url, string $result, array $extra = []): string
    {
        $hash = '';
        if (str_contains($url, '#')) {
            [$url, $hash] = explode('#', $url, 2);
            $hash = '#'.$hash;
        }

        $isAppLink = self::isAppDeepLink($url);
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if ($path === '' && ! $isAppLink) {
            $path = '/';
        }
        if (! $isAppLink && $hash === '' && ! str_contains($path, '/taxi/klant')) {
            // Website-boeking: anker; klant-app / native deeplink heeft geen #boek-rit nodig.
            $hash = '#boek-rit';
        }
        if ($isAppLink) {
            $hash = '';
        }

        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $query);
        }
        $query['boeking'] = $result;
        foreach ($extra as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $query[$key] = $value;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        $origin = '';
        if (is_string($scheme) && is_string($host) && $scheme !== '' && $host !== '') {
            $origin = $scheme.'://'.$host;
            if (is_int($port) && $port > 0) {
                $origin .= ':'.$port;
            }
        }

        return $origin.$path.'?'.http_build_query($query).$hash;
    }
}
