<?php

namespace App\Modules\NexaTaxi\Support;

/**
 * Bedrijfs- of locatienaam (bioscoop, stadion, winkel) los van de straatregel.
 * Een kale straat of plaatsnaam wordt niet als extra regel bewaard.
 */
final class RidePlaceName
{
    public static function distinct(mixed $name, mixed $address): ?string
    {
        $place = trim((string) $name);
        if ($place === '') {
            return null;
        }
        if (mb_strlen($place) > 160) {
            $place = trim(mb_substr($place, 0, 160));
        }

        $line = trim((string) $address);
        if ($line === '') {
            return $place;
        }
        if (mb_strtolower($place) === mb_strtolower($line)) {
            return null;
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $line)
        ), static fn (string $part): bool => $part !== ''));

        $placeLower = mb_strtolower($place);
        foreach ($parts as $part) {
            if (mb_strtolower($part) === $placeLower) {
                return null;
            }
        }

        $street = mb_strtolower($parts[0] ?? '');
        if ($street !== '' && (
            $street === $placeLower
            || str_starts_with($street, $placeLower.' ')
            || str_starts_with($placeLower, $street)
        )) {
            return null;
        }

        return $place;
    }

    public static function fromPayload(mixed $payload, string $which): ?string
    {
        if (! is_array($payload)) {
            return null;
        }
        $key = $which === 'dropoff' ? 'dropoff_place_name' : 'pickup_place_name';
        $name = trim((string) ($payload[$key] ?? ''));

        return $name !== '' ? $name : null;
    }
}
