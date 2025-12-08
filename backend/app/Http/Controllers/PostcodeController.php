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
     * Uses free Dutch postcode APIs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'postcode' => 'required|string|max:10',
            'house_number' => 'required|string|max:10',
        ]);

        $postcode = strtoupper(preg_replace('/\s+/', '', $request->postcode));
        $houseNumber = trim($request->house_number);

        // Validate Dutch postcode format (1234AB)
        if (!preg_match('/^[1-9][0-9]{3}[A-Z]{2}$/', $postcode)) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldig postcode formaat. Gebruik formaat: 1234AB'
            ], 422);
        }

        // Try multiple free APIs in order of preference
        $apis = [
            'pdok_search' => function() use ($postcode, $houseNumber) {
                // PDOK Locatieserver API (official Dutch government service)
                try {
                    // Use the search endpoint with proper query
                    $query = "{$postcode} {$houseNumber}";
                    $response = Http::timeout(10)->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/search', [
                        'q' => $query,
                        'fq' => 'type:adres',
                        'fl' => 'weergavenaam,straatnaam,huisnummer,postcode,woonplaatsnaam',
                        'rows' => 1
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['response']['docs']) && count($data['response']['docs']) > 0) {
                            $address = $data['response']['docs'][0];
                            return [
                                'success' => true,
                                'street' => $address['straatnaam'] ?? '',
                                'house_number' => $address['huisnummer'] ?? $houseNumber,
                                'postal_code' => $address['postcode'] ?? $postcode,
                                'city' => $address['woonplaatsnaam'] ?? '',
                                'country' => 'Nederland',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('PDOK search API error', ['error' => $e->getMessage()]);
                }
                return null;
            },
            'pdok_suggest' => function() use ($postcode, $houseNumber) {
                // PDOK suggest API (alternative endpoint)
                try {
                    $query = "{$postcode} {$houseNumber}";
                    $response = Http::timeout(10)->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest', [
                        'q' => $query,
                        'fq' => 'type:adres',
                        'rows' => 1
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['response']['docs']) && count($data['response']['docs']) > 0) {
                            $address = $data['response']['docs'][0];
                            return [
                                'success' => true,
                                'street' => $address['straatnaam'] ?? '',
                                'house_number' => $address['huisnummer'] ?? $houseNumber,
                                'postal_code' => $address['postcode'] ?? $postcode,
                                'city' => $address['woonplaatsnaam'] ?? '',
                                'country' => 'Nederland',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('PDOK suggest API error', ['error' => $e->getMessage()]);
                }
                return null;
            },
            'postcodeapi_nu' => function() use ($postcode, $houseNumber) {
                // PostcodeAPI.nu (free community service)
                try {
                    $response = Http::timeout(10)->get("https://api.postcodeapi.nu/v2/addresses", [
                        'postcode' => $postcode,
                        'number' => $houseNumber
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['_embedded']['addresses'][0])) {
                            $address = $data['_embedded']['addresses'][0];
                            return [
                                'success' => true,
                                'street' => $address['street'] ?? '',
                                'house_number' => $address['number'] ?? $houseNumber,
                                'postal_code' => $address['postcode'] ?? $postcode,
                                'city' => $address['city']['label'] ?? '',
                                'country' => 'Nederland',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('PostcodeAPI.nu error', ['error' => $e->getMessage()]);
                }
                return null;
            }
        ];

        // Try each API until one succeeds
        foreach ($apis as $apiName => $apiCall) {
            try {
                $result = $apiCall();
                if ($result && isset($result['success']) && $result['success']) {
                    return response()->json($result);
                }
            } catch (\Exception $e) {
                Log::debug("Postcode API {$apiName} failed", [
                    'postcode' => $postcode,
                    'house_number' => $houseNumber,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // All APIs failed
        Log::warning('All postcode APIs failed', [
            'postcode' => $postcode,
            'house_number' => $houseNumber
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Kon het adres niet vinden. Controleer de postcode en het huisnummer.'
        ], 404);
    }
}

