<?php

namespace App\Http\Controllers;

use App\Services\PostcodeLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostcodeController extends Controller
{
    public function lookup(Request $request, PostcodeLookupService $lookup): JsonResponse
    {
        $request->validate([
            'postcode' => 'required|string|max:10',
            'huisnummer' => 'required|string|max:10',
        ]);

        $result = $lookup->lookup((string) $request->input('postcode'), (string) $request->input('huisnummer'));
        if (empty($result['success'])) {
            $status = ($result['message'] ?? '') === 'Ongeldig postcode formaat. Gebruik formaat: 1234AB'
                ? 422
                : 404;

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Kon het adres niet vinden. Controleer de postcode en het huisnummer.',
            ], $status);
        }

        return response()->json($result);
    }
}
