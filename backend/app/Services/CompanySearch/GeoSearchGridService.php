<?php

namespace App\Services\CompanySearch;

class GeoSearchGridService
{
    /**
     * @return list<array{city: string, province: string, lat: float, lng: float, radius_km: int}>
     */
    public function points(CompanySearchData $search): array
    {
        $radius = max(5, min(100, $search->radiusKm));
        $city = trim((string) $search->city);
        if ($city !== '') {
            $match = $this->findCity($city, $search->provinces);
            if ($match !== null) {
                return [$match + ['radius_km' => $radius]];
            }

            $province = $search->provinces[0] ?? '';

            return [[
                'city' => $city,
                'province' => $province,
                'lat' => 0.0,
                'lng' => 0.0,
                'radius_km' => $radius,
            ]];
        }

        $out = [];
        $grid = config('company-enrichment.province_grid', []);
        foreach ($search->provinces as $province) {
            $points = $grid[$province] ?? [];
            if ($points === []) {
                $out[] = [
                    'city' => $province,
                    'province' => $province,
                    'lat' => 0.0,
                    'lng' => 0.0,
                    'radius_km' => $radius,
                ];

                continue;
            }
            foreach ($points as $point) {
                $out[] = [
                    'city' => (string) ($point['city'] ?? $province),
                    'province' => $province,
                    'lat' => (float) ($point['lat'] ?? 0),
                    'lng' => (float) ($point['lng'] ?? 0),
                    'radius_km' => min($radius, (int) config('company-enrichment.search.grid_radius_km', 25)),
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $provinces
     * @return array{city: string, province: string, lat: float, lng: float}|null
     */
    private function findCity(string $city, array $provinces): ?array
    {
        $needle = mb_strtolower($city);
        $grid = config('company-enrichment.province_grid', []);
        $scope = $provinces !== [] ? $provinces : array_keys($grid);
        foreach ($scope as $province) {
            foreach ($grid[$province] ?? [] as $point) {
                if (mb_strtolower((string) ($point['city'] ?? '')) === $needle) {
                    return [
                        'city' => (string) $point['city'],
                        'province' => $province,
                        'lat' => (float) $point['lat'],
                        'lng' => (float) $point['lng'],
                    ];
                }
            }
        }

        return null;
    }
}
