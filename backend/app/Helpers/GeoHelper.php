<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Calculate distance between two points using Haversine formula
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Get coordinates for a city name
     * 
     * @param string $cityName
     * @return array|null
     */
    public static function getCityCoordinates($cityName)
    {
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
        
        // Check for exact matches first
        foreach ($cityCoordinates as $city => $coords) {
            if (stripos($cityName, $city) !== false) {
                return $coords;
            }
        }
        
        // Check for partial matches
        foreach ($cityCoordinates as $city => $coords) {
            if (stripos($city, $cityName) !== false || stripos($cityName, $city) !== false) {
                return $coords;
            }
        }
        
        return null;
    }
}
