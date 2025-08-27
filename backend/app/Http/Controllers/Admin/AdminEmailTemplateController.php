<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    public function index()
    {
        $emailTemplates = EmailTemplate::with('company')->paginate(10);
        return view('admin.email-templates.index', compact('emailTemplates'));
    }

    public function create()
    {
        $companies = Company::all();
        
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
        $companies = Company::all();
        
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
        $emailTemplate->delete();
        return redirect()->route('admin.email-templates.index')->with('success', 'E-mail template succesvol verwijderd.');
    }
}
