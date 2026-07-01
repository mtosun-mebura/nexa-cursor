<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\EmailTemplate;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeEmailTemplateService;
use App\Services\EmailTemplateService;
use App\Services\InformatieaanvraagEmailHtmlNormalizer;
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
            'taxi_ride_accepted' => 'taxi',
            'taxi_customer_login_code' => 'taxi',
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
            'taxi_ride_accepted' => 'Taxi: rit geaccepteerd (klant)',
            'taxi_customer_login_code' => 'Taxi: eenmalige inlogcode (klant)',
        ];
    }

    /**
     * Algemene templatevariabelen (skillmatching, factuur, etc.).
     *
     * @return array<string, string>
     */
    protected static function genericTemplateVariables(): array
    {
        return [
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
    }

    /**
     * Variabelenlijst in admin, afgestemd op template-type.
     *
     * @return array<string, string>
     */
    protected static function templateVariablesForType(?string $type): array
    {
        return match ($type) {
            TaxiCustomerLoginCodeEmailTemplateService::TYPE => TaxiCustomerLoginCodeEmailTemplateService::variableLabels(),
            TaxiCustomerAcceptEmailTemplateService::TYPE => TaxiCustomerAcceptEmailTemplateService::variableLabels(),
            default => static::genericTemplateVariables(),
        };
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

    /**
     * Lijst e-mailtemplates: tenant ziet eigen templates; super-admin met tenant ook globale defaults.
     */
    protected function applyEmailTemplateListFilter($query)
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin') && ! session('selected_tenant')) {
            return $query;
        }

        $tenantId = $user->hasRole('super-admin')
            ? (int) session('selected_tenant')
            : (int) ($user->company_id ?? 0);

        if ($tenantId <= 0) {
            return $query->whereNull('company_id');
        }

        if ($user->hasRole('super-admin')) {
            return $query->where(function ($q) use ($tenantId) {
                $q->whereNull('company_id')->orWhere('company_id', $tenantId);
            });
        }

        return $query->where('company_id', $tenantId);
    }

    protected function provisionTaxiEmailTemplatesIfNeeded(MenuService $menuService): void
    {
        $allowed = $this->getAllowedEmailTemplateTypes($menuService);
        $tenantId = $this->getTenantId();
        $tenantId = $tenantId && (int) $tenantId > 0 ? (int) $tenantId : null;

        if (in_array(TaxiCustomerAcceptEmailTemplateService::TYPE, $allowed, true)) {
            app(TaxiCustomerAcceptEmailTemplateService::class)->ensureGlobalTemplateExists();
        }

        if (! in_array(TaxiCustomerLoginCodeEmailTemplateService::TYPE, $allowed, true)) {
            return;
        }

        $service = app(TaxiCustomerLoginCodeEmailTemplateService::class);
        $service->ensureGlobalTemplateExists();

        if ($tenantId !== null) {
            $service->ensureTenantTemplateExists($tenantId);
        }
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates te bekijken.');
        }

        $menuService = app(MenuService::class);
        $this->provisionTaxiEmailTemplatesIfNeeded($menuService);

        $query = EmailTemplate::with('company');

        $query = $this->applyEmailTemplateListFilter($query);
        
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
        $statsQuery = $this->applyEmailTemplateListFilter($statsQuery);
        
        $stats = [
            'total_templates' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'unique_types' => (clone $statsQuery)->distinct('type')->count('type'),
        ];
        
        // Get companies for filter (only for super-admin)
        $companies = auth()->user()->hasRole('super-admin') ? Company::orderBy('name')->get() : collect();

        $allowedTypes = $this->getAllowedEmailTemplateTypes($menuService);
        $typeLabels = static::emailTemplateTypeLabels();

        return view('admin.email-templates.index', compact('emailTemplates', 'stats', 'companies', 'allowedTypes', 'typeLabels'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates aan te maken.');
        }
        
        $query = Company::query();
        $query = $this->applyTenantFilter($query);
        $companies = $query->get();
        
        $templateVariables = static::templateVariablesForType(old('type'));

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
                <table role="presentation" class="info-request-email-card" width="100%" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td class="info-request-email-header" width="100%" bgcolor="#2563eb" style="padding: 24px 30px; background-color: #2563eb; border-radius: 8px 8px 0 0; width: 100%;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; line-height: 1.3;">Nieuwe informatieaanvraag</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-body" width="100%" bgcolor="#ffffff" style="padding: 30px; background-color: #ffffff; color: #333333; width: 100%;">
                            <p style="margin: 0; color: #333333; font-size: 16px; line-height: 1.5;">
                                Er is een informatieaanvraag binnengekomen met de volgende gegevens:
                            </p>
                            <table role="presentation" class="info-request-fields" width="100%" style="width: 100%; border-collapse: collapse; margin: 0; font-size: 15px; color: #333333; background-color: #ffffff; text-align: left; table-layout: fixed;">
                                <colgroup><col style="width: 175px;"><col></colgroup>
{{ DYNAMIC_FORM_FIELDS }}
                            </table>
                            <p style="margin: 0 0 8px 0; color: #333333; font-size: 16px; font-weight: bold;">Omschrijving / vraag:</p>
                            <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 1.6; white-space: pre-wrap;">{{ OMSCHRIJVING }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-footer" width="100%" bgcolor="#f9fafb" style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb; width: 100%;">
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

        $loginCodeTemplateService = app(TaxiCustomerLoginCodeEmailTemplateService::class);
        $loginCodeTemplateService->ensureGlobalTemplateExists();
        $defaultHtmlTemplateLoginCode = $loginCodeTemplateService->defaultHtmlContent();

        return view('admin.email-templates.create', compact(
            'companies',
            'templateVariables',
            'infoRequestVariables',
            'formFields',
            'defaultHtmlTemplate',
            'defaultHtmlTemplateInformatieaanvraag',
            'defaultHtmlTemplateLoginCode',
            'allowedTypes',
            'typeLabels',
            'users'
        ));
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
        
        $templateVariables = static::templateVariablesForType($emailTemplate->type);

        $tenantId = $this->getTenantId();
        $users = $tenantId
            ? User::where('company_id', $tenantId)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email'])
            : collect();

        $isInfoRequestType = ($emailTemplate->type === 'informatieaanvraag');
        $formFields = $isInfoRequestType ? $emailTemplate->getOrderedFormFields() : collect();
        $previewHtml = $emailTemplate->html_content ?? '';
        if ($isInfoRequestType && $previewHtml !== '') {
            $previewHtml = str_replace('{{ DYNAMIC_FORM_FIELDS }}', $emailTemplate->renderDynamicFormFieldsHtml(), $previewHtml);
            $previewHtml = $this->normalizeInformatieaanvraagPreviewHtml($previewHtml);
        }
        $previewHtml = $this->injectEmailTemplatePreviewLogo($emailTemplate, $previewHtml);

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
        
        $templateVariables = static::templateVariablesForType($emailTemplate->type);

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
            'form_field_required' => 'nullable|array',
            'form_field_required.*' => 'boolean',
        ]);

        $emailTemplateData = $request->only([
            'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active',
            'recipient_type', 'recipient_user_id', 'recipient_email',
            'form_field_order',
        ]);
        if (isset($emailTemplateData['form_field_order']) && is_array($emailTemplateData['form_field_order'])) {
            $emailTemplateData['form_field_order'] = array_values(array_map('intval', $emailTemplateData['form_field_order']));
            $required = [];
            foreach ($emailTemplateData['form_field_order'] as $fieldId) {
                $required[(string) $fieldId] = $request->boolean('form_field_required.' . $fieldId);
            }
            $emailTemplateData['form_field_required'] = $required;
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

    public function duplicate(EmailTemplate $emailTemplate)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('create-email-templates')) {
            abort(403, 'Je hebt geen rechten om e-mail templates aan te maken.');
        }

        if (! $this->canAccessResource($emailTemplate)) {
            abort(403, 'Je hebt geen toegang tot deze e-mail template.');
        }

        $user = auth()->user();
        $companyId = $user->hasRole('super-admin') ? null : $user->company_id;

        $copy = EmailTemplate::query()->create([
            'name' => $this->buildDuplicateTemplateName($emailTemplate->name),
            'subject' => $emailTemplate->subject,
            'type' => $emailTemplate->type,
            'html_content' => $emailTemplate->html_content,
            'text_content' => $emailTemplate->text_content,
            'description' => $emailTemplate->description,
            'is_active' => false,
            'company_id' => $companyId,
            'recipient_type' => $emailTemplate->recipient_type,
            'recipient_user_id' => $emailTemplate->recipient_user_id,
            'recipient_email' => $emailTemplate->recipient_email,
            'form_field_order' => $emailTemplate->form_field_order,
            'form_field_required' => $emailTemplate->form_field_required,
        ]);

        return redirect()
            ->route('admin.email-templates.edit', $copy)
            ->with('success', 'Template gedupliceerd. Pas de naam aan en koppel het template aan een bedrijf indien nodig.');
    }

    /**
     * Tenant voor logo in preview/verzending bij algemeen template (company_id null).
     * Volgorde: gekoppeld bedrijf → geselecteerde tenant (super-admin) → account-bedrijf → host-tenant.
     */
    protected function resolveEmailTemplatePreviewCompanyId(EmailTemplate $emailTemplate): ?int
    {
        if ($emailTemplate->company_id) {
            return (int) $emailTemplate->company_id;
        }

        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            return (int) session('selected_tenant');
        }

        $tenantId = $this->getTenantId();
        if ($tenantId) {
            return (int) $tenantId;
        }

        if (app()->bound('resolved_tenant_id')) {
            $resolved = app('resolved_tenant_id');
            if ($resolved !== null && (int) $resolved > 0) {
                return (int) $resolved;
            }
        }

        return null;
    }

    protected function normalizeInformatieaanvraagPreviewHtml(string $html): string
    {
        return app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);
    }

    protected function injectEmailTemplatePreviewLogo(EmailTemplate $emailTemplate, string $previewHtml): string
    {
        if ($previewHtml === '') {
            return $previewHtml;
        }

        $previewCompanyId = $this->resolveEmailTemplatePreviewCompanyId($emailTemplate);
        $previewCompanyName = $emailTemplate->company?->name
            ?? ($previewCompanyId ? Company::find($previewCompanyId)?->name : null)
            ?? 'Ons bedrijf';

        return app(\App\Services\CompanyEmailLogoService::class)->injectPreviewLogoIntoHtml(
            $previewHtml,
            $previewCompanyId,
            $previewCompanyName,
            true
        );
    }

    protected function buildDuplicateTemplateName(string $originalName): string
    {
        $base = trim(preg_replace('/ - kopie(?: \d+)?$/u', '', $originalName) ?? $originalName);
        $suffix = ' - kopie';
        $name = $base.$suffix;
        $counter = 2;

        while (EmailTemplate::query()->where('name', $name)->exists()) {
            $name = $base.$suffix.' '.$counter;
            $counter++;
        }

        return $name;
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
            $rules[$key] = $emailTemplate->validationRulesForFormField($field);
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
