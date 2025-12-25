<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobConfiguration;
use App\Models\JobConfigurationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobConfigurationTypeController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te bekijken.');
        }

        $query = JobConfigurationType::query();

        // Apply active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $driver = DB::connection()->getDriverName();
            $query->where(function($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    $q->whereRaw("name ILIKE ?", ["%{$search}%"])
                      ->orWhereRaw("display_name ILIKE ?", ["%{$search}%"]);
                } else {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                }
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'sort_order');
        $sortDirection = $request->get('direction', 'asc');
        
        if (in_array($sortBy, ['name', 'display_name', 'is_active', 'sort_order', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('sort_order', 'asc')->orderBy('display_name', 'asc');
        }

        // Load all for KT Datatable client-side pagination
        $types = $query->withCount('jobConfigurations')->get();

        // Statistics
        $stats = [
            'total' => JobConfigurationType::count(),
            'active' => JobConfigurationType::where('is_active', true)->count(),
            'inactive' => JobConfigurationType::where('is_active', false)->count(),
        ];

        return view('admin.job-configuration-types.index', compact('types', 'stats'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types aan te maken.');
        }

        return view('admin.job-configuration-types.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types aan te maken.');
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:job_configuration_types,name|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.regex' => 'De naam mag alleen kleine letters en underscores bevatten (bijv. employment_type)',
        ]);

        JobConfigurationType::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.job-configuration-types.index')
            ->with('success', 'Job configuratie type succesvol aangemaakt.');
    }

    public function show(JobConfigurationType $jobConfigurationType)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te bekijken.');
        }

        $jobConfigurationType->loadCount('jobConfigurations');
        $configurationsCount = $jobConfigurationType->jobConfigurations()->count();

        return view('admin.job-configuration-types.show', compact('jobConfigurationType', 'configurationsCount'));
    }

    public function edit(JobConfigurationType $jobConfigurationType)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te bewerken.');
        }

        return view('admin.job-configuration-types.edit', compact('jobConfigurationType'));
    }

    public function update(Request $request, JobConfigurationType $jobConfigurationType)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te bewerken.');
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:job_configuration_types,name,' . $jobConfigurationType->id . '|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.regex' => 'De naam mag alleen kleine letters en underscores bevatten (bijv. employment_type)',
        ]);

        $jobConfigurationType->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.job-configuration-types.index')
            ->with('success', 'Job configuratie type succesvol bijgewerkt.');
    }

    public function destroy(JobConfigurationType $jobConfigurationType)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te verwijderen.');
        }

        // Check if in use
        $configurationsCount = $jobConfigurationType->jobConfigurations()->count();

        if ($configurationsCount > 0) {
            return redirect()->back()
                ->with('error', "Dit type kan niet worden verwijderd omdat het wordt gebruikt door {$configurationsCount} configuratie(s).");
        }

        $jobConfigurationType->delete();

        return redirect()->route('admin.job-configuration-types.index')
            ->with('success', 'Job configuratie type succesvol verwijderd.');
    }

    public function toggleStatus(Request $request, JobConfigurationType $jobConfigurationType)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te bewerken.');
        }

        // Check if trying to deactivate and if it's in use
        if ($jobConfigurationType->is_active) {
            $configurationsCount = $jobConfigurationType->jobConfigurations()->count();
            
            if ($configurationsCount > 0) {
                $message = "Dit type kan niet worden gedeactiveerd omdat het wordt gebruikt door {$configurationsCount} configuratie(s).";
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                
                return back()->with('error', $message);
            }
        }

        $jobConfigurationType->update([
            'is_active' => !$jobConfigurationType->is_active
        ]);

        $status = $jobConfigurationType->is_active ? 'geactiveerd' : 'gedeactiveerd';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Job configuratie type succesvol {$status}.",
                'is_active' => $jobConfigurationType->is_active
            ]);
        }

        return back()->with('success', "Job configuratie type succesvol {$status}.");
    }

    public function import(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuratie types te importeren.');
        }

        // Show import form on GET request
        if ($request->isMethod('get')) {
            return view('admin.job-configuration-types.import');
        }

        $request->validate([
            'json_data' => 'required|string',
            'skip_existing' => 'boolean',
            'create_types' => 'boolean',
        ]);

        $jsonData = $request->json_data;
        $skipExisting = $request->has('skip_existing');
        $createTypes = $request->has('create_types');

        try {
            $data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['json_data' => 'Ongeldige JSON data: ' . json_last_error_msg()]);
            }

            $results = [
                'types_created' => 0,
                'types_skipped' => 0,
                'configurations_created' => 0,
                'configurations_skipped' => 0,
                'errors' => [],
            ];

            // Mapping voor display names
            $typeDisplayNames = [
                'dienstverbanden' => 'Dienstverband Type',
                'werkuren' => 'Werkuren',
                'statussen' => 'Status',
                'salarisindicaties' => 'Salarisindicaties',
            ];

            // Process simple arrays (dienstverbanden, werkuren, statussen)
            foreach (['dienstverbanden', 'werkuren', 'statussen'] as $key) {
                if (!isset($data[$key]) || !is_array($data[$key])) {
                    continue;
                }

                // Get or create type
                $typeName = $key === 'dienstverbanden' ? 'employment_type' : ($key === 'werkuren' ? 'working_hours' : 'status');
                $type = JobConfigurationType::where('name', $typeName)->first();

                if (!$type) {
                    if ($createTypes) {
                        $type = JobConfigurationType::create([
                            'name' => $typeName,
                            'display_name' => $typeDisplayNames[$key] ?? ucfirst(str_replace('_', ' ', $typeName)),
                            'description' => 'Geïmporteerd via JSON',
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                        $results['types_created']++;
                    } else {
                        $results['errors'][] = "Type '{$typeName}' bestaat niet en wordt overgeslagen.";
                        continue;
                    }
                } else {
                    $results['types_skipped']++;
                }

                // Create configurations
                foreach ($data[$key] as $value) {
                    if (empty($value)) {
                        continue;
                    }

                    $existing = JobConfiguration::where('type_id', $type->id)
                        ->where('value', $value)
                        ->whereNull('company_id')
                        ->first();

                    if ($existing) {
                        if ($skipExisting) {
                            $results['configurations_skipped']++;
                            continue;
                        }
                    }

                    JobConfiguration::create([
                        'type_id' => $type->id,
                        'type' => $type->name, // Backward compatibility
                        'value' => $value,
                        'company_id' => null, // Global
                    ]);

                    $results['configurations_created']++;
                }
            }

            // Process salarisindicaties (nested structure)
            if (isset($data['salarisindicaties']) && is_array($data['salarisindicaties'])) {
                foreach ($data['salarisindicaties'] as $subKey => $subArray) {
                    if (!is_array($subArray)) {
                        continue;
                    }

                    // Get or create type for this salarisindicatie sub-type
                    $typeName = 'salary_' . str_replace('_', '_', $subKey);
                    $displayName = 'Salarisindicatie - ' . ucfirst(str_replace('_', ' ', $subKey));
                    
                    $type = JobConfigurationType::where('name', $typeName)->first();

                    if (!$type) {
                        if ($createTypes) {
                            $type = JobConfigurationType::create([
                                'name' => $typeName,
                                'display_name' => $displayName,
                                'description' => 'Geïmporteerd via JSON - Salarisindicaties',
                                'is_active' => true,
                                'sort_order' => 0,
                            ]);
                            $results['types_created']++;
                        } else {
                            $results['errors'][] = "Type '{$typeName}' bestaat niet en wordt overgeslagen.";
                            continue;
                        }
                    } else {
                        $results['types_skipped']++;
                    }

                    // Create configurations for each item
                    foreach ($subArray as $item) {
                        if (is_array($item) && isset($item['label'])) {
                            // Store as JSON string for complex structures
                            $value = json_encode($item);
                        } else {
                            $value = is_string($item) ? $item : json_encode($item);
                        }

                        $existing = JobConfiguration::where('type_id', $type->id)
                            ->where('value', $value)
                            ->whereNull('company_id')
                            ->first();

                        if ($existing) {
                            if ($skipExisting) {
                                $results['configurations_skipped']++;
                                continue;
                            }
                        }

                        JobConfiguration::create([
                            'type_id' => $type->id,
                            'type' => $type->name, // Backward compatibility
                            'value' => $value,
                            'company_id' => null, // Global
                        ]);

                        $results['configurations_created']++;
                    }
                }
            }

            // Build success message
            $message = "Import voltooid: ";
            $messageParts = [];
            if ($results['types_created'] > 0) {
                $messageParts[] = "{$results['types_created']} type(s) aangemaakt";
            }
            if ($results['configurations_created'] > 0) {
                $messageParts[] = "{$results['configurations_created']} configuratie(s) aangemaakt";
            }
            if ($results['types_skipped'] > 0 || $results['configurations_skipped'] > 0) {
                $messageParts[] = ($results['types_skipped'] + $results['configurations_skipped']) . " item(s) overgeslagen";
            }
            
            $message = "Import voltooid: " . implode(", ", $messageParts) . ".";

            if (!empty($results['errors'])) {
                return redirect()->route('admin.job-configuration-types.index')
                    ->with('warning', $message . ' Fouten: ' . implode(' ', $results['errors']));
            }

            return redirect()->route('admin.job-configuration-types.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['json_data' => 'Fout bij importeren: ' . $e->getMessage()]);
        }
    }
}

