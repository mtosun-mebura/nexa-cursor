<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for a vacancy.
     */
    public function toggle(Request $request, Vacancy $vacancy)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je moet ingelogd zijn om vacatures op te slaan.'
                ], 401);
            }

            if (!$vacancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacature niet gevonden.'
                ], 404);
            }

            $favorite = Favorite::where('user_id', $user->id)
                               ->where('vacancy_id', $vacancy->id)
                               ->first();

            if ($favorite) {
                // Remove from favorites
                $favorite->delete();
                $isFavorited = false;
                $message = 'Vacature verwijderd uit je favorieten.';
            } else {
                // Add to favorites
                Favorite::create([
                    'user_id' => $user->id,
                    'vacancy_id' => $vacancy->id,
                ]);
                $isFavorited = true;
                $message = 'Vacature opgeslagen in je favorieten!';
            }

            return response()->json([
                'success' => true,
                'isFavorited' => $isFavorited,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Favorite toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a vacancy is favorited by the current user.
     */
    public function check(Request $request, Vacancy $vacancy)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'isFavorited' => false
                ]);
            }

            if (!$vacancy) {
                return response()->json([
                    'success' => false,
                    'isFavorited' => false
                ]);
            }

            $isFavorited = Favorite::where('user_id', $user->id)
                                  ->where('vacancy_id', $vacancy->id)
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