<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for a vacancy.
     * Route parameter {vacancy} can be vacancy id (int/string) or a Vacancy model (e.g. App\Modules\Skillmatching\Models\Vacancy).
     */
    public function toggle(Request $request, $vacancy)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je moet ingelogd zijn om vacatures op te slaan.'
                ], 401);
            }

            $vacancyId = $vacancy instanceof \Illuminate\Database\Eloquent\Model
                ? (int) $vacancy->getKey()
                : (int) $vacancy;

            if ($vacancyId <= 0 || !DB::table('vacancies')->where('id', $vacancyId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacature niet gevonden.'
                ], 404);
            }

            $exists = DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('vacancy_id', $vacancyId)
                ->exists();

            if ($exists) {
                DB::table('favorites')
                    ->where('user_id', $user->id)
                    ->where('vacancy_id', $vacancyId)
                    ->delete();
                $isFavorited = false;
                $message = 'Vacature verwijderd uit je favorieten.';
            } else {
                DB::table('favorites')->insert([
                    'user_id' => $user->id,
                    'vacancy_id' => $vacancyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $isFavorited = true;
                $message = 'Vacature opgeslagen in je favorieten!';
            }

            return response()->json([
                'success' => true,
                'isFavorited' => $isFavorited,
                'message' => $message
            ]);
        } catch (\Throwable $e) {
            \Log::error('Favorite toggle error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a vacancy is favorited by the current user.
     * Route parameter {vacancy} can be vacancy id (int/string) or a Vacancy model.
     */
    public function check(Request $request, $vacancy)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'isFavorited' => false
                ]);
            }

            $vacancyId = $vacancy instanceof \Illuminate\Database\Eloquent\Model
                ? (int) $vacancy->getKey()
                : (int) $vacancy;

            if ($vacancyId <= 0) {
                return response()->json([
                    'success' => false,
                    'isFavorited' => false
                ]);
            }

            $isFavorited = DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('vacancy_id', $vacancyId)
                ->exists();

            return response()->json([
                'success' => true,
                'isFavorited' => $isFavorited
            ]);
        } catch (\Exception $e) {
            \Log::error('Favorite check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'isFavorited' => false
            ]);
        }
    }

    /**
     * Get all favorites for the current user.
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $favorites = $user->favoriteVacancies()
                          ->with(['company', 'category'])
                          ->withPivot('created_at', 'updated_at')
                          ->latest('favorites.created_at')
                          ->paginate(12);

        return view('frontend.pages.favorites', compact('favorites'));
    }
}