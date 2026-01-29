<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\EmailTemplate;
use App\Models\Company;
use App\Services\MenuService;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    use TenantFilter;

    /**
     * Email template types and the module they belong to (null = core, always visible).
     */
    protected static function emailTemplateTypesByModule(): array
    {
        return [
            'welcome' => null,
            'password_reset' => null,
            'email_verification' => null,
            'custom' => null,
            'interview' => 'skillmatching',
            'interview_invitation' => 'skillmatching',
            'interview_update' => 'skillmatching',
            'interview_confirmed' => 'skillmatching',
            'match_notification' => 'skillmatching',
            'application_received' => 'skillmatching',
            'application_status' => 'skillmatching',
            'rejection' => 'skillmatching',
        ];
    }

    /**
     * Labels for email template types.
     */
    protected static function emailTemplateTypeLabels(): array
    {
        return [
            'welcome' => 'Welkom',
            'password_reset' => 'Wachtwoord Reset',
            'email_verification' => 'E-mail Verificatie',
            'interview' => 'Interview',
            'interview_invitation' => 'Interview Uitnodiging',
            'interview_update' => 'Interview Update',
            'interview_confirmed' => 'Interview Bevestigd',
            'match_notification' => 'Match Notificatie',
            'application_received' => 'Sollicitatie Ontvangen',
            'application_status' => 'Sollicitatie Status',
            'rejection' => 'Afwijzing',
            'custom' => 'Aangepast',
        ];
    }

    /**
     * Get list of email template types that are allowed (core + types from active modules).
     */
    protected function getAllowedEmailTemplateTypes(MenuService $menuService): array
    {
        $typesByModule = static::emailTemplateTypesByModule();
        $activeModuleKeys = [];
        $grouped = $menuService->getModulePermissionsGrouped();
        foreach ($grouped as $moduleData) {
            if (!empty($moduleData['module'])) {
                $activeModuleKeys[] = $moduleData['module'];
            }
        }
        $allowed = [];
        foreach ($typesByModule as $type => $module) {
            if ($module === null || in_array($module, $activeModuleKeys, true)) {
                $allowed[] = $type;
            }
        }
        return $allowed;
    }
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bekijken.');
        }
        
        $query = EmailTemplate::with('company');
        
        // Apply tenant filtering
        $query = $this->applyTenantFilter($query);
        
        // Filter op type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter op status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter op bedrijf (alleen voor super-admin)
        if ($request->filled('company') && auth()->user()->hasRole('super-admin')) {
            $query->where('company_id', $request->company);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function($companyQuery) use ($search) {
                      $companyQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sorting
        $sortField = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'asc');
        
        $allowedSortFields = ['id', 'name', 'type', 'company_id', 'is_active', 'created_at'];
        
        // Set default direction based on sort field
        if (!$sortDirection || !in_array($sortDirection, ['asc', 'desc'])) {
            if (in_array($sortField, ['created_at'])) {
                $sortDirection = 'desc';
            } else {
                $sortDirection = 'asc';
            }
        }
        
        if (in_array($sortField, $allowedSortFields)) {
            if ($sortField === 'company_id') {
                $query->join('companies', 'email_templates.company_id', '=', 'companies.id')
                      ->orderBy('companies.name', $sortDirection)
                      ->select('email_templates.*');
            } elseif ($sortField === 'is_active') {
                $query->orderByRaw("
                    CASE
                        WHEN is_active = false THEN 1
                        WHEN is_active = true THEN 2
                    END " . $sortDirection
                )->orderBy('id', 'asc');
            } else {
                $query->orderBy($sortField, $sortDirection)->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        // Load all email templates for client-side pagination
        $emailTemplates = $query->get();
        
        // Calculate statistics
        $statsQuery = EmailTemplate::query();
        $statsQuery = $this->applyTenantFilter($statsQuery);
        
        $stats = [
            'total_templates' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'unique_types' => (clone $statsQuery)->distinct('type')->count('type'),
        ];
        
        // Get companies for filter (only for super-admin)
        $companies = auth()->user()->hasRole('super-admin') ? Company::orderBy('name')->get() : collect();
        
        return view('admin.email-templates.index', compact('emailTemplates', 'stats', 'companies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates aan te maken.');
        }
        
        $query = Company::query();
        $query = $this->applyTenantFilter($query);
        $companies = $query->get();
        
        // Template variabelen voor de view
        $templateVariables = [
            'USER_NAME' => 'Gebruikersnaam',
            'USER_EMAIL' => 'E-mailadres',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'NOTIFICATION_TITLE' => 'Notificatie titel',
            'NOTIFICATION_MESSAGE' => 'Notificatie bericht',
            'ACTION_URL' => 'Actie URL',
            'VACANCY_TITLE' => 'Vacature titel',
            'MATCH_SCORE' => 'Match score',
            'INTERVIEW_DATE' => 'Interview datum',
            'RESET_LINK' => 'Wachtwoord reset link',
            'VERIFICATION_LINK' => 'E-mail verificatie link'
        ];
        
        // Default HTML template
        $defaultHtmlTemplate = '<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ NOTIFICATION_TITLE }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 30px; background-color: #2563eb; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">{{ NOTIFICATION_TITLE }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Beste {{ USER_NAME }},
                            </p>
                            <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                {{ NOTIFICATION_MESSAGE }}
                            </p>
                            <p style="margin: 20px 0;">
                                <a href="{{ ACTION_URL }}" style="display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Bekijk Details</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">
                                Met vriendelijke groet,<br>
                                {{ COMPANY_NAME }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
        
        $menuService = app(MenuService::class);
        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);
        $typeLabels = static::emailTemplateTypeLabels();

        return view('admin.email-templates.create', compact('companies', 'templateVariables', 'defaultHtmlTemplate', 'allowedTypes', 'typeLabels'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates aan te maken.');
        }

        $menuService = app(MenuService::class);
        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);

        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'type' => ['required', 'string', 'max:50', 'in:' . implode(',', $allowedTypes)],
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $emailTemplateData = $request->all();
        
        // Als Super Admin: gebruik company_id uit formulier (kan null zijn voor algemeen)
        if (auth()->user()->hasRole('super-admin')) {
            $emailTemplateData['company_id'] = $request->filled('company_id') ? $request->company_id : null;
        } else {
            // Als geen Super Admin: gebruik altijd de user's company
            $emailTemplateData['company_id'] = auth()->user()->company_id;
        }

        EmailTemplate::create($emailTemplateData);
        return redirect()->route('admin.email-templates.index')->with('success', 'E-mail template succesvol aangemaakt.');
    }

    public function show(EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }
        
        // Template variabelen voor de view
        $templateVariables = [
            'USER_NAME' => 'Gebruikersnaam',
            'USER_EMAIL' => 'E-mailadres',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'NOTIFICATION_TITLE' => 'Notificatie titel',
            'NOTIFICATION_MESSAGE' => 'Notificatie bericht',
            'ACTION_URL' => 'Actie URL',
            'VACANCY_TITLE' => 'Vacature titel',
            'MATCH_SCORE' => 'Match score',
            'INTERVIEW_DATE' => 'Interview datum',
            'RESET_LINK' => 'Wachtwoord reset link',
            'VERIFICATION_LINK' => 'E-mail verificatie link'
        ];
        
        return view('admin.email-templates.show', compact('emailTemplate', 'templateVariables'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }
        
        $query = Company::query();
        $query = $this->applyTenantFilter($query);
        $companies = $query->get();
        
        // Template variabelen voor de view
        $templateVariables = [
            'USER_NAME' => 'Gebruikersnaam',
            'USER_EMAIL' => 'E-mailadres',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'NOTIFICATION_TITLE' => 'Notificatie titel',
            'NOTIFICATION_MESSAGE' => 'Notificatie bericht',
            'ACTION_URL' => 'Actie URL',
            'VACANCY_TITLE' => 'Vacature titel',
            'MATCH_SCORE' => 'Match score',
            'INTERVIEW_DATE' => 'Interview datum',
            'RESET_LINK' => 'Wachtwoord reset link',
            'VERIFICATION_LINK' => 'E-mail verificatie link'
        ];
        
        $menuService = app(MenuService::class);
        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);
        // Ensure current template type is in the list when editing (e.g. module was disabled later)
        if (!in_array($emailTemplate->type, $allowedTypes, true)) {
            $allowedTypes[] = $emailTemplate->type;
        }
        $typeLabels = static::emailTemplateTypeLabels();

        return view('admin.email-templates.edit', compact('emailTemplate', 'companies', 'templateVariables', 'allowedTypes', 'typeLabels'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }

        $menuService = app(MenuService::class);
        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);
        // When updating, allow keeping the current type even if its module is now disabled
        if (!in_array($emailTemplate->type, $allowedTypes, true)) {
            $allowedTypes[] = $emailTemplate->type;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'type' => ['required', 'string', 'max:50', 'in:' . implode(',', $allowedTypes)],
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $emailTemplateData = $request->all();
        
        // Als Super Admin: gebruik company_id uit formulier (kan null zijn voor algemeen)
        if (auth()->user()->hasRole('super-admin')) {
            $emailTemplateData['company_id'] = $request->filled('company_id') ? $request->company_id : null;
        } else {
            // Als geen Super Admin: gebruik altijd de user's company
            $emailTemplateData['company_id'] = auth()->user()->company_id;
        }

        $emailTemplate->update($emailTemplateData);
        return redirect()->route('admin.email-templates.show', $emailTemplate)->with('success', 'E-mail template succesvol bijgewerkt.');
    }

    public function toggleStatus(EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-email-templates')) {
            if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Je hebt geen rechten om e-mail templates te bewerken.'], 403);
            }
            abort(403, 'Je hebt geen rechten om e-mail templates te bewerken.');
        }

        if (!$this->canAccessResource($emailTemplate)) {
            if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Je hebt geen toegang tot deze e-mail template.'], 403);
            }
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }

        $emailTemplate->update(['is_active' => !$emailTemplate->is_active]);

        if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'is_active' => $emailTemplate->fresh()->is_active]);
        }
        return redirect()->back()->with('success', 'Status bijgewerkt.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }
        
        $emailTemplate->delete();
        return redirect()->route('admin.email-templates.index')->with('success', 'E-mail template succesvol verwijderd.');
    }
}
