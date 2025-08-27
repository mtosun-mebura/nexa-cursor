<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacancy::query();
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        return $query->paginate(20);
    }

    public function show(int $id)
    {
        return Vacancy::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string',
            'location' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'offer' => 'nullable|string',
            'application_instructions' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'reference_number' => 'nullable|string',
            'logo' => 'nullable|string',
            'salary_range' => 'nullable|string',
            'start_date' => 'nullable|date',
            'working_hours' => 'nullable|string',
            'travel_expenses' => 'boolean',
            'remote_work' => 'boolean',
            'status' => 'nullable|string',
            'language' => 'nullable|string',
            'publication_date' => 'nullable|date',
            'closing_date' => 'nullable|date',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        $vacancy = Vacancy::create($data);
        return response()->json($vacancy, 201);
    }

    public function update(Request $request, int $id)
    {
        $vacancy = Vacancy::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string',
            'location' => 'sometimes|nullable|string',
            'employment_type' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
            'requirements' => 'sometimes|nullable|string',
            'offer' => 'sometimes|nullable|string',
            'application_instructions' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'reference_number' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|string',
            'salary_range' => 'sometimes|nullable|string',
            'start_date' => 'sometimes|nullable|date',
            'working_hours' => 'sometimes|nullable|string',
            'travel_expenses' => 'sometimes|boolean',
            'remote_work' => 'sometimes|boolean',
            'status' => 'sometimes|nullable|string',
            'language' => 'sometimes|nullable|string',
            'publication_date' => 'sometimes|nullable|date',
            'closing_date' => 'sometimes|nullable|date',
            'meta_title' => 'sometimes|nullable|string',
            'meta_description' => 'sometimes|nullable|string',
            'meta_keywords' => 'sometimes|nullable|string',
        ]);
        $vacancy->update($data);
        return response()->json($vacancy);
    }

    public function destroy(int $id)
    {
        $vacancy = Vacancy::findOrFail($id);
        $vacancy->delete();
        return response()->json(['status' => 'ok']);
    }
}


