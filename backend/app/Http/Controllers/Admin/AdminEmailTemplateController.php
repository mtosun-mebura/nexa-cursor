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
        
        // Sorting
        $sortField = $request->get('sort', 'id');
        $sortDirection = $request->get('order', 'asc');
        
        $allowedSortFields = ['id', 'name', 'type', 'company_id', 'is_active', 'created_at'];
        
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
                );
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        $emailTemplates = $query->paginate(25)->withQueryString();
        
        return view('admin.email-templates.index', compact('emailTemplates'));
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
