<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobConfiguration;
use App\Models\JobConfigurationType;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobConfigurationController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te bekijken.');
        }

        $query = JobConfiguration::query();

        // Apply type filter
        if ($request->filled('type')) {
            // Support both old type string and new type_id
            if (is_numeric($request->type)) {
                $query->where('type_id', $request->type);
            } else {
                $query->where('type', $request->type);
            }
        }

        // Apply company filter
        if ($request->filled('company_id')) {
            if ($request->company_id === 'global') {
                $query->whereNull('company_id');
            } else {
                $query->where('company_id', $request->company_id);
            }
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $driver = DB::connection()->getDriverName();
            $query->where(function($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    $q->whereRaw("value ILIKE ?", ["%{$search}%"]);
                } else {
                    $q->where('value', 'like', "%{$search}%");
                }
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'type');
        $sortDirection = $request->get('direction', 'asc');
        
        if (in_array($sortBy, ['type', 'value', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('type', 'asc')->orderBy('value', 'asc');
        }

        // Load all for KT Datatable client-side pagination
        $configurations = $query->with('company')->get();
        
        // Check which configurations are in use (for bulk delete checkbox)
        foreach ($configurations as $config) {
            $usageCount = 0;
            if ($config->type === 'employment_type') {
                $usageCount = DB::table('vacancies')
                    ->where('employment_type', $config->value)
                    ->count();
            } elseif ($config->type === 'working_hours') {
                $usageCount = DB::table('vacancies')
                    ->where('working_hours', $config->value)
                    ->count();
            } elseif ($config->type === 'status') {
                $usageCount = DB::table('vacancies')
                    ->where('status', $config->value)
                    ->count();
            }
            $config->in_use = $usageCount > 0;
            $config->usage_count = $usageCount;
        }

        // Get types for filter (from job_configuration_types table)
        $types = JobConfigurationType::active()->ordered()->get();

        // Get companies for filter
        $companies = Company::orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => JobConfiguration::count(),
            'global' => JobConfiguration::whereNull('company_id')->count(),
            'by_type' => JobConfiguration::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->orderBy('type')
                ->get(),
        ];

        return view('admin.job-configurations.index', compact('configurations', 'types', 'companies', 'stats'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties aan te maken.');
        }

        $companies = Company::orderBy('name')->get();
        $types = JobConfigurationType::active()->ordered()->get();

        return view('admin.job-configurations.create', compact('companies', 'types'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties aan te maken.');
        }

        $request->validate([
            'type' => 'required|string|max:50|in:employment_type,working_hours,status',
            'value' => 'required|string|max:100',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        // Check for duplicates
        $existing = JobConfiguration::where('type_id', $request->type_id)
            ->where('value', $request->value)
            ->where('company_id', $request->company_id ?: null)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['value' => 'Deze waarde bestaat al voor dit type en bedrijf.']);
        }

        JobConfiguration::create([
            'type_id' => $request->type_id,
            'type' => $type->name, // Keep for backward compatibility
            'value' => $request->value,
            'company_id' => $request->company_id ?: null,
        ]);

        return redirect()->route('admin.job-configurations.index')
            ->with('success', 'Job configuratie succesvol aangemaakt.');
    }

    public function show(JobConfiguration $jobConfiguration)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te bekijken.');
        }

        $jobConfiguration->load('company');

        // Check if this configuration is in use
        $inUse = false;
        $usageCount = 0;
        
        if ($jobConfiguration->type === 'employment_type') {
            $usageCount = DB::table('vacancies')
                ->where('employment_type', $jobConfiguration->value)
                ->count();
        } elseif ($jobConfiguration->type === 'working_hours') {
            $usageCount = DB::table('vacancies')
                ->where('working_hours', $jobConfiguration->value)
                ->count();
        } elseif ($jobConfiguration->type === 'status') {
            $usageCount = DB::table('vacancies')
                ->where('status', $jobConfiguration->value)
                ->count();
        }

        $inUse = $usageCount > 0;

        return view('admin.job-configurations.show', compact('jobConfiguration', 'inUse', 'usageCount'));
    }

    public function edit(JobConfiguration $jobConfiguration)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te bewerken.');
        }

        $companies = Company::orderBy('name')->get();
        $types = JobConfigurationType::active()->ordered()->get();

        return view('admin.job-configurations.edit', compact('jobConfiguration', 'companies', 'types'));
    }

    public function update(Request $request, JobConfiguration $jobConfiguration)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te bewerken.');
        }

        $request->validate([
            'type_id' => 'required|exists:job_configuration_types,id',
            'value' => 'required|string|max:100',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        
        // Get type name for backward compatibility
        $type = JobConfigurationType::findOrFail($request->type_id);

        // Check for duplicates (excluding current)
        $existing = JobConfiguration::where('type_id', $request->type_id)
            ->where('value', $request->value)
            ->where('company_id', $request->company_id ?: null)
            ->where('id', '!=', $jobConfiguration->id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['value' => 'Deze waarde bestaat al voor dit type en bedrijf.']);
        }

        // If type or value changed, update related vacancies
        $oldType = $jobConfiguration->type;
        $oldValue = $jobConfiguration->value;
        $newType = $type->name;
        $newValue = $request->value;

        $jobConfiguration->update([
            'type_id' => $request->type_id,
            'type' => $newType, // Keep for backward compatibility
            'value' => $request->value,
            'company_id' => $request->company_id ?: null,
        ]);

        // Update vacancies if type and value changed
        if ($oldType === $newType && $oldValue !== $newValue) {
            $column = match($newType) {
                'employment_type' => 'employment_type',
                'working_hours' => 'working_hours',
                'status' => 'status',
                default => null,
            };

            if ($column) {
                DB::table('vacancies')
                    ->where($column, $oldValue)
                    ->update([$column => $newValue]);
            }
        }

        return redirect()->route('admin.job-configurations.index')
            ->with('success', 'Job configuratie succesvol bijgewerkt.');
    }

    public function destroy(JobConfiguration $jobConfiguration)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te verwijderen.');
        }

        // Check if in use
        $inUse = false;
        $usageCount = 0;
        
        if ($jobConfiguration->type === 'employment_type') {
            $usageCount = DB::table('vacancies')
                ->where('employment_type', $jobConfiguration->value)
                ->count();
        } elseif ($jobConfiguration->type === 'working_hours') {
            $usageCount = DB::table('vacancies')
                ->where('working_hours', $jobConfiguration->value)
                ->count();
        } elseif ($jobConfiguration->type === 'status') {
            $usageCount = DB::table('vacancies')
                ->where('status', $jobConfiguration->value)
                ->count();
        }

        if ($usageCount > 0) {
            return redirect()->back()
                ->with('error', "Deze configuratie kan niet worden verwijderd omdat deze wordt gebruikt door {$usageCount} vacature(s).");
        }

        $jobConfiguration->delete();

        return redirect()->route('admin.job-configurations.index')
            ->with('success', 'Job configuratie succesvol verwijderd.');
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-job-configurations')) {
            abort(403, 'Je hebt geen rechten om job configuraties te verwijderen.');
        }

        $request->validate([
            'configurations' => 'required|array|min:1',
            'configurations.*' => 'integer|exists:job_configurations,id',
        ]);

        $configurationIds = $request->configurations;
        $configurations = JobConfiguration::whereIn('id', $configurationIds)->get();

        $deleted = 0;
        $skipped = 0;
        
        foreach ($configurations as $config) {
            // Check if in use
            $usageCount = 0;
            if ($config->type === 'employment_type') {
                $usageCount = DB::table('vacancies')
                    ->where('employment_type', $config->value)
                    ->count();
            } elseif ($config->type === 'working_hours') {
                $usageCount = DB::table('vacancies')
                    ->where('working_hours', $config->value)
                    ->count();
            } elseif ($config->type === 'status') {
                $usageCount = DB::table('vacancies')
                    ->where('status', $config->value)
                    ->count();
            }

            if ($usageCount > 0) {
                $skipped++;
                continue;
            }

            $config->delete();
            $deleted++;
        }

        if ($deleted > 0 && $skipped > 0) {
            return redirect()->route('admin.job-configurations.index')
                ->with('warning', "{$deleted} configuratie(s) verwijderd. {$skipped} configuratie(s) overgeslagen omdat ze in gebruik zijn.");
        } elseif ($deleted > 0) {
            return redirect()->route('admin.job-configurations.index')
                ->with('success', "{$deleted} configuratie(s) succesvol verwijderd.");
        } else {
            return redirect()->route('admin.job-configurations.index')
                ->with('error', 'Geen configuraties verwijderd. Alle geselecteerde configuraties zijn in gebruik.');
        }
    }
}

