<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSetting;
use App\Models\PlatformBillingSetting;
use App\Services\PaymentProviderService;
use App\Services\PlatformBilling\PlatformMollieService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlatformBillingSettingsController extends Controller
{
    public function edit(PlatformMollieService $mollie): View
    {
        $this->ensureSuperAdmin();
        $settings = PlatformBillingSetting::current()->mergeDefaultsFromGlobalInvoiceSettings();
        $globalInvoiceSettings = InvoiceSetting::query()->whereNull('company_id')->first();
        $mollieConfigured = $mollie->isConfigured();
        $mollieApiKeyMasked = $settings->maskedMollieApiKey();
        $mollieFromEnvFallback = ! $settings->hasStoredMollieApiKey()
            && PaymentProviderService::isValidMollieApiKeyFormat(trim((string) config('platform-billing.mollie_api_key', '')));
        $defaultPlatformWebhookUrl = url('/api/platform/webhooks/mollie');

        return view('admin.platform-billing.settings', compact(
            'settings',
            'globalInvoiceSettings',
            'mollieConfigured',
            'mollieApiKeyMasked',
            'mollieFromEnvFallback',
            'defaultPlatformWebhookUrl',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'tax_rate_percent' => 'required|numeric|min:0|max:100',
            'payment_terms_days' => 'required|integer|min:1|max:365',
            'invoice_footer' => 'nullable|string|max:5000',
            'invoice_number_prefix' => 'required|string|max:10',
            'invoice_number_format' => 'required|string|max:100',
            'next_invoice_number' => 'required|integer|min:1',
            'current_year' => 'required|integer|min:2020|max:2100',
            'invoice_title' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_house_number' => 'nullable|string|max:20',
            'company_city' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_vat_number' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:50',
            'invoice_payment_terms_text' => 'nullable|string|max:1000',
            'mollie_api_key' => 'nullable|string|max:255',
            'mollie_webhook_url' => 'nullable|url|max:500',
            'clear_mollie_api_key' => 'nullable|boolean',
        ]);

        if (array_key_exists('invoice_footer', $validated)) {
            $validated['invoice_footer'] = trim((string) $validated['invoice_footer']) ?: null;
        }
        if (array_key_exists('invoice_payment_terms_text', $validated)) {
            $validated['invoice_payment_terms_text'] = trim((string) $validated['invoice_payment_terms_text']) ?: null;
        }
        if (array_key_exists('mollie_webhook_url', $validated)) {
            $validated['mollie_webhook_url'] = trim((string) ($validated['mollie_webhook_url'] ?? '')) ?: null;
        }

        $settings = PlatformBillingSetting::current();
        $plainMollieKey = trim((string) ($validated['mollie_api_key'] ?? ''));
        $clearMollieKey = $request->boolean('clear_mollie_api_key');
        unset($validated['mollie_api_key'], $validated['clear_mollie_api_key']);

        if ($plainMollieKey !== '') {
            if (! PaymentProviderService::isValidMollieApiKeyFormat($plainMollieKey)) {
                return back()
                    ->withInput()
                    ->withErrors(['mollie_api_key' => 'Ongeldige Mollie API-sleutel. Gebruik een test_… of live_… sleutel.']);
            }
            $settings->setEncryptedMollieApiKey($plainMollieKey);
        } elseif ($clearMollieKey) {
            $settings->setEncryptedMollieApiKey(null);
        }

        $settings->fill($validated);
        $settings->save();

        return redirect()->route('admin.platform-billing.settings.edit')
            ->with('success', 'SaaS-facturatie-instellingen opgeslagen.');
    }

    public function importFromInvoiceSettings(): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $global = InvoiceSetting::query()->whereNull('company_id')->first();

        if (! $global) {
            return back()->with('error', 'Geen globale factuurinstellingen gevonden om over te nemen.');
        }

        PlatformBillingSetting::current()->update([
            'company_name' => $global->company_name,
            'company_address' => $global->company_address,
            'company_city' => $global->company_city,
            'company_postal_code' => $global->company_postal_code,
            'company_country' => $global->company_country,
            'company_vat_number' => $global->company_vat_number,
            'company_email' => $global->company_email,
            'company_phone' => $global->company_phone,
            'bank_account' => $global->bank_account,
            'invoice_payment_terms_text' => $global->invoice_payment_terms_text,
            'payment_terms_days' => $global->payment_terms_days,
            'tax_rate_percent' => $global->default_tax_rate,
            'invoice_footer' => $global->invoice_footer_text,
            'invoice_number_prefix' => $global->invoice_number_prefix,
            'invoice_number_format' => $global->invoice_number_format,
            'next_invoice_number' => $global->next_invoice_number,
            'current_year' => $global->current_year,
            'sender_name' => $global->company_name,
            'sender_email' => $global->company_email,
        ]);

        return back()->with('success', 'Bedrijfs- en factuurgegevens overgenomen uit algemene factuurinstellingen.');
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}
