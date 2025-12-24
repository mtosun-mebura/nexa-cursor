<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\EmailTemplate;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    use TenantFilter;
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
            'name' => 'Gebruikersnaam',
            'email' => 'E-mailadres',
            'company_name' => 'Bedrijfsnaam',
            'vacancy_title' => 'Vacature titel',
            'match_score' => 'Match score',
            'interview_date' => 'Interview datum',
            'reset_link' => 'Wachtwoord reset link',
            'verification_link' => 'E-mail verificatie link'
        ];
        
        return view('admin.email-templates.create', compact('companies', 'templateVariables'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplateData = $request->all();
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $emailTemplateData['company_id'] = session('selected_tenant');
        } else {
            // Als geen tenant geselecteerd, gebruik null (voor Super Admin) of huidige user's company
            $emailTemplateData['company_id'] = auth()->user()->hasRole('super-admin') ? null : auth()->user()->company_id;
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
            'name' => 'Gebruikersnaam',
            'email' => 'E-mailadres',
            'company_name' => 'Bedrijfsnaam',
            'vacancy_title' => 'Vacature titel',
            'match_score' => 'Match score',
            'interview_date' => 'Interview datum',
            'reset_link' => 'Wachtwoord reset link',
            'verification_link' => 'E-mail verificatie link'
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
            'name' => 'Gebruikersnaam',
            'email' => 'E-mailadres',
            'company_name' => 'Bedrijfsnaam',
            'vacancy_title' => 'Vacature titel',
            'match_score' => 'Match score',
            'interview_date' => 'Interview datum',
            'reset_link' => 'Wachtwoord reset link',
            'verification_link' => 'E-mail verificatie link'
        ];
        
        return view('admin.email-templates.edit', compact('emailTemplate', 'companies', 'templateVariables'));
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
        
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplateData = $request->all();
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $emailTemplateData['company_id'] = session('selected_tenant');
        } else {
            // Als geen tenant geselecteerd, gebruik null (voor Super Admin) of huidige user's company
            $emailTemplateData['company_id'] = auth()->user()->hasRole('super-admin') ? null : auth()->user()->company_id;
        }

        $emailTemplate->update($emailTemplateData);
        return redirect()->route('admin.email-templates.index')->with('success', 'E-mail template succesvol bijgewerkt.');
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
