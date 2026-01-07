<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CompanyLocation;
use App\Models\JobMatch;
use App\Models\PipelineTemplate;
use App\Models\StageInstance;
use App\Models\StageType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StageInstanceController extends Controller
{
    /**
     * Initialize pipeline for an application or match
     */
    public function initialize(Request $request, $type, $id)
    {
        $validated = $request->validate([
            'pipeline_template_id' => 'required|exists:pipeline_templates,id',
        ]);

        $pipelineTemplate = PipelineTemplate::findOrFail($validated['pipeline_template_id']);

        if ($type === 'application') {
            $application = Application::findOrFail($id);
            $this->createStagesForApplication($application, $pipelineTemplate);
            return redirect()->back()->with('success', 'Pipeline geïnitialiseerd.');
        } elseif ($type === 'match') {
            $match = JobMatch::findOrFail($id);
            $this->createStagesForMatch($match, $pipelineTemplate);
            return redirect()->back()->with('success', 'Pipeline geïnitialiseerd.');
        }

        return redirect()->back()->with('error', 'Ongeldig type.');
    }

    /**
     * Create stages for an application
     */
    private function createStagesForApplication(Application $application, PipelineTemplate $template)
    {
        foreach ($template->stages as $stageDef) {
            StageInstance::create([
                'application_id' => $application->id,
                'pipeline_template_id' => $template->id,
                'stage_type_key' => $stageDef['stageType'],
                'label' => $stageDef['label'],
                'sequence' => $stageDef['sequence'],
                'status' => 'PENDING',
            ]);
        }
    }

    /**
     * Create stages for a match
     */
    private function createStagesForMatch(JobMatch $match, PipelineTemplate $template)
    {
        foreach ($template->stages as $stageDef) {
            StageInstance::create([
                'match_id' => $match->id,
                'pipeline_template_id' => $template->id,
                'stage_type_key' => $stageDef['stageType'],
                'label' => $stageDef['label'],
                'sequence' => $stageDef['sequence'],
                'status' => 'PENDING',
            ]);
        }
    }

    /**
     * Get a stage instance (for AJAX)
     */
    public function show(StageInstance $stageInstance)
    {
        return response()->json([
            'stage' => [
                'id' => $stageInstance->id,
                'status' => $stageInstance->status,
                'outcome' => $stageInstance->outcome,
                'scheduled_at' => $stageInstance->scheduled_at ? $stageInstance->scheduled_at->toISOString() : null,
                'notes' => $stageInstance->notes,
                'artifacts' => $stageInstance->artifacts ?? [],
            ]
        ]);
    }

    /**
     * Update a stage instance
     */
    public function update(Request $request, StageInstance $stageInstance)
    {
        // Load relationships needed for location formatting
        $stageInstance->load([
            'match.vacancy.company.mainLocation',
            'application.vacancy.company.mainLocation'
        ]);
        
        $validated = $request->validate([
            'status' => 'sometimes|in:PENDING,SCHEDULED,IN_PROGRESS,COMPLETED,SKIPPED,CANCELED',
            'outcome' => 'nullable|string',
            'scheduled_at' => 'nullable|date_format:Y-m-d',
            'scheduled_time' => ['nullable','regex:/^[0-2][0-9]:[0-5][0-9]$/'],
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'type' => 'nullable|string',
            'duration' => 'nullable|integer|min:0|max:480',
            'location_type' => 'nullable|string',
            'location_custom' => 'nullable|string',
            'company_location_id' => 'nullable|integer|exists:company_locations,id',
            'interviewer_id' => 'nullable|string',
            'interviewer_name' => 'nullable|string|max:255',
            'interviewer_email' => 'nullable|email',
            'user_id' => 'nullable|integer',
        ]);

        $validated['updated_by'] = Auth::id();

        // Auto-set timestamps based on status
        if (isset($validated['status'])) {
            if ($validated['status'] === 'SCHEDULED' && !$stageInstance->scheduled_at && empty($validated['scheduled_at']) && empty($request->input('scheduled_time'))) {
                $validated['scheduled_at'] = now();
            }
            if ($validated['status'] === 'IN_PROGRESS' && !$stageInstance->started_at) {
                $validated['started_at'] = now();
            }
            if ($validated['status'] === 'COMPLETED' && !$stageInstance->completed_at) {
                $validated['completed_at'] = now();
            }
        }

        // Combine date and time into scheduled_at
        // Always read from request first, as validated might not contain it if validation failed
        $dateInput = $request->input('scheduled_at');
        $timeInput = $request->input('scheduled_time');
        
        // If date is in validated (passed validation), use that, otherwise use raw input
        if (isset($validated['scheduled_at']) && !empty($validated['scheduled_at'])) {
            $dateInput = $validated['scheduled_at'];
        }
        
        if (!empty($dateInput) || !empty($timeInput)) {
            // If we have a date, combine it with time (always prioritize the provided date)
            if (!empty($dateInput)) {
                $validated['scheduled_at'] = $this->buildScheduledAt($dateInput, $timeInput);
            } 
            // If we only have time, use existing scheduled_at date or today's date
            elseif (!empty($timeInput) && $stageInstance->scheduled_at) {
                $existingDate = $stageInstance->scheduled_at;
                $validated['scheduled_at'] = $this->buildScheduledAt($existingDate->format('Y-m-d'), $timeInput);
            }
            // If we only have time and no existing date, use today
            elseif (!empty($timeInput)) {
                $validated['scheduled_at'] = $this->buildScheduledAt(now()->format('Y-m-d'), $timeInput);
            }
        }

        $artifacts = $stageInstance->artifacts ?? [];
        $artifacts['type'] = $request->input('type');
        $artifacts['duration'] = $request->input('duration');
        $artifacts['location_type'] = $request->input('location_type');
        $artifacts['location_custom'] = $request->input('location_custom');
        $artifacts['company_location_id'] = $request->input('company_location_id');
        $artifacts['location'] = $this->formatStageLocation($request, $stageInstance);
        $artifacts['scheduled_time'] = $request->input('scheduled_time');
        $artifacts['interviewer_id'] = $request->input('interviewer_id');
        $artifacts['interviewer_name'] = $request->input('interviewer_name');
        $artifacts['interviewer_email'] = $request->input('interviewer_email');
        $artifacts['user_id'] = $request->input('user_id');

        if ($this->stageHasColumn('type')) {
            $validated['type'] = $request->input('type');
        }
        if ($this->stageHasColumn('duration')) {
            $validated['duration'] = $request->input('duration');
        }
        if ($this->stageHasColumn('location_type')) {
            $validated['location_type'] = $request->input('location_type');
        }
        if ($this->stageHasColumn('company_location_id')) {
            $validated['company_location_id'] = $request->input('company_location_id');
        }
        if ($this->stageHasColumn('location')) {
            $validated['location'] = $this->formatStageLocation($request, $stageInstance);
        }
        if ($this->stageHasColumn('scheduled_time')) {
            $validated['scheduled_time'] = $request->input('scheduled_time');
        }
        if ($this->stageHasColumn('interviewer_id')) {
            $validated['interviewer_id'] = $request->input('interviewer_id');
        }
        if ($this->stageHasColumn('interviewer_name')) {
            $validated['interviewer_name'] = $request->input('interviewer_name');
        }
        if ($this->stageHasColumn('interviewer_email')) {
            $validated['interviewer_email'] = $request->input('interviewer_email');
        }
        if ($this->stageHasColumn('user_id')) {
            $validated['user_id'] = $request->input('user_id');
        }
        $validated['artifacts'] = array_filter($artifacts, function ($value) {
            return $value !== null && $value !== '';
        });

        // Only keep columns that exist to avoid SQL errors when migration not yet run
        $always = ['status','outcome','scheduled_at','started_at','completed_at','notes','artifacts','updated_by'];
        $validated = collect($validated)
            ->filter(function ($value, $key) use ($always) {
                return in_array($key, $always) || Schema::hasColumn('stage_instances', $key);
            })
            ->toArray();

        $stageInstance->update($validated);

        // If completed, move to next stage
        if (isset($validated['status']) && $validated['status'] === 'COMPLETED') {
            if (isset($validated['outcome']) && $validated['outcome'] === 'PASS') {
                $this->moveToNextStage($stageInstance);
            } elseif (isset($validated['outcome']) && $validated['outcome'] === 'FAIL') {
                $this->rejectApplicationOrMatch($stageInstance);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Stage bijgewerkt.']);
        }

        return redirect()->back()->with('success', 'Stage bijgewerkt.');
    }

    /**
     * Move to next stage in pipeline
     */
    private function moveToNextStage(StageInstance $currentStage)
    {
        $nextStage = StageInstance::where(function($query) use ($currentStage) {
            if ($currentStage->application_id) {
                $query->where('application_id', $currentStage->application_id);
            } else {
                $query->where('match_id', $currentStage->match_id);
            }
        })
        ->where('sequence', '>', $currentStage->sequence)
        ->where('status', 'PENDING')
        ->orderBy('sequence')
        ->first();

        if ($nextStage) {
            $nextStage->update(['status' => 'SCHEDULED']);
        }
    }

    /**
     * Reject application or match
     */
    private function rejectApplicationOrMatch(StageInstance $stage)
    {
        $rejectionStage = StageInstance::where(function($query) use ($stage) {
            if ($stage->application_id) {
                $query->where('application_id', $stage->application_id);
            } else {
                $query->where('match_id', $stage->match_id);
            }
        })
        ->where('stage_type_key', 'REJECTION')
        ->first();

        if ($rejectionStage) {
            $rejectionStage->update(['status' => 'COMPLETED']);
        }
    }

    private function buildScheduledAt(?string $dateInput, ?string $timeInput): ?Carbon
    {
        if (empty($dateInput)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput);
            if (!empty($timeInput) && preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $timeInput)) {
                [$hours, $minutes] = explode(':', $timeInput);
                $date->setTime((int)$hours, (int)$minutes);
            } else {
                $date->startOfDay();
            }
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatStageLocation(Request $request, ?StageInstance $stageInstance = null): ?string
    {
        if (!$request->filled('location_type')) {
            return null;
        }

        if ($request->location_type === 'online') {
            return 'Online / Digitaal';
        }

        if ($request->location_type === 'other') {
            return $request->location_custom;
        }

        if ($request->location_type === 'company_main') {
            // Get company from stage instance
            $company = null;
            if ($stageInstance) {
                if ($stageInstance->match) {
                    $company = $stageInstance->match->vacancy->company ?? null;
                } elseif ($stageInstance->application) {
                    $company = $stageInstance->application->vacancy->company ?? null;
                }
            }
            
            if ($company) {
                // Check if company has a mainLocation
                if ($company->mainLocation) {
                    $mainLoc = $company->mainLocation;
                    $address = trim(($mainLoc->street ?? '') . ' ' . ($mainLoc->house_number ?? '') . ($mainLoc->house_number_extension ? '-' . $mainLoc->house_number_extension : ''));
                    $address = trim($address . ' ' . ($mainLoc->postal_code ?? '') . ' ' . ($mainLoc->city ?? ''));
                    return $mainLoc->name . ($address ? ' - ' . $address : '');
                } else {
                    // Use company address fields
                    $address = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                    $address = trim($address . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                    return $company->name . ($address ? ' - ' . $address : '');
                }
            }
            return null;
        }

        if (is_numeric($request->location_type)) {
            $location = \App\Models\CompanyLocation::find($request->location_type);
            if ($location) {
                $address = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                $address = trim($address . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                return $location->name . ($address ? ' - ' . $address : '');
            }
        }

        return null;
    }

    private function stageHasColumn(string $column): bool
    {
        return Schema::hasColumn('stage_instances', $column);
    }
}
