<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param  float  $lat1  Latitude of first point
     * @param  float  $lon1  Longitude of first point
     * @param  float  $lat2  Latitude of second point
     * @param  float  $lon2  Longitude of second point
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get coordinates for a city name
     *
     * @param  string  $cityName
     * @return array{latitude: float, longitude: float}|null
     */
    public static function getCityCoordinates($cityName)
    {
        $cityName = trim((string) $cityName);
        if ($cityName === '') {
            return null;
        }

        $cityCoordinates = [
            'Amsterdam' => ['latitude' => 52.3676, 'longitude' => 4.9041],
            'Rotterdam' => ['latitude' => 51.9244, 'longitude' => 4.4777],
            'Den Haag' => ['latitude' => 52.0705, 'longitude' => 4.3007],
            'Utrecht' => ['latitude' => 52.0907, 'longitude' => 5.1214],
            'Eindhoven' => ['latitude' => 51.4416, 'longitude' => 5.4697],
            'Tilburg' => ['latitude' => 51.5555, 'longitude' => 5.0913],
            'Groningen' => ['latitude' => 53.2194, 'longitude' => 6.5665],
            'Almere' => ['latitude' => 52.3508, 'longitude' => 5.2647],
            'Breda' => ['latitude' => 51.5719, 'longitude' => 4.7683],
            'Nijmegen' => ['latitude' => 51.8426, 'longitude' => 5.8586],
            'Arnhem' => ['latitude' => 51.9851, 'longitude' => 5.8987],
            'Maastricht' => ['latitude' => 50.8514, 'longitude' => 5.6910],
            'Zwolle' => ['latitude' => 52.5168, 'longitude' => 6.0830],
            'Alkmaar' => ['latitude' => 52.6316, 'longitude' => 4.7485],
            'Leeuwarden' => ['latitude' => 53.2012, 'longitude' => 5.7999],
            'Enschede' => ['latitude' => 52.2205, 'longitude' => 6.8958],
        ];

        $normalized = self::normalizeCityName($cityName);
        $aliases = [
            'den haag' => 'Den Haag',
            "'s-gravenhage" => 'Den Haag',
            "'s gravenhage" => 'Den Haag',
            's-gravenhage' => 'Den Haag',
            'gravenhage' => 'Den Haag',
            'the hague' => 'Den Haag',
        ];
        if (isset($aliases[$normalized])) {
            return $cityCoordinates[$aliases[$normalized]];
        }

        foreach ($cityCoordinates as $city => $coords) {
            if (self::normalizeCityName($city) === $normalized) {
                return $coords;
            }
        }

        foreach ($cityCoordinates as $city => $coords) {
            if (stripos($cityName, $city) !== false) {
                return $coords;
            }
        }

        foreach ($cityCoordinates as $city => $coords) {
            if (stripos($city, $cityName) !== false || stripos($cityName, $city) !== false) {
                return $coords;
            }
        }

        return null;
    }

    /**
     * Kaartmidden voor een adres: stad gaat voor als opgeslagen coördinaten in een andere stad liggen.
     *
     * @return array{0: float, 1: float}
     */
    public static function mapCenterForAddress(
        ?string $city,
        mixed $latitude,
        mixed $longitude,
        float $fallbackLat = 0.0,
        float $fallbackLng = 0.0,
        float $sameCityMaxKm = 30.0
    ): array {
        $storedLat = is_numeric($latitude) ? (float) $latitude : 0.0;
        $storedLng = is_numeric($longitude) ? (float) $longitude : 0.0;
        $hasStored = abs($storedLat) > 0.0001 || abs($storedLng) > 0.0001;
        $cityCoords = self::getCityCoordinates((string) ($city ?? ''));

        if ($cityCoords !== null && $hasStored) {
            $distance = self::calculateDistance(
                $cityCoords['latitude'],
                $cityCoords['longitude'],
                $storedLat,
                $storedLng
            );
            if ($distance <= $sameCityMaxKm) {
                return [$storedLat, $storedLng];
            }

            return [(float) $cityCoords['latitude'], (float) $cityCoords['longitude']];
        }

        if ($cityCoords !== null) {
            return [(float) $cityCoords['latitude'], (float) $cityCoords['longitude']];
        }

        if ($hasStored) {
            return [$storedLat, $storedLng];
        }

        if ($fallbackLat === 0.0 && $fallbackLng === 0.0) {
            return [52.2215, 6.8937];
        }

        return [$fallbackLat, $fallbackLng];
    }

    private static function normalizeCityName(string $cityName): string
    {
        $value = trim($cityName);
        $value = strtr($value, [
            '’' => "'",
            '‘' => "'",
            '`' => "'",
            '´' => "'",
        ]);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }
}
