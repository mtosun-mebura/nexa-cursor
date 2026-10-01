<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\TaxiNetworkPartnership;
use App\Models\User;
use App\Services\CompanyEmailLogoService;
use App\Support\NexaBranding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TaxiNetworkPartnershipMailer
{
    public function __construct(
        protected CompanyEmailLogoService $logos
    ) {}

    public function sendPartnerRequest(TaxiNetworkPartnership $partnership): void
    {
        $partnership->loadMissing(['ownerCompany', 'partnerCompany']);

        $partnerCompany = $partnership->partnerCompany;
        $ownerCompany = $partnership->ownerCompany;
        if (! $partnerCompany || ! $ownerCompany) {
            return;
        }

        $recipients = $this->companyAdminEmails((int) $partnerCompany->id);
        if ($recipients === []) {
            return;
        }

        $html = view('emails.taxi-network-partner-request', [
            'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
            'ownerCompanyName' => (string) $ownerCompany->name,
            'partnerCompanyName' => (string) $partnerCompany->name,
            'partnerContactName' => '',
            'actionUrl' => route('admin.taxi.dispatch_settings.edit').'#dispatch-nexa-network-partners',
        ])->render();

        $subject = 'NEXA Network: partnerverzoek van '.$ownerCompany->name;

        foreach ($recipients as $email) {
            $this->sendHtml($email, $subject, $html, (int) $partnerCompany->id);
        }
    }

    /**
     * @return list<string>
     */
    public function companyAdminEmails(int $companyId): array
    {
        return User::query()
            ->where('company_id', $companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->filter(function (User $user): bool {
                return $user->hasRole('company-admin')
                    || $user->hasRole('super-admin')
                    || $user->can('rides.update');
            })
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * One-off preview/test to a concrete address (NEXA Suite card styling).
     */
    public function sendTestTo(string $toEmail, ?Company $owner = null, ?Company $partner = null): void
    {
        $ownerName = $owner?->name ?? 'Taxi Royaal';
        $partnerName = $partner?->name ?? 'Partner Taxi';
        $partnerId = $partner?->id ? (int) $partner->id : null;

        $html = view('emails.taxi-network-partner-request', [
            'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
            'ownerCompanyName' => $ownerName,
            'partnerCompanyName' => $partnerName,
            'partnerContactName' => 'Mehmet',
            'actionUrl' => route('admin.taxi.dispatch_settings.edit').'#dispatch-nexa-network-partners',
        ])->render();

        $this->sendHtml(
            $toEmail,
            '[TEST] NEXA Network: partnerverzoek van '.$ownerName,
            $html,
            $partnerId
        );
    }

    private function sendHtml(string $toEmail, string $subject, string $html, ?int $companyId): void
    {
        try {
            Mail::send([], [], function ($message) use ($toEmail, $subject, $html, $companyId) {
                $htmlBody = $this->logos->embedInHtml($html, $message, $companyId, 'NEXA Suite');
                $message->to($toEmail)
                    ->subject($subject)
                    ->html($htmlBody);
            });
        } catch (Throwable $e) {
            Log::warning('Failed to send network partner request mail', [
                'to' => $toEmail,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
