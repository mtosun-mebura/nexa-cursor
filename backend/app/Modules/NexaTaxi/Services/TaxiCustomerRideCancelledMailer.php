<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\InvoiceSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Models\TenantCustomerEmail;
use App\Services\CompanyEmailLogoService;
use App\Services\TenantCustomerMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class TaxiCustomerRideCancelledMailer
{
    public const LOG_CONTEXT = 'customer_cancel_email';

    public function __construct(
        protected TaxiRideNotificationLogService $notificationLogs,
    ) {}

    public function refundBusinessDays(): int
    {
        $days = (int) config('taxi-dispatch.customer_refund_business_days', 10);

        return max(1, min(30, $days));
    }

    /**
     * @param  array{refunded?: bool, refund_error?: ?string, reason?: string}  $context
     */
    public function send(string $conn, RideRequest $ride, array $context = []): bool
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }

        $email = trim((string) ($ride->customer_email ?? ''));
        $rideId = (int) $ride->id;
        $customerName = trim((string) ($ride->customer_name ?: 'Klant'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $customerName,
                null,
                null,
                self::LOG_CONTEXT.': Geen geldig klant-e-mailadres.'
            );

            return false;
        }

        if ($this->alreadySent($conn, $rideId)) {
            return true;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $settings = $companyId > 0
            ? InvoiceSetting::getSettingsForCompany($companyId)
            : InvoiceSetting::getSettings();

        $companyName = (string) ($settings->company_name ?? $company?->name ?? 'Nexa Taxi');
        $companyEmail = (string) ($settings->company_email ?? $company?->email ?? '');
        $pickupAt = $ride->pickup_at
            ? (ContractTransportTimezone::asAmsterdamWall($ride->pickup_at)?->format('d-m-Y H:i') ?: '—')
            : '—';

        $reason = (string) ($context['reason'] ?? TaxiRideCancellationService::REASON_CUSTOMER);
        $driverMessage = trim((string) ($context['message'] ?? ''));
        $intro = match (true) {
            $reason === TaxiRideCancellationService::REASON_AUTO_UNACCEPTED =>
                'Uw rit is automatisch geannuleerd omdat er binnen de beschikbare tijd geen chauffeur beschikbaar was.',
            $reason === TaxiRideCancellationService::REASON_DRIVER && $driverMessage !== '' =>
                $driverMessage,
            $reason === TaxiRideCancellationService::REASON_DRIVER =>
                'Uw rit is geannuleerd door de chauffeur.',
            default => 'Uw rit is geannuleerd zoals u heeft aangevraagd.',
        };

        $refunded = ! empty($context['refunded']);
        $refundError = isset($context['refund_error']) ? trim((string) $context['refund_error']) : '';
        $wasPaid = in_array($ride->payment_status, [
            RideRequest::PAYMENT_STATUS_PAID,
            RideRequest::PAYMENT_STATUS_REFUNDED,
            RideRequest::PAYMENT_STATUS_REFUND_PENDING,
            RideRequest::PAYMENT_STATUS_REFUND_FAILED,
        ], true) || $refunded;

        $days = $this->refundBusinessDays();
        $refundMessage = null;
        if ($refunded || ($wasPaid && $refundError === '')) {
            $refundMessage = 'Het betaalde bedrag wordt teruggestort op de rekening/methode waarmee u heeft betaald. '
                .'Reken op zichtbaarheid binnen circa '.$days.' werkdagen (afhankelijk van uw bank).';
        } elseif ($wasPaid && $refundError !== '') {
            $refundMessage = 'De automatische terugbetaling is nog niet afgerond. Neem contact op met '
                .$companyName.' zodat we dit voor u kunnen afronden.';
        } else {
            $refundMessage = 'Er is geen voorafbetaling gekoppeld aan deze rit; er volgt geen terugstorting.';
        }

        $html = View::make('emails.taxi-ride-cancelled-customer', [
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
            'company_name' => $companyName,
            'company_website_url' => \App\Support\EmailCardHtml::primaryWebsiteUrlForCompany($companyId > 0 ? $companyId : null),
            'company_email' => $companyEmail,
            'customer_name' => $customerName,
            'ride_id' => $rideId,
            'pickup_at' => $pickupAt,
            'pickup_address' => (string) ($ride->pickup_address ?: '—'),
            'dropoff_address' => (string) ($ride->dropoff_address ?: '—'),
            'intro_text' => $intro,
            'refund_message' => $refundMessage,
        ])->render();

        $subject = 'Rit #'.$rideId.' geannuleerd — '.$companyName;

        try {
            $record = app(TenantCustomerMailService::class)->send([
                'company_id' => $companyId > 0 ? $companyId : null,
                'type' => TenantCustomerEmail::TYPE_BOOKING,
                'to_email' => $email,
                'to_name' => $customerName,
                'subject' => $subject,
                'html' => $html,
                'related_type' => 'ride_request',
                'related_id' => $rideId,
                'reply_to' => $companyEmail !== '' ? $companyEmail : null,
                'reply_to_name' => $companyName,
                'meta' => [
                    'event' => 'ride_cancelled',
                    'reason' => $reason,
                    'refunded' => $refunded,
                ],
                'platform_mail' => $companyId <= 0,
            ]);

            $sent = $record->status === TenantCustomerEmail::STATUS_SENT;
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                $sent ? RideRequestNotificationLog::STATUS_SENT : RideRequestNotificationLog::STATUS_FAILED,
                $customerName,
                $email,
                null,
                self::LOG_CONTEXT.($sent ? '' : ': '.$record->status)
            );

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('Annulatie-e-mail naar klant mislukt', [
                'ride_request_id' => $rideId,
                'error' => $e->getMessage(),
            ]);
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_FAILED,
                $customerName,
                $email,
                null,
                self::LOG_CONTEXT.': '.$e->getMessage()
            );

            return false;
        }
    }

    private function alreadySent(string $conn, int $rideId): bool
    {
        if ($rideId <= 0 || ! \App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema::tableExists($conn)) {
            return false;
        }

        return RideRequestNotificationLog::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('channel', RideRequestNotificationLog::CHANNEL_EMAIL)
            ->where('status', RideRequestNotificationLog::STATUS_SENT)
            ->where('detail', self::LOG_CONTEXT)
            ->exists();
    }
}
