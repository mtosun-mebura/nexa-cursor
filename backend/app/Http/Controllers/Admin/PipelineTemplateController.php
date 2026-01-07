<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PipelineTemplate;
use App\Models\StageType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PipelineTemplateController extends Controller
{
    /**
     * Display pipeline templates for a company
     */
    public function index(Company $company)
    {
        $templates = PipelineTemplate::where('company_id', $company->id)
            ->orWhere('company_id', null)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.companies.pipeline-templates.index', compact('company', 'templates'));
    }

    /**
     * Show the form for editing a pipeline template
     */
    public function edit(Company $company, PipelineTemplate $pipelineTemplate)
    {
        $stageTypes = StageType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.companies.pipeline-templates.edit', compact('company', 'pipelineTemplate', 'stageTypes'));
    }

    /**
     * Update a pipeline template
     */
    public function update(Request $request, Company $company, PipelineTemplate $pipelineTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stages' => 'required|array',
            'stages.*.id' => 'required|string',
            'stages.*.stageType' => 'required|string|exists:stage_types,key',
            'stages.*.label' => 'required|string|max:255',
            'stages.*.sequence' => 'required|integer|min:0',
            'stages.*.optional' => 'nullable|boolean',
            'terminal_stages' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Process terminal stages
        $terminalStages = [];
        if (!empty($validated['terminal_stages'])) {
            $terminalStages = array_map('trim', explode(',', $validated['terminal_stages']));
            $terminalStages = array_filter($terminalStages);
        }
        if (empty($terminalStages)) {
            $terminalStages = ['REJECTION', 'WITHDRAWN'];
        }

        // Process stages - convert to indexed array and handle optional field
        $stages = [];
        foreach ($validated['stages'] as $stage) {
            $stages[] = [
                'id' => $stage['id'],
                'stageType' => $stage['stageType'],
                'label' => $stage['label'],
                'sequence' => (int)$stage['sequence'],
                'optional' => isset($stage['optional']) && $stage['optional'] === 'on' || $stage['optional'] === true || $stage['optional'] === '1',
            ];
        }

        // Sort stages by sequence
        usort($stages, function($a, $b) {
            return $a['sequence'] <=> $b['sequence'];
        });

        // If this is the default template, create a copy for the company
        if ($pipelineTemplate->company_id === null) {
            $pipelineTemplate = PipelineTemplate::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'key' => 'custom_' . $company->id . '_' . time(),
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
                'stages' => $stages,
                'terminal_stages' => $terminalStages,
                'description' => $validated['description'] ?? null,
            ]);
        } else {
            $pipelineTemplate->update([
                'name' => $validated['name'],
                'stages' => $stages,
                'terminal_stages' => $terminalStages,
                'description' => $validated['description'] ?? null,
            ]);
        }

        return redirect()->route('admin.companies.pipeline-templates.index', $company)
            ->with('success', 'Pipeline template bijgewerkt.');
    }

    /**
     * Create a new pipeline template from default
     */
    public function createFromDefault(Company $company)
    {
        $defaultTemplate = PipelineTemplate::where('key', 'default_general')
            ->where('company_id', null)
            ->first();

        if (!$defaultTemplate) {
            return redirect()->route('admin.companies.pipeline-templates.index', $company)
                ->with('error', 'Standaard template niet gevonden.');
        }

        $pipelineTemplate = PipelineTemplate::create([
            'company_id' => $company->id,
            'name' => $defaultTemplate->name . ' (Kopie)',
            'key' => 'custom_' . $company->id . '_' . time(),
            'version' => 1,
            'is_default' => false,
            'is_active' => true,
            'stages' => $defaultTemplate->stages,
            'terminal_stages' => $defaultTemplate->terminal_stages,
            'description' => $defaultTemplate->description,
        ]);

        return redirect()->route('admin.companies.pipeline-templates.edit', [$company, $pipelineTemplate])
            ->with('success', 'Template gekopieerd. Pas deze aan naar wens.');
    }
}
