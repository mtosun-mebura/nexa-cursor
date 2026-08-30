<?php

namespace App\Services\PlatformBilling;

use App\Models\Invoice;
use App\Models\TenantCustomerEmail;
use App\Modules\NexaTaxi\Services\TaxiMolliePaymentService;
use App\Services\InvoicePdfService;
use App\Services\PaymentProviderService;
use App\Services\TenantCustomerMailService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TenantCustomerInvoicePaymentService
{
    public function __construct(
        private readonly PaymentProviderService $paymentProviders,
        private readonly TaxiMolliePaymentService $mollie,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function createPaymentLinkAndSend(Invoice $invoice, string $recipientEmail): Invoice
    {
        $email = trim($recipientEmail);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Voer een geldig e-mailadres in.');
        }

        if ((float) $invoice->total_amount <= 0) {
            throw new \InvalidArgumentException('Factuurbedrag moet groter zijn dan nul.');
        }

        $companyId = (int) $invoice->company_id;
        $provider = $this->paymentProviders->getMollieForCompany($companyId);
        if (! $provider) {
            throw new RuntimeException('Geen actieve Mollie-betalingsprovider voor deze tenant. Stel er één in onder Betalingsproviders.');
        }

        $apiKey = $this->paymentProviders->mollieApiKeyForCompany($companyId);
        if (! $apiKey) {
            throw new RuntimeException('De actieve Mollie-betalingsprovider heeft geen geldige API-sleutel.');
        }
        $redirectUrl = route('admin.tenant-customer-invoices.show', $invoice);
        $webhookUrl = $this->paymentProviders->mollieWebhookUrlForTenantInvoices($companyId);

        $payment = $this->mollie->createPayment(
            $apiKey,
            (float) $invoice->total_amount,
            'Factuur '.$invoice->invoice_number,
            $redirectUrl,
            $webhookUrl,
            [
                'invoice_id' => $invoice->id,
                'company_id' => $companyId,
                'module' => 'tenant_customer',
            ]
        );

        $checkoutUrl = $this->mollie->checkoutUrl($payment);
        $invoice->update([
            'customer_email' => $email,
            'mollie_payment_id' => (string) ($payment['id'] ?? ''),
            'mollie_checkout_url' => $checkoutUrl,
            'status' => $invoice->status === 'draft' ? 'sent' : $invoice->status,
        ]);

        $body = "Beste,\n\n".
            "Bij deze ontvangt u factuur {$invoice->invoice_number}.\n".
            'Totaalbedrag: €'.number_format((float) $invoice->total_amount, 2, ',', '.')."\n\n";
        if ($checkoutUrl) {
            $body .= "U kunt direct online betalen via:\n{$checkoutUrl}\n\n";
        }
        $body .= 'Met vriendelijke groet';

        $html = '<p>Beste,</p>'
            .'<p>Bij deze ontvangt u factuur <strong>'.e($invoice->invoice_number).'</strong>.</p>'
            .'<p>Totaalbedrag: <strong>€'.e(number_format((float) $invoice->total_amount, 2, ',', '.')).'</strong></p>';
        if ($checkoutUrl) {
            $html .= '<p>U kunt direct online betalen via:<br><a href="'.e($checkoutUrl).'">'.e($checkoutUrl).'</a></p>';
        }
        $html .= '<p>Met vriendelijke groet</p>';

        try {
            $pdf = $this->pdf->generateAndStore($invoice->fresh());
        } catch (\Throwable $e) {
            Log::warning('Klantfactuur-PDF kon niet worden gemaakt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $pdf = null;
        }

        $attachments = [];
        if ($pdf && ! empty($pdf['bytes'])) {
            $attachments[] = [
                'bytes' => $pdf['bytes'],
                'filename' => 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf',
                'mime' => 'application/pdf',
            ];
        }

        app(TenantCustomerMailService::class)->send([
            'company_id' => $companyId,
            'type' => TenantCustomerEmail::TYPE_PAYMENT_LINK,
            'to_email' => $email,
            'to_name' => $invoice->customer_name,
            'subject' => 'Factuur '.$invoice->invoice_number,
            'html' => $html,
            'text' => $body,
            'related_type' => 'invoice',
            'related_id' => $invoice->id,
            'attachments' => $attachments,
            'throw' => true,
        ]);

        return $invoice->fresh();
    }

    public function syncPaymentFromMollie(string $molliePaymentId): void
    {
        $invoice = Invoice::query()
            ->where('mollie_payment_id', $molliePaymentId)
            ->where('module', Invoice::MODULE_CUSTOMER)
            ->first();

        if (! $invoice) {
            return;
        }

        $apiKey = $this->paymentProviders->mollieApiKeyForCompany((int) $invoice->company_id);
        if (! $apiKey) {
            Log::warning('Tenant klantfactuur webhook: geen Mollie API-sleutel', [
                'invoice_id' => $invoice->id,
                'mollie_payment_id' => $molliePaymentId,
            ]);

            return;
        }

        $remote = $this->mollie->fetchPayment($apiKey, $molliePaymentId);
        if (! $remote) {
            return;
        }

        $status = $this->mollie->mapMollieStatus((string) ($remote['status'] ?? ''));
        if ($status !== 'paid') {
            return;
        }

        $invoice->update([
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);
    }
}
