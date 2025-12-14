<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchFunction;
use App\Models\BranchFunctionSkill;
use Illuminate\Http\Request;

class AdminBranchFunctionSkillController extends Controller
{
    public function index(Branch $branch, BranchFunction $function)
    {
        if ($function->branch_id !== $branch->id) {
            abort(404);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        $skills = $function->skills()
            ->orderBy('name')
            ->get(['id', 'branch_function_id', 'name'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'display_name' => str_replace('_', ' ', (string) $s->name),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'skills' => $skills,
        ]);
    }

    public function store(Request $request, Branch $branch, BranchFunction $function)
    {
        if ($function->branch_id !== $branch->id) {
            abort(404);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = $this->normalizeKey($data['name']);

        $exists = $function->skills()->where('name', $name)->exists();
        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Deze vaardigheid bestaat al voor deze functie.',
                    'errors' => ['name' => ['Deze vaardigheid bestaat al voor deze functie.']],
                ], 422);
            }
            return back()->withErrors(['name' => 'Deze vaardigheid bestaat al voor deze functie.'])->withInput();
        }

        $skill = $function->skills()->create([
            'name' => $name,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vaardigheid toegevoegd.',
                'skill' => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'display_name' => $skill->display_name,
                ],
            ]);
        }

        return back()->with('success', 'Vaardigheid toegevoegd.');
    }

    public function destroy(Request $request, Branch $branch, BranchFunction $function, BranchFunctionSkill $skill)
    {
        if ($function->branch_id !== $branch->id) {
            abort(404);
        }

        if ($skill->branch_function_id !== $function->id) {
            abort(404);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        $skill->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vaardigheid verwijderd.',
                'id' => $skill->id,
            ]);
        }

        return back()->with('success', 'Vaardigheid verwijderd.');
    }

    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        // Replace one-or-more whitespace characters with underscore for storage key.
        $value = preg_replace('/\s+/', '_', $value) ?? $value;
        return $value;
    }
}
