<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class TaxiDriverAccountStatus
{
    public static function isActive(User $user): bool
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            return true;
        }

        if ($user->is_active === null) {
            return $user->email_verified_at !== null;
        }

        return (bool) $user->is_active;
    }

    public static function inactiveResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Je chauffeuraccount is nog niet actief. Neem contact op met je werkgever of beheerder.',
            'error' => 'driver_not_active',
        ], 403);
    }
}
