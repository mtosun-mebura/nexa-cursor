<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeController extends Controller
{
    /**
     * Lookup address by postcode and house number
     * Uses OpenPostcode API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'postcode' => 'required|string|max:10',
            'huisnummer' => 'required|string|max:10',
        ]);

        $postcode = strtoupper(preg_replace('/\s+/', '', $request->postcode));
        $huisnummer = trim($request->huisnummer);

        // Validate Dutch postcode format (1234AB)
        if (!preg_match('/^[1-9][0-9]{3}[A-Z]{2}$/', $postcode)) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldig postcode formaat. Gebruik formaat: 1234AB'
            ], 422);
        }

        try {
            // Use OpenPostcode API
            $response = Http::timeout(10)->get('https://openpostcode.nl/api/address', [
                'postcode' => $postcode,
                'huisnummer' => $huisnummer
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if we got valid data
                if (isset($data['straat']) && isset($data['woonplaats'])) {
                    return response()->json([
                        'success' => true,
                        'street' => $data['straat'] ?? '',
                        'house_number' => $data['huisnummer'] ?? $huisnummer,
                        'postal_code' => $data['postcode'] ?? $postcode,
                        'city' => $data['woonplaats'] ?? '',
                        'country' => $data['provincie'] ? 'Nederland' : 'Nederland',
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::debug('OpenPostcode API error', [
                'error' => $e->getMessage(),
                'postcode' => $postcode,
                'huisnummer' => $huisnummer
            ]);
        }

        // API failed or no result
        return response()->json([
            'success' => false,
            'message' => 'Kon het adres niet vinden. Controleer de postcode en het huisnummer.'
        ], 404);
    }
}

