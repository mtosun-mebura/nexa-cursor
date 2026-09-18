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

        $latestPayment = null;
        if ($ride) {
            $latestPayment = $ride->payments()->orderByDesc('id')->first();
            if ($latestPayment && $latestPayment->status === RidePayment::STATUS_OPEN) {
                $payments->syncRidePaymentFromMollie($conn, $latestPayment);
                $ride = $ride->fresh();
                $latestPayment = $latestPayment->fresh();
            }
        }

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

        $paid = $ride && (
            $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID
            || ($latestPayment && $latestPayment->status === RidePayment::STATUS_PAID)
        );
        $failed = $latestPayment && in_array($latestPayment->status, [
            RidePayment::STATUS_FAILED,
            RidePayment::STATUS_CANCELED,
            RidePayment::STATUS_EXPIRED,
        ], true);

        if ($paid) {
            $request->session()->forget($sessionKey);

            $redirect = redirect()->to(self::withBookingResultQuery($returnUrl, 'betaald'));
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

            return redirect()->to(self::withBookingResultQuery($returnUrl, 'betaling-mislukt'));
        }

        return view('taxi::booking.payment-return', [
            'paid' => false,
            'rideId' => $rideId,
            'refreshUrl' => $request->fullUrl(),
        ]);
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
        if ($parts === false || empty($parts['host'])) {
            return $fallback;
        }

        if (strcasecmp((string) $parts['host'], (string) $request->getHost()) !== 0) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        return $candidate;
    }

    public static function withBookingResultQuery(string $url, string $result): string
    {
        $hash = '';
        if (str_contains($url, '#')) {
            [$url, $hash] = explode('#', $url, 2);
            $hash = '#'.$hash;
        }
        if ($hash === '') {
            $hash = '#boek-rit';
        }

        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $query);
        }
        $query['boeking'] = $result;

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
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
