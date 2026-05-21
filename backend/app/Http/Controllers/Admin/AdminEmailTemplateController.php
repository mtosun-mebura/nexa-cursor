<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\EmailTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\MenuService;
use App\Models\InfoRequestFormField;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    use TenantFilter;

    /**
     * Variabelenaam voor in de e-mailtemplate = slug in HOOFDLETTERS met underscores.
     * Gebruik in template: {{ VOORNAAM }}, {{ TELEFOONNUMMER }} etc. (slug = voornaam, telefoonnummer).
     */
    private static function fieldNameToVariableKey(string $name): string
    {
        return strtoupper(str_replace('-', '_', $name));
    }

    /**
     * Bouw infoRequestVariables uit Formulier velden (voor variabelenlijst en testmail).
     */
    private static function buildInfoRequestVariablesFromFormFields(\Illuminate\Support\Collection $formFields): array
    {
        $vars = [];
        foreach ($formFields as $field) {
            $vars[static::fieldNameToVariableKey($field->name)] = $field->label;
        }
        $vars['DATUM_AANVRAAG'] = 'Datum/tijd aanvraag';
        return $vars;
    }

    /**
     * Validatieregels voor de testformuliervelden (test_*), afgeleid van Formulier velden.
     * Alleen bij type informatieaanvraag; anders lege array.
     */
    private static function getTestFormValidationRules(?string $type, string $context = 'send-test'): array
    {
        if ($type !== 'informatieaanvraag') {
            return [];
        }
        try {
            $formFields = InfoRequestFormField::ordered()->get();
        } catch (\Throwable $e) {
            return [];
        }
        $rules = [];
        foreach ($formFields as $field) {
            $rules['test_' . $field->name] = $field->getValidationRules();
        }
        return $rules;
    }

    /**
     * Email template types and the module they belong to (null = core, always visible).
     */
    protected static function emailTemplateTypesByModule(): array
    {
        return [
            'welcome' => null,
            'password_reset' => null,
            'email_verification' => null,
            'informatieaanvraag' => null,
            'custom' => null,
            'interview' => 'skillmatching',
            'interview_invitation' => 'skillmatching',
            'interview_update' => 'skillmatching',
            'interview_confirmed' => 'skillmatching',
            'match_notification' => 'skillmatching',
            'application_received' => 'skillmatching',
            'application_status' => 'skillmatching',
            'rejection' => 'skillmatching',
            'invoice' => 'taxi',
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
            'informatieaanvraag' => 'Informatieaanvraag',
            'interview' => 'Interview',
            'interview_invitation' => 'Interview Uitnodiging',
            'interview_update' => 'Interview Update',
            'interview_confirmed' => 'Interview Bevestigd',
            'match_notification' => 'Match Notificatie',
            'application_received' => 'Sollicitatie Ontvangen',
            'application_status' => 'Sollicitatie Status',
            'rejection' => 'Afwijzing',
            'custom' => 'Aangepast',
            'invoice' => 'Factuur',
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
            'VERIFICATION_LINK' => 'E-mail verificatie link',
            'VOORNAAM' => 'Voornaam (contact)',
            'ACHTERNAAM' => 'Achternaam (contact)',
            'TELEFOONNUMMER' => 'Telefoonnummer',
            'OMSCHRIJVING' => 'Omschrijving (vrije tekst)',
            'DATUM_AANVRAAG' => 'Datum/tijd van de aanvraag',
            'EMAIL_AANVRAAG' => 'E-mailadres van de aanvrager',
            'CUSTOMER_NAME' => 'Klantnaam (factuur)',
            'CUSTOMER_EMAIL' => 'Klant e-mail (factuur)',
            'INVOICE_NUMBER' => 'Factuurnummer',
            'INVOICE_DATE' => 'Factuurdatum',
            'INVOICE_AMOUNT_EXCL' => 'Bedrag excl. BTW',
            'INVOICE_TAX_LABEL' => 'BTW-regel (bijv. BTW (21%))',
            'INVOICE_TAX_AMOUNT' => 'BTW-bedrag',
            'INVOICE_TAX_RATE' => 'BTW-percentage',
            'INVOICE_TOTAL' => 'Totaalbedrag',
            'INVOICE_AMOUNTS_HTML' => 'Bedragenblok (HTML-tabel)',
            'INVOICE_AMOUNTS_TEXT' => 'Bedragenblok (platte tekst)',
            'COMPANY_ADDRESS' => 'Bedrijfsadres',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
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
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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

        // Standaard template voor type Informatieaanvraag (met VOORNAAM, ACHTERNAAM, TELEFOONNUMMER, OMSCHRIJVING, DATUM_AANVRAAG, EMAIL_AANVRAAG)
        // Styling gelijk aan admin-preview: witte kaart 600px, blauwe header, witte content met donkere tekst.
        $defaultHtmlTemplateInformatieaanvraag = '<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Informatieaanvraag ontvangen</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 30px; background-color: #2563eb; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">Nieuwe informatieaanvraag</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px; background-color: #ffffff; color: #333333;">
                            <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Er is een informatieaanvraag binnengekomen met de volgende gegevens:
                            </p>
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 15px; color: #333333; background-color: #ffffff; text-align: left;">
{{ DYNAMIC_FORM_FIELDS }}
                            </table>
                            <p style="margin: 0 0 8px 0; color: #333333; font-size: 16px; font-weight: bold;">Omschrijving / vraag:</p>
                            <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 1.6; white-space: pre-wrap;">{{ OMSCHRIJVING }}</p>
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

        $tenantId = $this->getTenantId();
        $users = $tenantId
            ? User::where('company_id', $tenantId)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email'])
            : collect();

        try {
            $formFields = InfoRequestFormField::ordered()->get();
        } catch (\Throwable $e) {
            $formFields = collect();
        }
        $infoRequestVariables = static::buildInfoRequestVariablesFromFormFields($formFields);

        return view('admin.email-templates.create', compact('companies', 'templateVariables', 'infoRequestVariables', 'formFields', 'defaultHtmlTemplate', 'defaultHtmlTemplateInformatieaanvraag', 'allowedTypes', 'typeLabels', 'users'));
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
            'recipient_type' => 'nullable|in:user,email',
            'recipient_user_id' => 'nullable|exists:users,id',
            'recipient_email' => 'nullable|email',
            'test_send' => 'nullable|boolean',
        ] + static::getTestFormValidationRules($request->input('type'), 'store'));

        $emailTemplateData = $request->only([
            'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active',
            'recipient_type', 'recipient_user_id', 'recipient_email',
        ]);
        if ($request->filled('recipient_type') && $request->recipient_type === 'email') {
            $emailTemplateData['recipient_user_id'] = null;
        }
        if ($request->filled('recipient_type') && $request->recipient_type === 'user') {
            $emailTemplateData['recipient_email'] = null;
        }
        if (!$request->filled('recipient_type')) {
            $emailTemplateData['recipient_type'] = null;
            $emailTemplateData['recipient_user_id'] = null;
            $emailTemplateData['recipient_email'] = null;
        }
        if (auth()->user()->hasRole('super-admin')) {
            $emailTemplateData['company_id'] = $request->filled('company_id') ? $request->company_id : null;
        } else {
            $emailTemplateData['company_id'] = auth()->user()->company_id;
        }

        $emailTemplate = EmailTemplate::create($emailTemplateData);

        if ($request->boolean('test_send') && $this->canAccessResource($emailTemplate)) {
            $toEmail = $emailTemplate->getRecipientEmailAddress();
            if ($toEmail) {
                $variables = ['DATUM_AANVRAAG' => now()->format('d-m-Y H:i')];
                $formFields = $emailTemplate->type === 'informatieaanvraag'
                    ? $emailTemplate->getOrderedFormFields()
                    : collect();
                foreach ($formFields as $field) {
                    $variables[static::fieldNameToVariableKey($field->name)] = $request->input('test_' . $field->name, '');
                }
                $toName = $toEmail;
                if ($formFields->count() >= 2) {
                    $toName = trim($request->input('test_' . $formFields->get(0)->name, '') . ' ' . $request->input('test_' . $formFields->get(1)->name, ''));
                } elseif ($formFields->isNotEmpty()) {
                    $toName = trim((string) $request->input('test_' . $formFields->first()->name, '')) ?: $toEmail;
                }
                app(EmailTemplateService::class)->sendTestEmail($emailTemplate, $toEmail, $toName, $variables);
                return redirect()->route('admin.email-templates.index')->with('success', 'E-mail template aangemaakt en testmail verstuurd naar ' . $toEmail);
            }
        }

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
            'VERIFICATION_LINK' => 'E-mail verificatie link',
            'VOORNAAM' => 'Voornaam (contact)',
            'ACHTERNAAM' => 'Achternaam (contact)',
            'TELEFOONNUMMER' => 'Telefoonnummer',
            'OMSCHRIJVING' => 'Omschrijving (vrije tekst)',
            'DATUM_AANVRAAG' => 'Datum/tijd van de aanvraag',
            'EMAIL_AANVRAAG' => 'E-mailadres van de aanvrager',
            'CUSTOMER_NAME' => 'Klantnaam (factuur)',
            'CUSTOMER_EMAIL' => 'Klant e-mail (factuur)',
            'INVOICE_NUMBER' => 'Factuurnummer',
            'INVOICE_DATE' => 'Factuurdatum',
            'INVOICE_AMOUNT_EXCL' => 'Bedrag excl. BTW',
            'INVOICE_TAX_LABEL' => 'BTW-regel (bijv. BTW (21%))',
            'INVOICE_TAX_AMOUNT' => 'BTW-bedrag',
            'INVOICE_TAX_RATE' => 'BTW-percentage',
            'INVOICE_TOTAL' => 'Totaalbedrag',
            'INVOICE_AMOUNTS_HTML' => 'Bedragenblok (HTML-tabel)',
            'INVOICE_AMOUNTS_TEXT' => 'Bedragenblok (platte tekst)',
            'COMPANY_ADDRESS' => 'Bedrijfsadres',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
        ];

        $tenantId = $this->getTenantId();
        $users = $tenantId
            ? User::where('company_id', $tenantId)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email'])
            : collect();

        $isInfoRequestType = ($emailTemplate->type === 'informatieaanvraag');
        $formFields = $isInfoRequestType ? $emailTemplate->getOrderedFormFields() : collect();
        $previewHtml = $emailTemplate->html_content ?? '';
        if ($isInfoRequestType && $previewHtml !== '') {
            $previewHtml = str_replace('{{ DYNAMIC_FORM_FIELDS }}', $emailTemplate->renderDynamicFormFieldsHtml(), $previewHtml);
        }
        return view('admin.email-templates.show', compact('emailTemplate', 'templateVariables', 'users', 'formFields', 'previewHtml'));
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
            'VERIFICATION_LINK' => 'E-mail verificatie link',
            'VOORNAAM' => 'Voornaam (contact)',
            'ACHTERNAAM' => 'Achternaam (contact)',
            'TELEFOONNUMMER' => 'Telefoonnummer',
            'OMSCHRIJVING' => 'Omschrijving (vrije tekst)',
            'DATUM_AANVRAAG' => 'Datum/tijd van de aanvraag',
            'EMAIL_AANVRAAG' => 'E-mailadres van de aanvrager',
            'CUSTOMER_NAME' => 'Klantnaam (factuur)',
            'CUSTOMER_EMAIL' => 'Klant e-mail (factuur)',
            'INVOICE_NUMBER' => 'Factuurnummer',
            'INVOICE_DATE' => 'Factuurdatum',
            'INVOICE_AMOUNT_EXCL' => 'Bedrag excl. BTW',
            'INVOICE_TAX_LABEL' => 'BTW-regel (bijv. BTW (21%))',
            'INVOICE_TAX_AMOUNT' => 'BTW-bedrag',
            'INVOICE_TAX_RATE' => 'BTW-percentage',
            'INVOICE_TOTAL' => 'Totaalbedrag',
            'INVOICE_AMOUNTS_HTML' => 'Bedragenblok (HTML-tabel)',
            'INVOICE_AMOUNTS_TEXT' => 'Bedragenblok (platte tekst)',
            'COMPANY_ADDRESS' => 'Bedrijfsadres',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
        ];

        $tenantId = $this->getTenantId();
        $users = $tenantId
            ? User::where('company_id', $tenantId)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email'])
            : collect();
        
        $menuService = app(MenuService::class);
        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);
        // Ensure current template type is in the list when editing (e.g. module was disabled later)
        if (!in_array($emailTemplate->type, $allowedTypes, true)) {
            $allowedTypes[] = $emailTemplate->type;
        }
        $typeLabels = static::emailTemplateTypeLabels();

        $isInfoRequestType = ($emailTemplate->type === 'informatieaanvraag');
        $formFields = $isInfoRequestType ? $emailTemplate->getOrderedFormFields() : collect();
        try {
            $allFormFieldsPool = $isInfoRequestType ? InfoRequestFormField::ordered()->get() : collect();
        } catch (\Throwable $e) {
            $allFormFieldsPool = collect();
        }
        $infoRequestVariables = static::buildInfoRequestVariablesFromFormFields($formFields);

        return view('admin.email-templates.edit', compact('emailTemplate', 'companies', 'templateVariables', 'infoRequestVariables', 'isInfoRequestType', 'formFields', 'allFormFieldsPool', 'allowedTypes', 'typeLabels', 'users'));
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
            'recipient_type' => 'nullable|in:user,email',
            'recipient_user_id' => 'nullable|exists:users,id',
            'recipient_email' => 'nullable|email',
            'form_field_order' => 'nullable|array',
            'form_field_order.*' => 'integer|exists:info_request_form_fields,id',
        ]);

        $emailTemplateData = $request->only([
            'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active',
            'recipient_type', 'recipient_user_id', 'recipient_email',
            'form_field_order',
        ]);
        if (isset($emailTemplateData['form_field_order']) && is_array($emailTemplateData['form_field_order'])) {
            $emailTemplateData['form_field_order'] = array_values(array_map('intval', $emailTemplateData['form_field_order']));
        }
        if ($request->filled('recipient_type') && $request->recipient_type === 'email') {
            $emailTemplateData['recipient_user_id'] = null;
        }
        if ($request->filled('recipient_type') && $request->recipient_type === 'user') {
            $emailTemplateData['recipient_email'] = null;
        }
        if (!$request->filled('recipient_type')) {
            $emailTemplateData['recipient_type'] = null;
            $emailTemplateData['recipient_user_id'] = null;
            $emailTemplateData['recipient_email'] = null;
        }

        // Als Super Admin: gebruik company_id uit formulier (kan null zijn voor algemeen)
        if (auth()->user()->hasRole('super-admin')) {
            $emailTemplateData['company_id'] = $request->filled('company_id') ? $request->company_id : null;
        } else {
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

    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bewerken.');
        }
        if (!$this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }

        $formFields = $emailTemplate->type === 'informatieaanvraag'
            ? $emailTemplate->getOrderedFormFields()
            : collect();
        $rules = [];
        foreach ($formFields as $field) {
            $key = 'test_' . $field->name;
            $rules[$key] = $field->getValidationRules();
        }
        if ($rules) {
            $request->validate($rules);
        }

        $toEmail = $emailTemplate->getRecipientEmailAddress();
        if (!$toEmail) {
            return redirect()->back()->with('error', 'Stel eerst een ontvanger in bij Basis Informatie (en sla de template op).');
        }

        $variables = ['DATUM_AANVRAAG' => now()->format('d-m-Y H:i')];
        foreach ($formFields as $field) {
            $variables[static::fieldNameToVariableKey($field->name)] = $request->input('test_' . $field->name, '');
        }
        $toName = $toEmail;
        if ($formFields->isNotEmpty()) {
            $first = $formFields->first();
            $nameKey = 'test_' . $first->name;
            $toName = trim((string) $request->input($nameKey, ''));
        }
        if ($toName === '' && $formFields->count() >= 2) {
            $second = $formFields->get(1);
            $toName = trim($request->input('test_' . $formFields->get(0)->name, '') . ' ' . $request->input('test_' . $second->name, ''));
        }
        if ($toName === '') {
            $toName = $toEmail;
        }
        $user = auth()->user();
        $fromEmail = $user?->email;
        $fromName = $user ? trim($user->first_name . ' ' . $user->last_name) : null;
        app(EmailTemplateService::class)->sendTestEmail($emailTemplate, $toEmail, $toName, $variables, $fromEmail, $fromName ?: null);

        return redirect()->back()->with('success', 'Testmail verstuurd naar ' . $toEmail);
    }
}
