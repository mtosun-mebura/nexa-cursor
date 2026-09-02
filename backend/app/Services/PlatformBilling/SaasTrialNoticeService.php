<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\NexaPricingService;
use App\Services\SaasTrialEndingEmailTemplateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class SaasTrialNoticeService
{
    public function __construct(
        private readonly TenantSubscriptionService $subscriptions,
        private readonly NexaPricingService $pricing,
        private readonly SubscriptionBillingCalculator $calculator,
        private readonly SaasTrialEndingEmailTemplateService $templates,
        private readonly EmailTemplateService $mail,
    ) {}

    public function run(?CarbonInterface $asOf = null): int
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $noticeDays = $this->pricing->trialNoticeDays();
        $sent = 0;

        CompanyBillingProfile::query()
            ->with('company')
            ->whereNull('trial_notice_sent_at')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>', $asOf->toDateString())
            ->whereDate('trial_ends_at', '<=', $asOf->copy()->addDays($noticeDays)->toDateString())
            ->chunkById(50, function ($profiles) use ($asOf, &$sent) {
                foreach ($profiles as $profile) {
                    if ($this->sendForProfile($profile, $asOf)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendForProfile(CompanyBillingProfile $profile, ?CarbonInterface $asOf = null): bool
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $company = $profile->company;
        if (! $company || ! $company->is_active) {
            return false;
        }
        if (! $this->subscriptions->isInTrial($profile, $asOf)) {
            return false;
        }
        if ($profile->trial_notice_sent_at) {
            return false;
        }

        $remaining = $this->subscriptions->trialDaysRemaining($profile, $asOf);
        $noticeDays = $this->pricing->trialNoticeDays();
        if ($remaining === null || $remaining < 1 || $remaining > $noticeDays) {
            return false;
        }

        $to = $this->recipientEmail($profile, $company);
        if ($to === null) {
            Log::warning('Proeftijd-aankondiging overgeslagen: geen e-mailadres.', [
                'company_id' => $company->id,
            ]);

            return false;
        }

        $template = $this->templates->resolveActive();
        $trialEnds = Carbon::parse($profile->trial_ends_at)->startOfDay();
        $packageKey = trim((string) ($company->package_key ?? ''));
        $package = $packageKey !== '' ? $this->pricing->packageByKey($packageKey) : null;

        $stopUrl = URL::temporarySignedRoute(
            'saas.trial.stop.show',
            $trialEnds->copy()->addDays(7)->endOfDay(),
            ['company' => $company->id]
        );
        $collection = $this->calculator->firstCollectionPresentation($profile, $trialEnds);

        try {
            $this->mail->sendTestEmail(
                $template,
                $to,
                $profile->billing_contact_name ?: $company->name,
                [
                    'COMPANY_NAME' => $company->name,
                    'PACKAGE_NAME' => (string) ($package['name'] ?? ($packageKey !== '' ? $packageKey : 'NEXA')),
                    'TRIAL_ENDS_AT' => $trialEnds->translatedFormat('j F Y'),
                    'DAYS_REMAINING' => (string) $remaining,
                    'START_DATE' => $collection['start_label'],
                    'STOP_TRIAL_URL' => $stopUrl,
                ],
                null,
                SaasTrialEndingEmailTemplateService::FROM_NAME,
                true
            );
        } catch (Throwable $e) {
            Log::warning('Proeftijd-aankondiging versturen mislukt.', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $profile->update(['trial_notice_sent_at' => now()]);

        return true;
    }

    private function recipientEmail(CompanyBillingProfile $profile, Company $company): ?string
    {
        $billing = $profile->billingEmailForCompany();
        if ($billing) {
            return $billing;
        }

        $admin = User::query()
            ->where('company_id', $company->id)
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        $email = trim((string) ($admin?->email ?? ''));

        return $email !== '' ? $email : null;
    }
}
