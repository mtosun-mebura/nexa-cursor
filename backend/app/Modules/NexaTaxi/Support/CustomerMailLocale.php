<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Locale for customer-facing emails: app language (nl|en).
 */
final class CustomerMailLocale
{
    public const SUPPORTED = ['nl', 'en'];

    public static function normalize(mixed $value): string
    {
        $locale = strtolower(trim((string) $value));
        if (str_starts_with($locale, 'en')) {
            return 'en';
        }

        return 'nl';
    }

    public static function fromRequest(?Request $request = null): string
    {
        $request ??= request();
        $header = $request->header('X-App-Locale')
            ?? $request->header('Accept-Language');

        if (is_string($header) && $header !== '') {
            $primary = trim(explode(',', $header)[0] ?? '');
            $primary = trim(explode(';', $primary)[0] ?? '');

            return self::normalize($primary);
        }

        return self::normalize(App::getLocale());
    }

    public static function forUser(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }
        $raw = $user->preferred_locale ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return self::normalize($raw);
    }

    public static function forRide(RideRequest $ride): string
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        if (isset($payload['locale']) && is_string($payload['locale']) && trim($payload['locale']) !== '') {
            return self::normalize($payload['locale']);
        }

        $userId = (int) ($ride->customer_user_id ?? 0);
        if ($userId > 0) {
            $user = User::query()->find($userId);
            $fromUser = self::forUser($user);
            if ($fromUser !== null) {
                return $fromUser;
            }
        }

        return 'nl';
    }

    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function runWith(string $locale, callable $callback): mixed
    {
        $locale = self::normalize($locale);
        $previous = App::getLocale();
        App::setLocale($locale);
        try {
            return $callback();
        } finally {
            App::setLocale($previous);
        }
    }

    public static function persistForUser(?User $user, string $locale): void
    {
        if ($user === null) {
            return;
        }
        $normalized = self::normalize($locale);
        if (($user->preferred_locale ?? null) === $normalized) {
            return;
        }
        if (! \Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'preferred_locale')) {
            return;
        }
        $user->forceFill(['preferred_locale' => $normalized])->save();
    }
}
