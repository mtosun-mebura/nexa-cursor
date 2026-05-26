<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;
use App\Services\EmailTemplateService;
use App\Models\GeneralSetting;
use App\Services\ModuleDatabaseService;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class InfoRequestController extends Controller
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Verstuur een informatieaanvraag op basis van een e-mailtemplate (website-sectie formulier).
     * Validatie en velden komen uit Formulier velden beheer (info_request_form_fields).
     */
    /** Minimale tijd dat het formulier zichtbaar moet zijn voordat verzenden is toegestaan (seconden). */
    private const MIN_FORM_TIME_SECONDS = 3;

    /** Maximale geldigheid van het formulier-token (seconden). */
    private const MAX_FORM_TIME_SECONDS = 7200;

    public function submit(Request $request)
    {
        try {
            if (trim((string) $request->input('company_website', '')) !== '') {
                return $this->respond($request, redirect: fn () => redirect()->back()->with('error', 'Er is iets misgegaan. Probeer het formulier opnieuw.')->withInput(), json: fn () => response()->json(['message' => 'Er is iets misgegaan. Probeer het formulier opnieuw.'], 422));
            }

            $timeToken = $request->input('form_time_token');
            try {
                if (! $timeToken || ! is_string($timeToken)) {
                    throw new \Exception('Missing token');
                }
                $submittedAt = (int) Crypt::decryptString($timeToken);
                $elapsed = time() - $submittedAt;
                if ($elapsed < self::MIN_FORM_TIME_SECONDS) {
                    throw new \Exception('Too fast');
                }
                if ($elapsed > self::MAX_FORM_TIME_SECONDS) {
                    throw new \Exception('Form expired');
                }
            } catch (\Exception $e) {
                return $this->respond($request, redirect: fn () => redirect()->back()->with('error', 'Het formulier is verlopen of ongeldig. Vernieuw de pagina en probeer opnieuw.')->withInput(), json: fn () => response()->json(['message' => 'Het formulier is verlopen of ongeldig. Vernieuw de pagina en probeer opnieuw.'], 422));
            }

            $request->validate(['template_id' => 'required|integer|min:1'], ['template_id.required' => 'Geen template gekozen.']);

            $template = $this->findTemplateForInfoRequest((int) $request->template_id);
            if (!$template) {
                return $this->respond($request, redirect: fn () => redirect()->back()->with('error', 'De gekozen template is niet beschikbaar.')->withInput(), json: fn () => response()->json(['message' => 'De gekozen template is niet beschikbaar.'], 400));
            }

            $formFields = $template->getOrderedFormFields();
            if ($formFields->isEmpty()) {
                $formFields = InfoRequestFormField::ordered()->get();
            }
            $rules = ['template_id' => 'required|integer|min:1'];
            $messages = ['template_id.required' => 'Geen template gekozen.'];
            foreach ($formFields as $field) {
                $fieldRules = $field->getValidationRules();
                if ($field->validation_rule === 'email') {
                    $fieldRules = array_filter($fieldRules, fn ($r) => $r !== 'email');
                    $fieldRules[] = function (string $attribute, mixed $value, \Closure $fail) {
                        $msg = self::validateEmailDetailed($value);
                        if ($msg !== null) {
                            $fail($msg);
                        }
                    };
                }
                if ($field->validation_rule === 'tel') {
                    $fieldRules[] = function (string $attribute, mixed $value, \Closure $fail) {
                        $msg = self::validateTelefoonNl($value);
                        if ($msg !== null) {
                            $fail($msg);
                        }
                    };
                }
                $rules[$field->name] = $fieldRules;
                $messages[$field->name . '.required'] = $field->label . ' is verplicht.';
            }
            $request->validate($rules, $messages);

            $toEmail = $template->getRecipientEmailAddress() ?? config('mail.from.address') ?? 'noreply@example.com';
            $companyName = (string) ($template->company?->name ?? config('app.name') ?? 'Ons bedrijf');
            $variables = $this->buildTemplateVariables($request, $formFields);

            app(EmailTemplateService::class)->sendTestEmail(
                $template,
                $toEmail,
                $companyName,
                $variables
            );
            $successMessage = GeneralSetting::get('info_request_success_title', 'Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.');
            return $this->respond($request, redirect: fn () => redirect()->back()->with('info_request_sent', true)->with('success', $successMessage), json: fn () => response()->json(['success' => true, 'message' => $successMessage]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Info request form error: ' . $e->getMessage(), ['exception' => $e]);
            $errorMessage = 'Er is een fout opgetreden bij het versturen. Probeer het later opnieuw.';
            $jsonMessage = config('app.debug') ? $errorMessage . ' (' . $e->getMessage() . ')' : $errorMessage;
            return $this->respond($request, redirect: fn () => redirect()->back()->with('error', $errorMessage)->withInput(), json: fn () => response()->json(['message' => $jsonMessage], 500));
        }
    }

    private function respond(Request $request, callable $redirect, callable $json)
    {
        if ($request->wantsJson()) {
            return $json();
        }
        return $redirect();
    }

    /**
     * Gedetailleerde e-mailvalidatie: geeft aan wat er ontbreekt (@, deel na @, punt, etc.).
     * Retourneert null bij geldig adres, anders de foutmelding.
     */
    public static function validateEmailDetailed(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! str_contains($value, '@')) {
            return 'Het e-mailadres moet een @ bevatten.';
        }
        $parts = explode('@', $value);
        if (count($parts) !== 2) {
            return 'Het e-mailadres mag maar één @ bevatten.';
        }
        [$local, $domain] = $parts;
        if ($local === '') {
            return 'Geef een adresgedeelte op vóór de @.';
        }
        if ($domain === '') {
            return "Geef een adresgedeelte op na de '@'. Bijvoorbeeld: voorbeeld@domein.nl";
        }
        if (! str_contains($domain, '.')) {
            return "Het e-mailadres moet een punt (.) bevatten in het deel na de '@'. Bijvoorbeeld: voorbeeld@domein.nl";
        }
        $afterLastDot = substr($domain, strrpos($domain, '.') + 1);
        if (strlen($afterLastDot) < 2) {
            return 'Het deel na de laatste punt moet minstens twee tekens zijn (bijv. nl of com).';
        }
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Vul een geldig e-mailadres in. Controleer op spaties of ongeldige tekens.';
        }
        return null;
    }

    /**
     * Nederlands telefoonnummer: 10 cijfers, of +31 gevolgd door 9 cijfers (totaal 12 karakters).
     * Spaties en streepjes worden genegeerd.
     */
    public static function validateTelefoonNl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $normalized = preg_replace('/[\s\-]/', '', $value);
        if (str_starts_with($normalized, '+31')) {
            if (! preg_match('/^\+31\d{9}$/', $normalized)) {
                return 'Bij een nummer met +31 moeten daarna precies 9 cijfers volgen (totaal 12 karakters). Bijvoorbeeld: +31612345678';
            }
        } else {
            $digitsOnly = preg_replace('/\D/', '', $normalized);
            if (strlen($digitsOnly) !== 10) {
                return 'Het telefoonnummer moet 10 cijfers bevatten. Bijvoorbeeld: 0612345678';
            }
        }
        return null;
    }

    /**
     * Bouw template-variabelen uit request: variabelenaam = slug in HOOFDLETTERS met underscores.
     */
    private function buildTemplateVariables(Request $request, $formFields): array
    {
        $emptyPlaceholder = '-';
        $variables = ['DATUM_AANVRAAG' => now()->format('d-m-Y H:i')];
        if ($formFields->isNotEmpty()) {
            foreach ($formFields as $field) {
                $key = strtoupper(str_replace('-', '_', $field->name));
                $val = $request->input($field->name, '');
                $variables[$key] = trim((string) $val) !== '' ? $val : $emptyPlaceholder;
            }
        } else {
            $variables['VOORNAAM'] = trim((string) ($request->voornaam ?? '')) !== '' ? ($request->voornaam ?? '') : $emptyPlaceholder;
            $variables['ACHTERNAAM'] = trim((string) ($request->achternaam ?? '')) !== '' ? ($request->achternaam ?? '') : $emptyPlaceholder;
            $variables['EMAIL_AANVRAAG'] = trim((string) ($request->email ?? '')) !== '' ? ($request->email ?? '') : $emptyPlaceholder;
            $variables['TELEFOONNUMMER'] = trim((string) ($request->telefoon ?? '')) !== '' ? ($request->telefoon ?? '') : $emptyPlaceholder;
            $variables['OMSCHRIJVING'] = trim((string) ($request->omschrijving ?? '')) !== '' ? ($request->omschrijving ?? '') : $emptyPlaceholder;
        }
        return $variables;
    }

    /**
     * Zoek actief template op standaard- of module-connection (actieve frontend-module).
     */
    private function findTemplateForInfoRequest(int $templateId): ?EmailTemplate
    {
        $template = EmailTemplate::where('id', $templateId)->where('is_active', true)->first();
        if ($template) {
            return $template;
        }
        $brandingModule = $this->websiteBuilder->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        if ($moduleName && $this->moduleDb->supportsModuleDatabases()) {
            $connName = $this->moduleDb->getModuleConnectionName($moduleName);
            if (Config::has("database.connections.{$connName}")) {
                return EmailTemplate::on($connName)->where('id', $templateId)->where('is_active', true)->first();
            }
        }
        return null;
    }
}
