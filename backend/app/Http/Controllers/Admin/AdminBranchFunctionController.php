<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchFunction;
use Illuminate\Http\Request;

class AdminBranchFunctionController extends Controller
{
    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        // Replace one-or-more whitespace characters with underscore for storage key.
        $value = preg_replace('/\s+/', '_', $value) ?? $value;
        return $value;
    }

    public function store(Request $request, Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = $this->normalizeKey($data['name']);

        $exists = $branch->functions()->where('name', $name)->exists();
        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Deze functie bestaat al binnen deze branch.',
                    'errors' => ['name' => ['Deze functie bestaat al binnen deze branch.']],
                ], 422);
            }

            return back()->withErrors(['name' => 'Deze functie bestaat al binnen deze branch.'])->withInput();
        }

        $function = $branch->functions()->create([
            'name' => $name,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Functie toegevoegd.',
                'function' => [
                    'id' => $function->id,
                    'name' => $function->name,
                    'display_name' => $function->display_name,
                    'update_url' => route('admin.skillmatching.branches.functions.update', [$branch, $function]),
                    'destroy_url' => route('admin.skillmatching.branches.functions.destroy', [$branch, $function]),
                ],
            ]);
        }

        return back()->with('success', 'Functie toegevoegd.');
    }

    public function update(Request $request, Branch $branch, BranchFunction $function)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        if ($function->branch_id !== $branch->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = $this->normalizeKey($data['name']);

        $exists = $branch->functions()
            ->where('name', $name)
            ->where('id', '!=', $function->id)
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Deze functie bestaat al binnen deze branch.',
                    'errors' => ['name' => ['Deze functie bestaat al binnen deze branch.']],
                ], 422);
            }

            return back()->withErrors(['name' => 'Deze functie bestaat al binnen deze branch.'])->withInput();
        }

        $function->update([
            'name' => $name,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Functie bijgewerkt.',
                'function' => [
                    'id' => $function->id,
                    'name' => $function->name,
                    'display_name' => $function->display_name,
                ],
            ]);
        }

        return back()->with('success', 'Functie bijgewerkt.');
    }

    public function destroy(Request $request, Branch $branch, BranchFunction $function)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        if ($function->branch_id !== $branch->id) {
            abort(404);
        }

        $function->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Functie verwijderd.',
                'id' => $function->id,
            ]);
        }

        return back()->with('success', 'Functie verwijderd.');
    }
}




